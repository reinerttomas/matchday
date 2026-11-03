# Herd site name for this workspace: matchday-<workspace>, lowercase, at most 50 characters.
slug=$(printf '%s' "${SUPERSET_WORKSPACE_NAME:-$(basename "$PWD")}" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^-+|-+$//g' | cut -c1-41)
SITE="matchday-${slug%-}"
