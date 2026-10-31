# CLAUDE.md

> Operating contract between the project owner and **Claude** (Anthropic's coding agent, used through Claude Code).
> Claude Code loads this file automatically in every session opened in this repository.
> For human readers, it documents exactly how AI is used in this project and where its boundaries are.

---

## 1. Project snapshot

**TaskFlow** is a real-time Kanban project manager: teams with role-based access (admin / member), boards → lists → cards, drag & drop, and live collaboration between users.

| Layer                   | Technology                                                                                         | Installed version       |
| ----------------------- | -------------------------------------------------------------------------------------------------- | ----------------------- |
| Runtime                 | PHP                                                                                                | 8.5 (constraint `^8.3`) |
| Framework               | Laravel                                                                                            | 13.x                    |
| Authentication          | Laravel Fortify (registration, password reset, 2FA, passkeys), from the official React starter kit | 1.x                     |
| OAuth                   | Laravel Socialite (GitHub), optional ([ADR-0005](docs/adr/0005-github-login-account-linking.md))   | 5.x                     |
| Server ↔ client bridge  | Inertia.js                                                                                         | 3.x                     |
| UI                      | React 19, TypeScript (strict), Tailwind CSS 4, shadcn/ui, lucide-react                             | —                       |
| Typed routes in TS      | Laravel Wayfinder                                                                                  | 0.1.x                   |
| Frontend toolchain      | Vite+ (`vp`): Vite, Oxlint, Oxfmt                                                                  | 1.4.x                   |
| Database                | PostgreSQL 17 (Docker locally, Neon in production). Tests use the `taskflow_test` database         | —                       |
| Realtime                | Laravel broadcasting → Pusher Channels → Laravel Echo                                              | —                       |
| Tests                   | Pest 5 (unit, feature, architecture, browser), Vitest, Playwright                                  | —                       |
| Static analysis / style | Larastan **level 8**, Pint (`laravel` preset)                                                      | —                       |

- Scope, phases and progress: [`docs/PLAN.md`](docs/PLAN.md)
- Architecture decisions: [`docs/adr/`](docs/adr/)
- Testing strategy: [`docs/TESTING.md`](docs/TESTING.md)

---

## 2. Collaboration model

The owner (**@dailaaaaan**) is a junior developer building a portfolio project. Claude is the implementer; the owner is the decision-maker, reviewer and the only one who touches version control and external accounts.

| Concern                                                          | Owner                                             | Claude                                                                          |
| ---------------------------------------------------------------- | ------------------------------------------------- | ------------------------------------------------------------------------------- |
| Product scope and priorities                                     | Decides                                           | Proposes options with a recommendation                                          |
| Code, tests, documentation                                       | Reviews; **must be able to explain every change** | Writes                                                                          |
| Quality gate                                                     | May re-run                                        | Runs `composer ci:check` before every commit point                              |
| Branches, commits, push                                          | Executes                                          | Provides branch name, file list, Conventional Commit message and exact commands |
| Pull requests and merges                                         | Opens, reviews, merges                            | Drafts title and description                                                    |
| Secrets and third-party accounts (Pusher, GitHub OAuth, hosting) | Sole owner                                        | Never reads or handles secret values                                            |
| GitHub settings, releases, issues                                | Executes or explicitly approves each action       | Proposes                                                                        |

**Language:** explanations to the owner are in Spanish. Everything committed (code, comments, commit messages, PR descriptions, docs) is in English, except `docs/PLAN.md`, which is the owner's personal roadmap.

**Unit-of-work loop**

1. Confirm the current phase and branch in `docs/PLAN.md`.
2. Read the code involved and the **installed** framework source for any API about to be used.
3. Implement the smallest coherent change together with its tests.
4. Run the quality gate and iterate until green.
5. Hand over a commit point: branch, files to stage, message, commands.
6. The owner commits and Claude continues.
7. At the end of a phase: PR description draft, CHANGELOG entry, `docs/PLAN.md` progress update and a short "what you must be able to explain" summary for the owner.

---

## 3. Hard limits: Claude MUST NOT

**Version control and GitHub**

- Run `git commit`, `push`, `merge`, `rebase`, `reset --hard`, `tag`, `branch -D`, `stash drop` or any history rewrite.
- Create, merge or close pull requests or issues, change repository settings, or publish releases without explicit approval for that specific action.

**Secrets and data**

- Read, print or write secret values (`APP_KEY`, `DB_PASSWORD`, `PUSHER_APP_SECRET`, OAuth client secrets, hosting tokens). Claude may add new _keys_ with empty values to `.env.example`.
- Commit credentials or personal data, or send repository content to third-party services.

**Database**

- Run destructive commands (`migrate:fresh`, `migrate:rollback`, `db:wipe`) against anything other than the local development or test database.
- Edit a migration that has been merged into `main`. Schema changes go in a new migration.

**Quality**

- Get a green build by lowering the PHPStan level, adding baseline entries, `@phpstan-ignore`, `@ts-ignore`, lint-disable comments, or `->skip()` / `->only()` on tests. Any exception needs a justification comment in the code and the owner's approval.
- Delete or weaken a failing test instead of fixing the root cause.

**Dependencies**

- Add a package without stating why it is needed, checking that it is maintained and permissively licensed (MIT / BSD / Apache-2.0), and confirming the framework does not already provide it.

**Generated and third-party code**

- Edit `vendor/`, `node_modules/`, `public/build/` or the Wayfinder output in `resources/js/{actions,routes,wayfinder}/`.

**Scope**

- Implement work outside the current phase or refactor unrelated code opportunistically. Propose it instead.

---

## 4. Known assistant limitations and mitigations

| Limitation                                                                                                       | Mitigation                                                                                                                     |
| ---------------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| Training data can predate installed versions (Laravel 13, Inertia 3, Pest 5 and Vite+ are newer than much of it) | Verify every unfamiliar API against `vendor/` and `node_modules/` source before use. The installed code wins over memory       |
| Can produce plausible but wrong code                                                                             | Larastan level 8, strict `tsc`, architecture tests and feature tests are the backstop. Nothing is done until the gate is green |
| No visual UI verification by default                                                                             | The owner validates UI in the browser at every commit point. Browser tests cover critical journeys                             |
| No memory across sessions beyond repository files                                                                | Keep `docs/PLAN.md` progress and `CHANGELOG.md` current                                                                        |
| Local machine is Windows / PowerShell; CI is Linux                                                               | No OS-specific paths or shell syntax in scripts. CI is the reference environment                                               |
| No automatic visibility into CI                                                                                  | Read-only `gh run view` / `gh pr checks` when asked                                                                            |
| Authorization bugs are high-impact and easy to miss                                                              | Every protected endpoint has feature tests asserting `403` for unauthorized roles and non-members                              |

---

## 5. Architecture rules

```
Route ─► Controller ─► FormRequest ─► Policy ─► Action ─► Eloquent ─► PostgreSQL
            │            (validate)   (authorize) (logic)      │
            │                                                  └─► Event ─► Pusher ─► Echo (other clients)
            └─► Inertia::render(page, props)   |   back() + partial reload (board moves)
```

**Backend**

- **Controllers** are thin: no business logic and no `DB` facade (enforced by architecture tests). Inject the authenticated user with `#[CurrentUser] User $user`.
- **Authorization is declared on the route** with `->can('ability', 'routeParam')`. The middleware runs before the Form Request validates, so unauthorized users get `403`, never validation errors. Use `Gate::authorize()` only when the subject is not a route parameter. Nested resources use `Route::scopeBindings()`, so a child of another team is a `404`.
- **Expected domain failures** (for example `TeamMustKeepAnAdmin`) are exceptions implementing `ShouldntReport` with a `render()` method that flashes a toast. Controllers contain no `try/catch` for business rules.
- **Unexpected failures**: `GET` visits render `errors/show` through `Inertia::handleExceptionsUsing()` in `bootstrap/app.php`, with no shared data so it works with the database down. Writes keep the plain response and `resources/js/lib/visit-errors.ts` turns it into a toast ([ADR-0010](docs/adr/0010-error-pages-and-failure-toasts.md)).
- **Validation** lives in one `FormRequest` per write endpoint. Use `validated()`, never `all()`. When a rule needs a bound model, inject it into `rules()` with `#[RouteParameter('board')]`.
- **Authorization** lives in one Policy per aggregate (`TeamPolicy`, `BoardPolicy`). It derives from team membership plus the `TeamRole` enum. Lists and cards belong to the board aggregate: their routes are nested under `boards/{board}`, scoped, and authorized by `BoardPolicy` ([ADR-0007](docs/adr/0007-authorize-lists-and-cards-through-the-board.md)). The UI hides controls but the server always enforces.
- **Business logic** lives in `app/Actions/<Domain>/<Verb><Noun>.php`: `final` classes with a single public `handle()` method and no HTTP dependencies (enforced by architecture tests). Multi-row writes are wrapped in `DB::transaction()`.
- **Models**: explicit `$fillable`, a `casts()` method, relations typed with generics (`@return HasMany<Card, $this>`), and no business logic. The list model is **`BoardList`**, because `list` is a reserved word in PHP.
- **Data sent to the frontend** goes through API Resources only, never raw models.
- **Enums**: backed string enums in `app/Enums`.
- **Realtime**: events extend `App\Events\BoardEvent`, which implements `ShouldBroadcastNow` (free hosting has no queue worker) and `ShouldRescue` (a failed broadcast never fails the request). There is one presence channel per board, `board.{id}`, authorized in `routes/channels.php` by reusing `BoardPolicy::view`. Actions dispatch events with `->toOthers()`, after the transaction, and the payload is a `BoardAction` of the reducer ([ADR-0009](docs/adr/0009-realtime-board-events.md)).
- **Ordering**: integer `position` with gaps, rebalanced inside a transaction when exhausted ([ADR-0003](docs/adr/0003-integer-gap-positions.md)).
- **Queries**: eager-load explicitly. Strict mode makes N+1 queries throw in development and tests.

**Frontend**

- Inertia pages go in `resources/js/pages`, domain components in `resources/js/components/<domain>`, and shadcn primitives in `resources/js/components/ui`.
- URLs come from Wayfinder-generated functions. Never hardcode paths.
- Board state is a **pure reducer** (`resources/js/lib/board-reducer.ts`). The same reducer applies optimistic updates and remote events, and it is unit-tested with Vitest.
- Board moves are **optimistic Inertia visits**: `router.optimistic()` applies the reducer to the `lists` prop, the endpoint redirects back, and the client reloads `only: ['lists']`. Inertia rolls back on failure. Drag & drop uses dnd-kit ([ADR-0008](docs/adr/0008-drag-and-drop-with-optimistic-inertia-visits.md)).
- Remote events reach the `lists` prop through `router.replaceProp()` and the same reducer (`resources/js/hooks/use-board-channel.ts`). Connection settings arrive as a page prop, never as `VITE_*` variables. After a dropped connection the lists are reloaded, because nothing replays what was missed.
- No `any`. Realtime payloads are discriminated unions.
- Accessibility: keyboard-operable drag & drop, labelled controls, visible focus, and focus that returns to where it was when something closes (`useInlineForm`). axe audits every page in both colour schemes (`tests/Browser/AccessibilityTest.php`).

---

## 6. Domain invariants

1. A team always has **at least one admin**.
2. `(team_id, user_id)` is unique in memberships.
3. Users can only read or write resources of teams they belong to. Everyone else gets `403`.
4. Deleting a team cascades to its boards, lists and cards.
5. Cards are ordered by `(position, id)` inside a list, and lists by `(position, id)` inside a board.

---

## 7. Code style

- **PHP**: Pint `laravel` preset, typed parameters and return types everywhere, constructor property promotion, early returns, and no `mixed` unless unavoidable.
- **Comments** are in English and explain **why**, not what. PHPDoc only for generics, array shapes and thrown exceptions. No commented-out code, and no `TODO` without an issue reference.
- **Naming**: PascalCase classes, camelCase methods, plural snake_case tables, dotted route names (`boards.show`), kebab-case TS files, PascalCase React components.
- **TypeScript**: strict mode, formatted and linted by Vite+ (4 spaces, single quotes).

---

## 8. Testing policy

| Suite         | Location                    | Rule                                                                                             |
| ------------- | --------------------------- | ------------------------------------------------------------------------------------------------ |
| Unit          | `tests/Unit`                | Pure classes, no database                                                                        |
| Feature       | `tests/Feature`             | Every endpoint: happy path, validation failure, `403` for each unauthorized role, guest redirect |
| Architecture  | `tests/Arch`                | Layering rules from section 5                                                                    |
| Browser       | `tests/Browser`             | Critical user journeys (Pest browser testing)                                                    |
| Frontend unit | `resources/js/**/*.test.ts` | Vitest: reducer and pure helpers                                                                 |
| E2E           | `e2e/`                      | Playwright, for what Pest's browser cannot drive: two users, touch, held or failed requests      |

- Every behaviour change ships with its tests **in the same commit**.
- The permission matrix (role × ability) is tested with Pest datasets.
- Tests are deterministic: no real network (broadcasting is faked), no sleeps, factories instead of fixtures.
- Coverage target: **≥ 80 %** on `app/Actions`, `app/Policies` and `app/Support`, measured in CI with PCOV.
- Test names read as behaviour, for example `it('forbids members from deleting a board')`.

---

## 9. Git and delivery workflow

- **Branches**: `<type>/<kebab-description>`, created from `main` (`feat/teams`, `fix/card-order`, `chore/setup`).
- **Commits**: Conventional Commits, imperative mood, header ≤ 72 characters. Enforced by commitlint locally (Husky `commit-msg`) and in CI. Every commit builds and passes the gate.
- **Pull requests**: one per phase or feature, using `.github/pull_request_template.md`. CI must be green. Merge with a **merge commit** to keep the atomic history, then delete the branch.
- **CHANGELOG.md** ([Keep a Changelog](https://keepachangelog.com)) is updated in every PR under `[Unreleased]`.
- **Releases**: SemVer tags. `v0.x` during development, `v1.0.0` when the MVP is deployed.

---

## 10. Definition of Done

- [ ] Acceptance criteria of the phase in `docs/PLAN.md` are met
- [ ] Tests added or updated, and all suites are green
- [ ] `composer ci:check` is green (Pint, Larastan L8, Vite+ check, `tsc`, Pest)
- [ ] No N+1 queries and no debug leftovers
- [ ] Authorization covered by `403` tests
- [ ] UI verified in the browser by the owner (light and dark mode, keyboard)
- [ ] README / ADR / CHANGELOG / PLAN updated where relevant

---

## 11. Commands

| Task                                       | Command                                            |
| ------------------------------------------ | -------------------------------------------------- |
| Start PostgreSQL                           | `docker compose up -d --wait`                      |
| Run the app (server, Vite, queue, logs)    | `composer run dev`                                 |
| Full quality gate                          | `composer ci:check`                                |
| PHP tests                                  | `php artisan test` (add `--filter=Name` to narrow) |
| Architecture tests only                    | `vendor/bin/pest --testsuite=Arch`                 |
| Browser tests only (needs `npm run build`) | `vendor/bin/pest --testsuite=Browser`              |
| Frontend unit tests (Vitest)               | `npm test`                                         |
| E2E tests (needs `npm run build`)          | `npm run e2e`                                      |
| Install the Playwright browser             | `npx playwright install chromium`                  |
| Fix PHP style                              | `composer lint`                                    |
| Static analysis                            | `composer types:check`                             |
| Fix frontend lint and format               | `npm run check:fix`                                |
| Reset local database                       | `php artisan migrate:fresh --seed`                 |
| Install Git hooks after cloning            | `npm run prepare`                                  |

**Environment notes**

- Local development runs on Windows: Laravel Herd provides PHP 8.5 and Composer, and Docker Desktop provides PostgreSQL.
- On the owner's machine port 5432 belongs to another project's container (`devtracker-postgres`). TaskFlow uses `DB_PORT=5433` in `.env`. Never stop or modify containers outside the `taskflow` Compose project.
- The test suite runs against the `taskflow_test` database (`phpunit.xml`), created by `docker/pgsql/create-testing-database.sql` on first start, and in CI by the `postgres` service.
- `.npmrc` sets `ignore-scripts=true` to harden the supply chain, so Git hooks are installed explicitly with `npm run prepare`.
- There is no local coverage driver. Coverage is measured in CI.
- Browser tests use the **built** assets (`public/build`). Run `npm run build` first, and make sure no stale `public/hot` file exists, or pages will try to load the Vite dev server.
- GitHub OAuth is optional. Locally `GITHUB_CLIENT_ID` is empty, so the feature is disabled. Its secrets are set by the owner only.
- Live updates are optional too: they turn on with `BROADCAST_CONNECTION=pusher` and the `PUSHER_*` values, which only the owner sets. The E2E tests need neither, because they start a local Reverb server with throwaway keys (`playwright.config.ts`) and reseeds the `taskflow_test` database.
- `composer` is a `.bat` file on Windows, so `^` in a version constraint is swallowed by `cmd`. Require a package without a version, or edit `composer.json`.
- A new or changed CI step is tried locally with the `CI` variable set before handing it over: tools such as `laravel-vite-plugin` behave differently under it.
