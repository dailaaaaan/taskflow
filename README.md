<div align="center">

# TaskFlow

**Real-time Kanban project manager with teams and role-based permissions.**

[![CI](https://github.com/dailaaaaan/taskflow/actions/workflows/ci.yml/badge.svg)](https://github.com/dailaaaaan/taskflow/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
![PHP 8.5](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)
![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![React 19](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=black)
![PHPStan level 8](https://img.shields.io/badge/PHPStan-level%208-brightgreen)

</div>

> 🚧 **Work in progress.** Track progress in the [roadmap](#roadmap) and the [changelog](CHANGELOG.md).

## Overview

TaskFlow lets teams organise work on Kanban boards and see each other's changes as they happen. It is a full-stack portfolio project focused on what production applications need: authentication, relational data modelling, authorization, real-time updates and an automated quality pipeline.

## Roadmap

- [x] Project foundation: CI pipeline, static analysis, architecture tests, Git hooks
- [x] Authentication: registration, login, password reset, 2FA, passkeys and GitHub OAuth
- [x] PostgreSQL schema: teams, memberships, boards, lists, cards
- [x] Teams with **admin / member** roles and signed invite links
- [x] Boards, lists and cards, with assignees and due dates
- [x] Drag & drop of cards and lists (mouse, touch and keyboard) with optimistic updates
- [x] Real-time collaboration and presence ("who is viewing")
- [ ] Responsive, accessible UI with dark mode
- [ ] Production deployment with a demo account

## Tech stack

| Area     | Technology                                                                      |
| -------- | ------------------------------------------------------------------------------- |
| Backend  | PHP 8.5, Laravel 13, Laravel Fortify, Laravel Socialite (GitHub OAuth)          |
| Frontend | React 19, TypeScript (strict), Inertia.js 3, Tailwind CSS 4, shadcn/ui, dnd-kit |
| Database | PostgreSQL 17                                                                   |
| Realtime | Laravel broadcasting, Pusher Channels, Laravel Echo                             |
| Testing  | Pest 5 (unit, feature, architecture, browser), Vitest, Playwright               |
| Quality  | Larastan (level 8), Pint, Vite+ (Oxlint, Oxfmt), commitlint, Husky              |
| CI/CD    | GitHub Actions, Dependabot                                                      |

## Architecture

TaskFlow is a **Laravel monolith that uses Inertia to render React pages**. There is no separate API to maintain: one codebase, session authentication and a single deployment ([ADR-0001](docs/adr/0001-laravel-inertia-monolith.md)).

```mermaid
flowchart LR
    subgraph Browser
        UI["React pages (Inertia)"]
        Echo["Laravel Echo"]
    end

    subgraph Laravel
        R["Routes"] --> C["Controllers"]
        C --> FR["Form Requests<br/>validation"]
        C --> P["Policies<br/>authorization"]
        C --> A["Actions<br/>business logic"]
        A --> DB[("PostgreSQL")]
        A --> E["Broadcast events"]
    end

    UI -- "Inertia visits (optimistic moves)" --> R
    E -- "publish" --> Pusher[("Pusher Channels")]
    Pusher -- "WebSocket" --> Echo
    Echo --> UI
```

Every request goes through the same layers: **validate** (Form Request), **authorize** (Policy), **execute** (Action) and **notify** (Event). Architecture tests enforce this layering, so it cannot erode over time. See [`CLAUDE.md` §5](CLAUDE.md#5-architecture-rules) and the [architecture decision records](docs/adr/).

## Getting started

### Prerequisites

- PHP 8.3+ and Composer 2 ([Laravel Herd](https://herd.laravel.com) is the easiest option on Windows and macOS)
- Node.js 22+
- Docker (runs PostgreSQL 17)

### Installation

```bash
git clone https://github.com/dailaaaaan/taskflow.git
cd taskflow
cp .env.example .env              # set DB_PORT if 5432 is already taken
docker compose up -d --wait       # PostgreSQL with the app and test databases
composer setup                    # PHP + JS dependencies, app key, migrations, asset build
php artisan db:seed               # demo workspace (login: demo@taskflow.test / password)
npm run prepare                   # Git hooks (npm lifecycle scripts are disabled by .npmrc)
npx playwright install chromium   # browser for the browser test suite
composer run dev                  # http://localhost:8000
```

### Optional: GitHub login

1. Create an OAuth App at <https://github.com/settings/developers> with the callback URL `http://localhost:8000/auth/github/callback`.
2. Set `GITHUB_CLIENT_ID` and `GITHUB_CLIENT_SECRET` in `.env`.

While `GITHUB_CLIENT_ID` is empty, the "Continue with GitHub" button is hidden and its routes return `404`. See [ADR-0005](docs/adr/0005-github-login-account-linking.md) for how accounts are linked.

### Optional: live updates

1. Create a Channels app at <https://dashboard.pusher.com> (the free plan is enough).
2. Set `BROADCAST_CONNECTION=pusher` and the `PUSHER_APP_ID`, `PUSHER_APP_KEY`, `PUSHER_APP_SECRET` and `PUSHER_APP_CLUSTER` values in `.env`.

Without them everything works, except that other people's changes only appear after a reload. See [ADR-0009](docs/adr/0009-realtime-board-events.md).

## Quality and testing

| Gate                          | Tool                      | Command                               |
| ----------------------------- | ------------------------- | ------------------------------------- |
| PHP code style                | Pint                      | `composer lint:check`                 |
| PHP static analysis           | Larastan, level 8         | `composer types:check`                |
| Frontend lint and format      | Vite+ (Oxlint, Oxfmt)     | `npm run check`                       |
| Type checking                 | TypeScript (strict)       | `npm run types:check`                 |
| Tests                         | Pest                      | `php artisan test`                    |
| Frontend unit tests           | Vitest (via Vite+)        | `npm test`                            |
| Browser tests (Chromium, axe) | Pest Browser + Playwright | `vendor/bin/pest --testsuite=Browser` |
| Realtime E2E (two browsers)   | Playwright + local Reverb | `npm run e2e`                         |
| **Everything but the E2E**    |                           | `composer ci:check`                   |

The CI pipeline runs on every push and pull request:

- **Quality**: style, static analysis, lint, type checking, frontend unit tests (Vitest) and a dependency audit (`composer audit`, `npm audit`)
- **Tests**: the full Pest suite on PostgreSQL, including browser tests in headless Chromium, with PCOV coverage, a JUnit report published as a check run, and reports uploaded as artifacts
- **Realtime E2E**: two browsers on the same board, against a local Reverb server
- **Conventional commits**: every commit message in the pull request is validated

The test strategy and the test inventory are documented in [`docs/TESTING.md`](docs/TESTING.md).

## Project documentation

| Document                             | Purpose                                                      |
| ------------------------------------ | ------------------------------------------------------------ |
| [`CLAUDE.md`](CLAUDE.md)             | Engineering conventions and AI collaboration contract        |
| [`CONTRIBUTING.md`](CONTRIBUTING.md) | Branching, commits, pull requests and the Definition of Done |
| [`docs/adr/`](docs/adr/)             | Architecture decision records                                |
| [`docs/TESTING.md`](docs/TESTING.md) | Test strategy and inventory                                  |
| [`SECURITY.md`](SECURITY.md)         | Vulnerability reporting and security measures                |
| [`CHANGELOG.md`](CHANGELOG.md)       | Notable changes per release                                  |

## AI-assisted development

This project is developed with **Claude Code** as a pair programmer. The working agreement is public in [`CLAUDE.md`](CLAUDE.md): the AI writes code inside strict quality gates, while the author owns every decision, reviews every change and performs all version-control operations.

## License

Released under the [MIT License](LICENSE).
