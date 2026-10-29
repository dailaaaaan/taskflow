# 0005. Link GitHub logins by verified email and never bypass 2FA

- **Status:** Accepted
- **Date:** 2026-10-29

## Context

TaskFlow offers "Continue with GitHub" next to email/password, passkeys and optional two-factor authentication (Laravel Fortify). Adding a second way to sign in raises three questions: how a GitHub identity maps to a local account, whether a GitHub login may skip the user's second factor, and how to avoid account takeover through email collisions.

## Options considered

1. **Separate accounts per provider.** Simple, but one person ends up with two disconnected workspaces.
2. **Link by email, trusting any email.** Convenient, but an attacker who controls an unverified address could hijack accounts.
3. **Link by GitHub id first, then by GitHub's verified email, and harden the edge cases.**

## Decision

Option 3, implemented in `App\Actions\Auth\AuthenticateWithGitHub`:

1. An account already linked to the GitHub id signs in, even if the email changed on either side.
2. Otherwise, an account with the same email (case-insensitive) is linked. Socialite only returns GitHub's primary **verified** email.
3. Otherwise, a new account is created, verified, with no local password.

Hardening:

- **Pre-account takeover:** if the matching local account was never verified, its password is discarded on linking. Whoever registered that address without proving ownership loses access, and the verified GitHub owner keeps it.
- **2FA is never bypassed:** users with two-factor authentication enabled are handed to Fortify's challenge (`login.id` in the session, `two-factor.login` route), exactly like a password login.
- **Optional feature:** routes return `404` and the button is hidden unless `GITHUB_CLIENT_ID` is configured.
- **Failure handling:** cancelled authorizations, expired OAuth state and GitHub API errors redirect back to the login page with a message instead of a 500.

## Consequences

- Positive: one account per person across sign-in methods, no takeover through unverified emails, consistent 2FA, and every branch covered by feature tests.
- Negative: users created through GitHub have no password until they use "Forgot password". Password-confirmation screens (deleting the account, for example) require setting one first.
