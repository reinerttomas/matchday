# 39 — Releases with a changelog via release-please

**What to build:** Releases are cut from Conventional Commits with release-please. Every push to `main` opens or updates one Release PR that carries the next semver version and the new `CHANGELOG.md` entries. Merging that PR tags `vX.Y.Z` and publishes a GitHub Release with the same notes. A push on its own never tags anything, so the release happens only when the Release PR is merged. The project is just starting, so the first release is `v0.1.0`.

**Blocked by:** —

**Status:** resolved

- [x] A `release` workflow runs on push to `main` with `googleapis/release-please-action`, pinned to a commit SHA with the version in a comment like the actions in `tests.yml`. It requests only `contents: write` and `pull-requests: write`.
- [x] release-please uses a manifest config (release type `simple`, a single package at the repo root). The first release is `0.1.0` (`initial-version`). Before 1.0, a breaking change bumps the minor version and not the major (`bump-minor-pre-major`). `feat` bumps the minor version and `fix` bumps the patch.
- [x] The Release PR title, which becomes the squash commit on `main`, has no scope (`chore: release 0.1.0`, not the default `chore(main): release 0.1.0`). This matches the repo's unscoped commit messages.
- [x] The changelog shows the Features, Bug Fixes and Performance sections. Commits of the other allowed types (`chore`, `docs`, `ci`, `build`, `refactor`, `test`, `style`) stay out of it and do not open a Release PR by themselves.
- [x] The release job exposes `release_created` and `tag_name` as job outputs, so later jobs in the same workflow can run only when a release was created. Tags created with `GITHUB_TOKEN` do not trigger other workflows, so the image build and deploy must run in this workflow.
- [x] `vp check` ignores `CHANGELOG.md` and `.release-please-manifest.json`. release-please writes them in its own format (2-space JSON, its own Markdown spacing), which `vp fmt --check` rejects, and it rewrites them on every update, so a manual reformat would not last. Without the ignore, the release commit on `main` fails `composer ci:check`, and the CI gate from ticket 40 blocks the image.
- [x] The README says how to release: merge the Release PR, and use a `Release-As: x.y.z` footer to force a version.
- [x] `composer ci:check` passes.

## Notes

- Decided on 2026-10-08:
    - **Tool:** release-please rather than semantic-release or git-cliff. The repo already enforces Conventional Commits, and the Release PR gives a deliberate point at which to release.
    - **One workflow:** release, image build (ticket 40) and deploy (ticket 41) are jobs of the same `release` workflow chained with `needs`/`if`, because of the `GITHUB_TOKEN` trigger limitation above.
- Manual step for the maintainer: enable **Settings → Actions → General → Allow GitHub Actions to create and approve pull requests**, or release-please cannot open the Release PR.
- Recommended repository settings (manual, Settings on GitHub). The repo has no remote yet, so none of this is checked. On a private repo on the Free plan, rulesets are not enforced, and as far as I know Environments with protection rules (ticket 41) aren't available either. Both need a public repo or GitHub Pro.
    - **Merge settings:** allow only squash merge, with the PR title as the default commit message. release-please reads the commits on `main`, so PR titles must follow Conventional Commits. Delete head branches on merge.
    - **Ruleset for `main`:** restrict deletions, block force pushes, require a pull request (0 approvals), require the `ci` status check from `tests.yml`, require linear history. Add the **Repository admin** role to the bypass list (mode "Always").
    - **Ruleset for tags `v*`:** restrict deletions and updates. Leave creation open, because release-please creates the tags as `github-actions[bot]`. No bypass.
- Decided on 2026-10-08: **direct pushes to `main` stay, through the admin bypass** (rather than a PR-only flow).
    - The maintainer keeps committing straight to `main`. The ruleset still stops everyone else and any automation, and blocks force pushes and deletion.
    - The bypass also skips the required `ci` check, so a broken commit is caught by `tests.yml` on `main` after the push. It still cannot reach production, because the release workflow runs the CI checks before building the image (ticket 40).
    - A Release PR opened with `GITHUB_TOKEN` does not trigger `tests.yml`, so its required `ci` check never reports. The maintainer merges it through the bypass. release-please therefore keeps `GITHUB_TOKEN`, and no GitHub App token or PAT is needed. If the repo ever moves to a PR-only flow, release-please needs an App token (`actions/create-github-app-token`) so the Release PR runs CI.
- Verify after merging to `main`: a Release PR proposing `0.1.0` appears with the `autorelease: pending` label. Merging it creates the `v0.1.0` tag and GitHub Release. release-please finds the merged Release PR by that label, so if it is missing, create the `autorelease: pending` and `autorelease: tagged` labels by hand. `pull-requests: write` covers labelling, so `issues: write` is not needed.
