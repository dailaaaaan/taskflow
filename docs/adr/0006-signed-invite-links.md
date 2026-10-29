# 0006. Invite members with signed, reusable invite links

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

Admins need a way to bring people into a team. The app does not send email yet (no mail provider on the free hosting tier), and invitations must not become an attack surface: they must not be guessable, they must expire, and they must be revocable.

## Options considered

1. **Email invitations with a stored random token.** The standard approach, but it needs a mail provider, and the token is a secret that would have to be hashed at rest.
2. **Add members by email address directly.** No acceptance step and no consent from the invited user, and it reveals which emails have accounts.
3. **Reusable invite links signed with Laravel's URL signatures.** An `invitations` row holds team, role, creator and expiry. The link is `URL::temporarySignedRoute()` with an HMAC (keyed by `APP_KEY`) over the path and the expiry.

## Decision

Option 3.

- The `signed:relative` middleware rejects tampered or expired links with `403`. Deleting the row revokes the link, which then resolves to `404`.
- The signature covers the path only (`absolute: false`), so links keep working behind proxies or alternative hosts.
- The accept endpoint (`POST`) is signed as well. Its signed URL is generated server-side for the preview page, so knowing an invitation id is never enough.
- Guests are sent to log in and then returned to the link (`url.intended`).
- Accepting is idempotent, and an invite link never changes the role of an existing member, so it cannot demote an admin.
- Nothing secret is stored in the database: a leaked dump cannot be used to forge links.

## Consequences

- Positive: no mail infrastructure, no stored secrets, and expiry and revocation for free. Every branch is covered by feature and browser tests.
- Negative: a link is a bearer credential until it expires, and anyone who obtains it can join. This is mitigated by the 7-day lifetime, one-click revocation, and admins seeing every active link.
- Follow-up: per-email invitations can be added later on the same table once a mail provider is configured.
