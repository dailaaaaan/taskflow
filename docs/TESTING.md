# Testing strategy

## Goals

1. **Authorization can never silently regress.** Every protected action has tests that assert `403` for each unauthorized role and for non-members.
2. **Domain rules are proven, not assumed.** Positions, role invariants and realtime payloads have focused tests.
3. **The architecture cannot erode.** Layering rules are executable tests.
4. **Fast feedback.** The full PHP suite runs in seconds, with no network and no sleeps.

## Test pyramid

```
            ▲  E2E (Playwright, two browsers, touch)        few, slow
           ▲▲  Browser journeys (Pest browser)
          ▲▲▲  Feature tests (HTTP + DB + Policies)         most of the value
         ▲▲▲▲  Unit tests (PHP pure classes, TS reducer)    many, fast
        ▬▬▬▬▬  Architecture tests + static analysis (L8)    always on
```

| Suite         | Location                    | Tooling                 | What it covers                                                                    |
| ------------- | --------------------------- | ----------------------- | --------------------------------------------------------------------------------- |
| Unit          | `tests/Unit`                | Pest                    | Pure PHP classes (`Position`, enums)                                              |
| Feature       | `tests/Feature`             | Pest, `RefreshDatabase` | Endpoints, validation, Policies (`403` matrix), Actions, broadcast events (faked) |
| Architecture  | `tests/Arch`                | Pest `arch()`           | Layering rules and security preset                                                |
| Browser       | `tests/Browser`             | Pest browser testing    | Critical journeys, keyboard use, phone screens and an axe audit of every page     |
| Frontend unit | `resources/js/**/*.test.ts` | Vitest                  | Board reducer and pure helpers                                                    |
| E2E           | `e2e/`                      | Playwright              | Live updates between two users, reconnection, touch drag, slow and failed visits  |

## Running tests

| Command                                 | Runs                               |
| --------------------------------------- | ---------------------------------- |
| `php artisan test`                      | All Pest suites                    |
| `php artisan test --filter=BoardPolicy` | Tests matching a name              |
| `vendor/bin/pest --testsuite=Arch`      | Architecture tests only            |
| `npm test`                              | Frontend unit tests (Vitest)       |
| `npm run e2e`                           | E2E tests (needs a build)          |
| `composer ci:check`                     | Full quality gate, including tests |

## Reports and records

Every CI run on GitHub Actions produces:

- **Pest test report**: a check run on the commit or PR listing every test with its result (JUnit).
- **Coverage summary**: in the job summary of the `Tests (Pest)` job.
- **Artifacts** (`test-reports`, kept 14 days): `junit.xml`, `clover.xml` and the raw Pest output.

Coverage is measured in CI with PCOV. The target is **≥ 80 %** on `app/Actions`, `app/Policies` and `app/Support`.

## Inventory

Updated at the end of each phase.

