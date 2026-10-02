## Git

- Commit messages MUST follow Conventional Commits: `<type>(<optional scope>): <description>`.
- Allowed types: `feat`, `fix`, `chore`, `refactor`, `docs`, `test`, `ci`, `build`, `perf`, `style`.
- Description is imperative, lowercase, no trailing period. Breaking changes use `!` after the type/scope or a `BREAKING CHANGE:` footer.
- Examples: `feat(auth): add passkey login`, `chore: install nunomaduro/essentials`, `fix(api)!: rename user id field`.
- Before running `git commit`, run `composer ci:check` (vp check, tsc --noEmit, pint --test, phpstan, pest) and commit only when it passes. Fix failures first; never skip or bypass the check. This mirrors CI in `.github/workflows/tests.yml`.
