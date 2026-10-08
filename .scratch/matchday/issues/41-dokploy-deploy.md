# 41 — Deploy released images to Dokploy, with manual rollback

**What to build:** A release is deployed to production on its own. After the image job (ticket 40), the `release` workflow sets `IMAGE_TAG` in the Dokploy compose app's environment to the new version and starts a Dokploy deploy. It then waits for the deploy result, so a failed deploy turns the workflow red. Any already published version can also be deployed by hand from the Actions tab, which is how a rollback works. The deploy logic lives in a shell script under `.infrastructure/`, so the maintainer can also run it locally in an emergency.

**Blocked by:** 40

**Status:** resolved

- [x] `compose.yml` runs every app service on `ghcr.io/reinerttomas/matchday:${IMAGE_TAG}` and no longer has a `build:` section, so Dokploy only pulls. `IMAGE_TAG` is still required.
- [x] A script `.infrastructure/dokploy-deploy.sh <tag>` takes `DOKPLOY_URL`, `DOKPLOY_API_KEY` and `DOKPLOY_COMPOSE_ID` from the environment and fails fast when any are missing. It:
    - reads the compose app's current env (`compose.one`),
    - replaces the `IMAGE_TAG=` line, or appends one if it is missing, and leaves every other line as it was,
    - saves the env (`compose.saveEnvironment`; it replaces the whole string, hence the read first),
    - starts a deploy (`compose.deploy`) titled `Release <tag>`,
    - polls the compose app's deployments (`deployment.allByCompose`) until the new deployment is `done` (exit 0) or `error` (exit non-zero), with a timeout.
- [x] The script never prints the env or the API key (the env holds every production secret), runs with `set -euo pipefail` and uses only `curl` and `jq`.
- [x] A `deploy` workflow runs on `workflow_dispatch` with a required `tag` input and on `workflow_call`. Before deploying, it checks that the tag exists in GHCR, then runs the script. It uses the `production` GitHub Environment (the Dokploy secrets live there) and a concurrency group, so two deploys never overlap.
- [x] The `release` workflow calls `deploy` with the version from the image job. A run with an older version from the Actions tab rolls back.
- [x] The job summary names the deployed version and the Dokploy deployment result.
- [x] `.env.dokploy` documents that `IMAGE_TAG` is managed by the release workflow.
- [x] The README deployment section covers: Dokploy pulling from GHCR (registry credentials in Dokploy, or a public package), the `production` environment and its three secrets, how a release reaches production, and how to roll back from the Actions tab or locally with the script.
- [x] `composer ci:check` passes.

## Notes

- Decided on 2026-10-08: a custom script rather than a marketplace Dokploy action. There is no official action, and the community ones (`benbristow/…`, `qbix-tech/…`, `nhridoy/…`) only trigger a deploy and cannot change the env. Running a third-party action with the Dokploy API key isn't worth it for one `curl` call.
- The deploy flow is based on `reinerttomas/laravel-subtrack-app`'s `release.yml`. That workflow does not wait for the deploy result; this one does.
- Manual steps for the maintainer:
    - Create the `production` environment with `DOKPLOY_URL`, `DOKPLOY_API_KEY` and `DOKPLOY_COMPOSE_ID`. Limit its deployment branches to `main`, so a manual deploy cannot run from another branch. Optionally require the maintainer's approval before each deploy. See ticket 39's notes for the plan limits.
    - Let Dokploy pull from GHCR: either add `ghcr.io` with a PAT that has `read:packages` as a registry in Dokploy, or make the package public.
    - Run the first real deploy and a rollback.
