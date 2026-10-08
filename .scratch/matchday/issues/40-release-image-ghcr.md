# 40 — Releases publish the Docker image to GHCR

**What to build:** When the `release` workflow creates a release (ticket 39), it first runs the same CI checks as `tests.yml`. If they pass, it builds the production image (`deploy` target of the `Dockerfile`) and pushes it to `ghcr.io/reinerttomas/matchday`, tagged with the release version. Each released version then has one immutable image that Dokploy can pull, and Dokploy no longer builds anything itself.

**Blocked by:** 39

**Status:** ready-for-agent

- [ ] `tests.yml` can also be called as a reusable workflow (`workflow_call`) and keeps running on push and pull requests as before.
- [ ] The `release` workflow has an image job that runs only when a release was created and only after the reused CI checks pass.
- [ ] The image job uses `docker/setup-buildx-action`, `docker/login-action` (GHCR with `GITHUB_TOKEN`), `docker/metadata-action` and `docker/build-push-action`, all pinned to commit SHAs with the version in a comment. The job requests only `contents: read` and `packages: write`.
- [ ] The image is built for `linux/amd64` from the `deploy` target with `VITE_APP_NAME=Matchday` and tagged `X.Y.Z` (without the `v`), `sha-<short sha>` and `latest`. It carries the OCI labels from `docker/metadata-action`, including the source repository so GHCR links the package to the repo.
- [ ] Layers are cached between runs (`type=gha`).
- [ ] The image job exposes the pushed version tag as an output for the deploy job (ticket 41).
- [ ] The job summary names the pushed image and its tags.
- [ ] `composer ci:check` passes.

## Notes

- The tag without the `v` matches the usual Docker convention and is what `IMAGE_TAG` in Dokploy's `.env` holds after ticket 41.
- The GHCR package starts private. Ticket 41 decides how Dokploy authenticates to pull it.
- Verify after the next release: `ghcr.io/reinerttomas/matchday:<version>` exists, is linked to the repository and runs with the existing `compose.yml`.
