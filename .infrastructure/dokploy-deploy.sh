#!/usr/bin/env bash
#
# Deploys an image tag to the Dokploy compose app: sets IMAGE_TAG in the app's
# env, starts a deploy and waits until Dokploy reports its result.
#
# Usage: .infrastructure/dokploy-deploy.sh <tag>
#
# Environment:
#   DOKPLOY_URL             Dokploy base URL, e.g. https://dokploy.example.com
#   DOKPLOY_API_KEY         Dokploy API key
#   DOKPLOY_COMPOSE_ID      ID of the compose app
#   DOKPLOY_DEPLOY_TIMEOUT  Seconds to wait for the deploy result (default 600)
#   DOKPLOY_POLL_INTERVAL   Seconds between status checks (default 5)
#
# The compose env holds every production secret, so it only travels through
# pipes and a variable and never reaches the output or a command line.

set -euo pipefail

if [[ $# -ne 1 ]]; then
    echo "Usage: $0 <tag>" >&2
    exit 64
fi

tag=$1

# The tag is written into the production env, so only the shapes the release
# image job pushes are accepted.
if [[ ! $tag =~ ^([0-9]+\.[0-9]+\.[0-9]+|sha-[0-9a-f]{7,40})$ ]]; then
    echo "Invalid tag: expected X.Y.Z or sha-<commit>." >&2
    exit 64
fi

missing=()
for name in DOKPLOY_URL DOKPLOY_API_KEY DOKPLOY_COMPOSE_ID; do
    if [[ -z ${!name:-} ]]; then
        missing+=("$name")
    fi
done
if [[ ${#missing[@]} -gt 0 ]]; then
    echo "Missing environment variables: ${missing[*]}" >&2
    exit 64
fi

for tool in curl jq; do
    if ! command -v "$tool" > /dev/null; then
        echo "$tool is required." >&2
        exit 69
    fi
done

timeout=${DOKPLOY_DEPLOY_TIMEOUT:-600}
interval=${DOKPLOY_POLL_INTERVAL:-5}
if [[ ! $timeout =~ ^[1-9][0-9]*$ || ! $interval =~ ^[1-9][0-9]*$ ]]; then
    echo "DOKPLOY_DEPLOY_TIMEOUT and DOKPLOY_POLL_INTERVAL must be positive whole seconds." >&2
    exit 64
fi

base_url=${DOKPLOY_URL%/}/api
title="Release $tag"

# The API key goes in through a file descriptor, so it never shows in the
# process list.
api() {
    curl --fail --silent --show-error --max-time 30 \
        --header @<(printf 'x-api-key: %s\n' "$DOKPLOY_API_KEY") \
        --header 'Accept: application/json' \
        "$@"
}

api_get() {
    api --get --data-urlencode "composeId=$DOKPLOY_COMPOSE_ID" "$base_url/$1"
}

# Sends stdin as the JSON body.
api_post() {
    api --header 'Content-Type: application/json' --data-binary @- "$base_url/$1"
}

# compose.saveEnvironment replaces the whole env, so the rest of it is carried
# over line by line. A response without the app's env must never get through,
# or it would wipe the production env.
set_image_tag='
    if (.composeId != $composeId) or (has("env") | not) then
        error("compose.one did not return the compose app and its env")
    else
        .
    end
    | (.env // "") as $env
    | ($env | endswith("\n")) as $trailing_newline
    | ($env | if $trailing_newline then .[:-1] else . end | split("\n")) as $lines
    | ("IMAGE_TAG=" + $tag) as $image_tag_line
    | ($lines | map(test("^\\s*IMAGE_TAG=")) | any) as $has_image_tag
    | (if $has_image_tag then
          $lines | map(if test("^\\s*IMAGE_TAG=") then $image_tag_line + (if endswith("\r") then "\r" else "" end) else . end)
      else
          $lines + [$image_tag_line]
      end) as $new_lines
    | {
        composeId: $composeId,
        env: (($new_lines | join("\n")) + (if $trailing_newline then "\n" else "" end))
    }
'

echo "Setting IMAGE_TAG=$tag in the Dokploy compose app"
# Built in full before the save, so a failed read can never post a partial env.
environment_body=$(api_get compose.one | jq --arg composeId "$DOKPLOY_COMPOSE_ID" --arg tag "$tag" "$set_image_tag")
printf '%s' "$environment_body" | api_post compose.saveEnvironment > /dev/null
unset environment_body

# compose.deploy only queues the deploy and returns no ID, so the new deployment
# is the one with this title that was not there before.
known_deployment_ids=$(api_get deployment.allByCompose | jq --compact-output 'map(.deploymentId)')

echo "Starting Dokploy deploy \"$title\""
jq --null-input --arg composeId "$DOKPLOY_COMPOSE_ID" --arg title "$title" '{composeId: $composeId, title: $title}' \
    | api_post compose.deploy > /dev/null

find_new_deployment='
    map(select(.title == $title and (.deploymentId | IN($known[]) | not)))
    | sort_by(.createdAt)
    | last
    | if . then "\(.deploymentId) \(.status // "running")" else empty end
'

deadline=$((SECONDS + timeout))
deployment_id=
status=
reported_status=

while true; do
    if ((SECONDS >= deadline)); then
        status=timeout
        break
    fi

    sleep "$interval"

    # A failed status check is retried until the deadline, since the deploy is
    # already running and only its result is missing.
    if ! deployments=$(api_get deployment.allByCompose); then
        echo "Could not read the deployments, retrying" >&2
        continue
    fi

    new_deployment=$(jq --raw-output --argjson known "$known_deployment_ids" --arg title "$title" "$find_new_deployment" <<< "$deployments")
    if [[ -z $new_deployment ]]; then
        continue
    fi

    read -r deployment_id status <<< "$new_deployment"

    if [[ $status != "$reported_status" ]]; then
        echo "Deployment $deployment_id: $status"
        reported_status=$status
    fi

    case $status in
        done | error | cancelled) break ;;
    esac
done

if [[ -n ${GITHUB_OUTPUT:-} ]]; then
    {
        echo "deployment_id=$deployment_id"
        echo "status=$status"
    } >> "$GITHUB_OUTPUT"
fi

case $status in
    done)
        echo "Deployed $tag."
        ;;
    timeout)
        echo "No result from Dokploy within ${timeout}s. Check the deployment in Dokploy." >&2
        exit 1
        ;;
    *)
        echo "Dokploy deployment ended with status \"$status\". Its log is in Dokploy." >&2
        exit 1
        ;;
esac
