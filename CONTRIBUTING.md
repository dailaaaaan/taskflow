# Contributing to TaskFlow

This guide describes how work moves from an idea to `main`. The engineering conventions (architecture, code style, testing policy) are in [`CLAUDE.md`](CLAUDE.md) and apply to every contributor, human or AI.

## Development setup

```bash
cp .env.example .env              # set DB_PORT if 5432 is already taken
docker compose up -d --wait       # PostgreSQL (app and test databases)
composer setup                    # dependencies, app key, migrations, asset build
php artisan db:seed               # demo workspace
npm run prepare                   # Git hooks (npm lifecycle scripts are disabled by .npmrc)
composer run dev                  # http://localhost:8000
```

## Workflow

```
main ──●───────────────────────●──────────►
        \                     /  merge commit (CI green)
         ●───●───●───●───●───●   feat/teams
         atomic, conventional commits
```

1. **Branch from an up-to-date `main`**
    ```bash
    git switch main && git pull
    git switch -c feat/short-description
    ```
2. **Commit in small, atomic steps.** Every commit builds and passes the quality gate.
3. **Open a pull request** using the template, and link the issue it closes.
4. **CI must be green** before merging.
5. **Merge with a merge commit** and delete the branch.

### Branch names

`<type>/<kebab-case-description>`. For example `feat/teams`, `fix/card-position`, `docs/readme`, `chore/setup`.

## Commit messages

Commits follow [Conventional Commits](https://www.conventionalcommits.org). They are validated by commitlint locally (Git hook) and in CI.

```
<type>(<optional scope>): <imperative summary, max 72 chars>

<optional body: what and why, wrapped at 100 chars>

<optional footer: Closes #12, BREAKING CHANGE: ...>
```

| Type       | Use for                                                   |
| ---------- | --------------------------------------------------------- |
| `feat`     | A new user-facing capability, together with its tests     |
| `fix`      | A bug fix                                                 |
| `refactor` | A code change that neither fixes a bug nor adds a feature |
| `perf`     | A performance improvement                                 |
| `test`     | Adding or fixing tests, or test infrastructure            |
| `docs`     | Documentation only                                        |
| `build`    | Build system or dependencies                              |
| `ci`       | CI configuration                                          |
| `chore`    | Maintenance that doesn't fit the types above              |
| `style`    | Formatting only, no behaviour change                      |
| `revert`   | Reverting a previous commit                               |

Examples:

```
feat(teams): invite members with signed invite links
fix(cards): keep order stable when two cards share a position
test(boards): cover 403 responses for non-members
```

## Quality gate

Run it before pushing. CI runs the same checks.

```bash
composer ci:check
```

It runs Pint, Larastan (level 8), the Vite+ lint and format check, TypeScript and the Pest suite. The pre-commit hook already formats staged PHP and TypeScript files.

## Pull request checklist

- [ ] Tests cover the change, including `403` cases for protected actions
- [ ] `composer ci:check` passes
- [ ] `CHANGELOG.md` updated under `[Unreleased]`
- [ ] Docs or ADRs updated if behaviour or architecture changed
- [ ] Screenshots attached for UI changes

## Architecture decisions

Significant technical decisions are recorded as ADRs in [`docs/adr/`](docs/adr/). To add one, copy [`docs/adr/template.md`](docs/adr/template.md) to the next number, fill it in, and include it in the pull request that implements the decision.
