# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
