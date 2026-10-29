# Testing strategy

## Goals

1. **Authorization can never silently regress.** Every protected action has tests that assert `403` for each unauthorized role and for non-members.
2. **Domain rules are proven, not assumed.** Positions, role invariants and realtime payloads have focused tests.
3. **The architecture cannot erode.** Layering rules are executable tests.
4. **Fast feedback.** The full PHP suite runs in seconds, with no network and no sleeps.

## Test pyramid

```
            ▲  Realtime E2E (Playwright, two browsers)      few, slow
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
| Browser       | `tests/Browser`             | Pest browser testing    | Critical journeys: auth, invitations, board CRUD, drag & drop                     |
| Frontend unit | `resources/js/**/*.test.ts` | Vitest                  | Board reducer and pure helpers                                                    |
| Realtime E2E  | `e2e/`                      | Playwright              | Two users see each other's changes live                                           |

## Running tests

| Command                                 | Runs                               |
| --------------------------------------- | ---------------------------------- |
| `php artisan test`                      | All Pest suites                    |
| `php artisan test --filter=BoardPolicy` | Tests matching a name              |
| `vendor/bin/pest --testsuite=Arch`      | Architecture tests only            |
| `composer ci:check`                     | Full quality gate, including tests |

## Reports and records

Every CI run on GitHub Actions produces:

- **Pest test report**: a check run on the commit or PR listing every test with its result (JUnit).
- **Coverage summary**: in the job summary of the `Tests (Pest)` job.
- **Artifacts** (`test-reports`, kept 14 days): `junit.xml`, `clover.xml` and the raw Pest output.

Coverage is measured in CI with PCOV. The target is **≥ 80 %** on `app/Actions`, `app/Policies` and `app/Support`.

## Inventory

Updated at the end of each phase.

| Phase        | Suite                                   | Tests | Notes                                                                                                                                                |
| ------------ | --------------------------------------- | ----- | ---------------------------------------------------------------------------------------------------------------------------------------------------- |
| 1 · Setup    | Feature (starter kit auth and settings) | 39    | Registration, login, password reset, email verification, 2FA, passkeys, profile                                                                      |
| 1 · Setup    | Unit                                    | 1     | Starter kit example                                                                                                                                  |
| 1 · Setup    | Architecture                            | 9     | PHP and security presets, layering rules                                                                                                             |
| 2 · Database | Feature (`tests/Feature/Models`)        | 10    | Membership roles and pivot, uniqueness, cascades, null-on-delete, ordering, date cast                                                                |
| 2 · Database | Feature (`tests/Feature/Database`)      | 2     | Demo seeder: admin account, team, ordered lists and cards                                                                                            |
| 3 · Auth     | Feature (`GitHubLoginTest`)             | 13    | Redirect, feature flag, new / returning / linked accounts, unverified takeover, 2FA hand-off, missing email, cancelled and expired OAuth, guest-only |
| 3 · Auth     | Browser (`tests/Browser`)               | 6     | Register → verify notice, login → dashboard → logout, invalid credentials, axe accessibility and no JS errors on login, register and forgot-password |

**Total after phase 3: 80 tests.**

All database tests run against **PostgreSQL 17** (`taskflow_test`), the same engine as production, so constraints and cascades are verified for real.