| Phase           | Suite                                                        | Tests | Notes                                                                                                                                                                                                                                 |
| --------------- | ------------------------------------------------------------ | ----- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 · Setup       | Feature (starter kit auth and settings)                      | 39    | Registration, login, password reset, email verification, 2FA, passkeys, profile                                                                                                                                                       |
| 1 · Setup       | Unit                                                         | 1     | Starter kit example                                                                                                                                                                                                                   |
| 1 · Setup       | Architecture                                                 | 9     | PHP and security presets, layering rules                                                                                                                                                                                              |
| 2 · Database    | Feature (`tests/Feature/Models`)                             | 10    | Membership roles and pivot, uniqueness, cascades, null-on-delete, ordering, date cast                                                                                                                                                 |
| 2 · Database    | Feature (`tests/Feature/Database`)                           | 2     | Demo seeder: admin account, team, ordered lists and cards                                                                                                                                                                             |
| 3 · Auth        | Feature (`GitHubLoginTest`)                                  | 13    | Redirect, feature flag, new / returning / linked accounts, unverified takeover, 2FA hand-off, missing email, cancelled and expired OAuth, guest-only                                                                                  |
| 3 · Auth        | Browser (`tests/Browser`)                                    | 6     | Register → verify notice, login → dashboard → logout, invalid credentials, axe accessibility and no JS errors on login, register and forgot-password                                                                                  |
| 4 · Teams       | Feature (`TeamPolicyTest`)                                   | 7     | Role × ability matrix (admin / member / outsider) for 6 abilities, `roleOf()`                                                                                                                                                         |
| 4 · Teams       | Feature (`TeamActionsTest`)                                  | 9     | CreateTeam, CreateInvitation, AcceptInvitation (never demotes), ChangeMemberRole and RemoveTeamMember with the last-admin invariant                                                                                                   |
| 4 · Teams       | Feature (HTTP: management, members, invitations)             | 33    | Dashboard scoping, CRUD, validation, **403 before validation**, 404 across teams, signed / tampered / unsigned / expired / revoked links, guest redirect                                                                              |
| 4 · Teams       | Browser (`TeamJourneyTest`)                                  | 3     | Create a team, join through an invite link after logging in, axe accessibility on dashboard, team and settings                                                                                                                        |
| 5 · Boards      | Unit (`PositionTest`)                                        | 4     | First position and append-after-last with gaps                                                                                                                                                                                        |
| 5 · Boards      | Feature (`BoardPolicyTest`)                                  | 6     | Role × ability matrix for view, collaborate, update and delete, `create` with the team as context, boards of other teams                                                                                                              |
| 5 · Boards      | Feature (`BoardActionsTest`, `BoardTest`, `TeamActionsTest`) | 9     | CreateBoard, CreateList and CreateCard append in order, `Board::cards()` through lists, color enum cast, removed members are unassigned in that team only                                                                             |
| 5 · Boards      | Feature (HTTP: boards, lists, cards)                         | 40    | Team page boards and counts, ordered board page, CRUD, validation, assignees limited to teammates, **403 before validation**, 404 for lists and cards of other boards, guest redirect on every endpoint                               |
| 5 · Boards      | Browser (`BoardJourneyTest`)                                 | 3     | Create a board, add a list and a card, edit card details, admin controls hidden from members, axe on the board page and the card modal                                                                                                |
| 6 · Drag & drop | Unit (`PositionTest`)                                        | +20   | Midpoint between neighbours, start / end / empty, clamped index, renumbering when no gap is left, order preserved after every insert                                                                                                  |
| 6 · Drag & drop | Feature (`MoveCardTest`, `MoveListTest`)                     | 22    | MoveCard and MoveList within and across lists, one write while a gap exists, renumbering, other boards untouched, target list of another board rejected, **403 before validation**, 404 across boards, partial reload of `lists` only |
| 6 · Drag & drop | Browser (`DragAndDropTest`)                                  | 5     | Keyboard moves across lists, within a list and of lists, with their screen reader announcements; rollback and error toast after a rejected move; axe                                                                                  |
| 6 · Drag & drop | Frontend unit (Vitest)                                       | 27    | Board reducer (moves, clamping, identity of unchanged lists, no mutation) and drag & drop helpers (drop targets, keyboard targets)                                                                                                    |
| 7 · Realtime    | Feature (`BoardChannelTest`)                                 | 5     | Presence channel with the real Pusher driver, signed locally: admin and member join, non-members, members of another team and guests get 403                                                                                          |
| 7 · Realtime    | Feature (`BoardBroadcastTest`, `BroadcastFailureTest`)       | 12    | Each of the eight events: channel, payload and `toOthers()`; payload limit with escaped characters; rejected changes send nothing; a failing broadcaster keeps the change                                                             |
| 7 · Realtime    | Feature (`RealtimeConfigTest`)                               | 4     | Live updates off without Pusher or a key, public settings only, Pusher-compatible host                                                                                                                                                |
| 7 · Realtime    | Architecture                                                 | +1    | Every event broadcasts synchronously and is rescued                                                                                                                                                                                   |
| 7 · Realtime    | Frontend unit (Vitest)                                       | +11   | Remote actions: created, updated, deleted and renamed, including items that already exist or no longer do                                                                                                                             |
| 7 · Realtime    | E2E (`e2e/realtime.spec.ts`)                                 | 1     | Two users in two browsers: presence, create, move, edit, list create and rename, delete, `X-Socket-ID`, leaving the board                                                                                                             |
| 8 · Polish      | Feature (`ErrorPagesTest`)                                   | 11    | Error page per status, unknown URLs, signed-in link, no shared data, debug screen kept, failed writes and JSON clients untouched                                                                                                      |
| 8 · Polish      | Feature (`BoardQueriesTest`)                                 | 7     | Same queries for any board size, partial reload, one role lookup per request, two users never mixed up, role forgotten after a membership change                                                                                      |
| 8 · Polish      | Browser (`AccessibilityTest`)                                | 24    | axe at the strictest level on 12 pages, each in light and dark mode                                                                                                                                                                   |
| 8 · Polish      | Browser (`KeyboardNavigationTest`)                           | 6     | Skip link, focus returned after the card modal and three inline forms, page announcements                                                                                                                                             |
| 8 · Polish      | Browser (`ResponsiveTest`)                                   | 7     | Six pages fit a phone, and only the board scrolls sideways                                                                                                                                                                            |
| 8 · Polish      | Browser (`ErrorHandlingTest`)                                | 2     | Error page and its way back, toast after a clash with another user                                                                                                                                                                    |
| 8 · Polish      | Frontend unit (Vitest)                                       | +3    | Messages of failed requests                                                                                                                                                                                                           |
| 8 · Polish      | E2E (`e2e/navigation`, `touch`, `realtime`)                  | +4    | Skeleton on a held request, failed visit keeps the page, long press and drag with real touch events, catching up after a dropped connection                                                                                           |

**Total after phase 8: 366 tests** (320 Pest + 41 Vitest + 5 E2E).

The E2E tests run a local [Reverb](https://reverb.laravel.com) server with throwaway keys, so the real Pusher driver and `pusher-js` are exercised with no external network and no secrets. They reseed the `taskflow_test` database, never the development one. Slow and failed requests are held or aborted by the test itself, never with sleeps.

All database tests run against **PostgreSQL 17** (`taskflow_test`), the same engine as production, so constraints and cascades are verified for real.
