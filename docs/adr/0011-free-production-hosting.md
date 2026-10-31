# 0011. Free production hosting and a public demo

- **Status:** Accepted
- **Date:** 2026-10-31

## Context

The project needs a public address for its portfolio, with no budget. The free plans decide much of the design: Render's web instance has 512 MB and 0.1 CPU, stops after 15 idle minutes, and offers no shell, one-off jobs or outbound SMTP. Visitors must be able to try the product without creating an account, and that means sharing accounts with strangers.

## Options considered

**Web server inside the image**

1. **FrankenPHP.** PHP and its web server in one process, with HTTP compression and static files built in. One binary to run and no process manager.
2. **nginx with PHP-FPM.** The classic setup, but two processes that need a supervisor and more memory.
3. **`php artisan serve`.** Simple, but it is a development server that handles requests one at a time.

**Public demo**

1. **Published email and password.** Anyone could change that password or turn on 2FA and lock everybody else out, so every one of those paths would need its own guard.
2. **One click, with a random password.** Nobody knows the password, so everything that asks for it is closed without extra code: changing it, deleting the account, 2FA and passkeys.

**Sign-up in production**

1. **Open registration, skipping email verification.** Rejected: GitHub logins link by verified email ([ADR-0005](0005-github-login-account-linking.md)), so an unverified account could capture someone else's.
2. **Open registration with an email provider.** Needs another third-party account, and an HTTP API because SMTP is blocked. It also means storing personal data of strangers in a portfolio project.
3. **Registration off, behind `REGISTRATION_ENABLED`.** The demo shows the whole product, and registration stays one setting away.

## Decision

- Render (web), Neon (PostgreSQL) and Pusher, all in the EU. The image uses FrankenPHP and runs as a non-root user.
- Neon's direct connection, because its transaction-mode pooler drops the `SET` commands that Laravel sends when connecting.
- `docker/start.sh` runs the caches, migrations and the demo seeder on every start, since there is nowhere else to run them. Caches are built there and not in the image: they depend on variables that only exist at runtime, and a cached route file built in the image would keep `/register` alive with registration off.
- One-click demo with random passwords. `DemoSeeder` needs no Faker, which is not installed in production, and replaces the workspace on each start. The profile form is closed to these accounts, because a new email has to be verified and would lock everyone else out.
- `trustProxies(at: '*')`: Render's proxy terminates HTTPS and is the only way to reach the app. Without it, the app would build `http://` links and signed invite links would fail.

## Consequences

- Positive: no monthly cost, and damage from visitors disappears with each restart. A smoke test in CI opens the demo inside the real image on every pull request.
- Negative: the first visitor after an idle period waits about a minute. Real users cannot register until an email provider is added.
- Known limits: visitors share the demo, so they can see and undo each other's changes, and someone could delete it for others until the next visit rebuilds it.
