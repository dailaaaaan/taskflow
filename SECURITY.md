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

| Area            | Measure                                                                                                                                                                                                       |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Authentication  | Laravel Fortify: bcrypt hashing, login rate limiting, optional 2FA (TOTP) and passkeys                                                                                                                        |
| OAuth (GitHub)  | Linking by verified email only, password of unverified look-alike accounts discarded, 2FA still enforced, callback rate-limited ([ADR-0005](docs/adr/0005-github-login-account-linking.md))                   |
| Authorization   | Policies on every protected action, enforced by route middleware **before** validation. Scoped bindings return `404` across teams. Feature tests assert `403` for every unauthorized role and for non-members |
| Invitations     | HMAC-signed, expiring, revocable invite links. No secret stored at rest, and the accept endpoint is signed too ([ADR-0006](docs/adr/0006-signed-invite-links.md))                                             |
| Integrity       | The "at least one admin" invariant is enforced in transactions with row locks (`SELECT … FOR UPDATE`) against concurrent demotions                                                                            |
| Input handling  | Form Request validation on every write endpoint. Only validated data reaches the domain layer                                                                                                                 |
| Data exposure   | API Resources whitelist the fields sent to the browser                                                                                                                                                        |
| CSRF / sessions | Laravel CSRF protection and session regeneration on login and logout                                                                                                                                          |
| Realtime        | Private presence channels authorized server-side by team membership                                                                                                                                           |
| Supply chain    | `composer audit` and `npm audit` in CI, Dependabot updates, npm lifecycle scripts disabled (`.npmrc`)                                                                                                         |
| Secrets         | `.env` is never committed. Production secrets live only in the hosting provider's settings                                                                                                                    |
| Code quality    | Larastan level 8, Pest security architecture preset, Eloquent strict mode outside production                                                                                                                  |
