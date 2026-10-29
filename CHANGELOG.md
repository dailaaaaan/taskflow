# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0] - 2026-10-29

### Added

- Boards: every team member creates boards with a title and an accent color; admins edit and delete them. The team page lists its boards with their list and card counts.
- Board page with lists and cards ordered by `(position, id)`, loaded with eager loading in a fixed number of queries whatever the board size.
- Lists: add, rename and delete (with their cards).
- Cards: quick add at the bottom of a list (Enter adds the card and keeps the form open) and a details modal for title, description, assignee and due date. Overdue dates are highlighted.
- `BoardPolicy` (view, create, collaborate, update, delete), tested as a role × ability matrix.
- `App\Support\Position` appends lists and cards with gaps of 1024 ([ADR-0003](docs/adr/0003-integer-gap-positions.md)).
- Browser tests for the board journey, with axe checks on the board page and the card modal.

### Changed

- Lists and cards are authorized through their board with nested, scoped routes instead of a policy per model ([ADR-0007](docs/adr/0007-authorize-lists-and-cards-through-the-board.md)).
- Removing a member, or a member leaving, unassigns their cards in that team.

### Fixed

- The phase 4 rows of the test inventory in `docs/TESTING.md` were not rendered as part of the table.

### Security

- A list or card id from another board returns `404`, so ids cannot be swapped in the URL.
- Card assignees are validated against the members of the board's team.

## [0.4.0] - 2026-10-29

### Added

- Teams with **admin** and **member** roles. The creator of a team becomes its first admin.
- `TeamPolicy` (view, update, delete, invite, manage members, leave), tested as a role × ability matrix.
- Reusable **invite links**: HMAC-signed, role-scoped, expiring after 7 days and revocable. Guests log in and are brought back to the link ([ADR-0006](docs/adr/0006-signed-invite-links.md)).
- Member management: change roles, remove members, leave a team.
- Pages: dashboard with the user's teams, team overview, team settings (general, members, invite links, leave, delete) and invitation preview.
- Browser tests for team creation and the invitation journey, with axe checks on the new pages.

### Changed

- Authorization is declared on routes (`->can()`), so it runs before validation.
- API Resources are serialized without the `data` wrapper for Inertia props.
- The sidebar links point to the TaskFlow repository and README.

### Security

- A team can never lose its last admin: demotions and removals lock the team's admin rows inside a transaction.
- Invite links never change the role of an existing member.

## [0.3.0] - 2026-10-29

### Added

- "Continue with GitHub" sign-in (Laravel Socialite) on the login and register pages. Accounts are matched by GitHub id, then linked by verified email, otherwise created as verified accounts ([ADR-0005](docs/adr/0005-github-login-account-linking.md)).
- GitHub sign-in hands users with two-factor authentication to Fortify's challenge, so the second factor is never bypassed.
- `github_id` and `avatar` columns on users. GitHub avatars show up in the UI automatically.
- Browser test suite (Pest Browser + Playwright, headless Chromium) covering registration, login, logout and invalid credentials, plus axe accessibility checks on the auth pages. Runs in CI.
- TaskFlow logo, SVG/ICO favicon and Apple touch icon.

### Changed

- GitHub login is a feature flag: hidden, with `404` routes, until `GITHUB_CLIENT_ID` is configured.
- `users.password` is nullable for accounts created through GitHub.

### Fixed

- Positive `tabindex` values on the login and register forms broke the natural keyboard order (axe rule `tabindex`).

### Security

- Linking GitHub to an existing unverified account discards that account's password, preventing pre-account takeover.

## [0.2.0] - 2026-10-29

### Added

- PostgreSQL 17 for local development via Docker Compose, with a dedicated `taskflow_test` database. The host port is configurable through `DB_PORT`.
- Domain schema: `teams`, `memberships` (unique per team and user, role), `boards`, `board_lists` and `cards`, with foreign keys, cascading deletes and ordering indexes.
- `TeamRole` backed enum and Eloquent models `Team`, `Membership` (custom pivot), `Board`, `BoardList` and `Card`, with typed relations and factories.
- Demo seeder: `demo@taskflow.test` administers a team with one teammate and two boards of ordered lists and cards.

### Changed

- The test suite runs against PostgreSQL locally and in CI (a service container) instead of in-memory SQLite.
- The CI quality job no longer needs a database.

## [0.1.0] - 2026-10-29

### Added

- Project scaffold: Laravel 13, Inertia 3 and React 19 from the official React starter kit, with authentication by Laravel Fortify (registration, login, password reset, email verification, 2FA, passkeys).
- Architecture tests: Pest `php` and `security` presets plus layering rules for controllers, actions, models, requests, policies and enums.
- Git hooks: Pint and Vite+ run on staged files (lint-staged), and commit messages are validated against Conventional Commits (commitlint).
- CI pipeline with a quality job (Pint, Larastan, Vite+ check, TypeScript, dependency audit), a test job (PCOV coverage, JUnit check run, report artifacts) and commit message linting on pull requests.
- Dependabot for Composer, npm and GitHub Actions.
- Documentation: README, `CLAUDE.md`, contributing guide, security policy, architecture decision records, testing strategy and project plan.
- Issue forms, pull request template and code owners.

### Changed

- Static analysis raised from Larastan level 7 to level 8.
- Eloquent strict mode is enabled outside production (lazy loading, discarded and missing attributes now throw).
- The authenticated user is injected into the settings controllers with `#[CurrentUser]`.

### Fixed

- Possible null dereference when rendering a passkey's creation date in security settings.

[Unreleased]: https://github.com/dailaaaaan/taskflow/compare/v0.5.0...HEAD
[0.5.0]: https://github.com/dailaaaaan/taskflow/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/dailaaaaan/taskflow/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/dailaaaaan/taskflow/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/dailaaaaan/taskflow/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/dailaaaaan/taskflow/releases/tag/v0.1.0
