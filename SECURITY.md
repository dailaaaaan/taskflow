# Security Policy

## Supported versions

Only the latest commit on `main` and the most recent release receive security fixes.

## Reporting a vulnerability

**Please do not open a public issue for security problems.**

Report vulnerabilities privately through GitHub: go to the repository's **Security** tab and click **Report a vulnerability**.

Please include:

- A description of the issue and its impact
- Steps to reproduce, or a proof of concept
- The affected version or commit

You can expect an acknowledgement within **72 hours** and a status update within **7 days**.

## Security measures in this project

| Area            | Measure                                                                                                        |
| --------------- | -------------------------------------------------------------------------------------------------------------- |
| Authentication  | Laravel Fortify: bcrypt hashing, login rate limiting, optional 2FA (TOTP) and passkeys                         |
| Authorization   | Policies on every protected action. Feature tests assert `403` for every unauthorized role and for non-members |
| Input handling  | Form Request validation on every write endpoint. Only validated data reaches the domain layer                  |
| Data exposure   | API Resources whitelist the fields sent to the browser                                                         |
| CSRF / sessions | Laravel CSRF protection and session regeneration on login and logout                                           |
| Realtime        | Private presence channels authorized server-side by team membership                                            |
| Supply chain    | `composer audit` and `npm audit` in CI, Dependabot updates, npm lifecycle scripts disabled (`.npmrc`)          |
| Secrets         | `.env` is never committed. Production secrets live only in the hosting provider's settings                     |
| Code quality    | Larastan level 8, Pest security architecture preset, Eloquent strict mode outside production                   |
