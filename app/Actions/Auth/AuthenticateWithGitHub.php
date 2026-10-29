<?php

namespace App\Actions\Auth;

use App\Exceptions\GitHubEmailUnavailable;
use App\Models\User;
use Laravel\Socialite\Contracts\User as GitHubUser;

/**
 * Resolves the local account for a GitHub identity (see ADR-0005):
 *
 *  1. an account already linked to the GitHub id;
 *  2. otherwise an account with the same email, which gets linked;
 *  3. otherwise a new account.
 *
 * Socialite only returns GitHub's primary *verified* email, so the address is
 * trusted and the account is marked as verified.
 */
final class AuthenticateWithGitHub
{
    public function handle(GitHubUser $githubUser): User
    {
        $githubId = (string) $githubUser->getId();

        $user = User::query()->where('github_id', $githubId)->first()
            ?? $this->findOrNewByEmail($githubUser);

        // An unverified account with this email may have been registered by
        // someone else (pre-account takeover). Its password is discarded so
        // only the verified owner keeps access.
        if ($user->exists && $user->email_verified_at === null) {
            $user->password = null;
        }

        $user->forceFill([
            'github_id' => $githubId,
            'avatar' => $githubUser->getAvatar(),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        return $user;
    }

    private function findOrNewByEmail(GitHubUser $githubUser): User
    {
        $email = $githubUser->getEmail();

        if (blank($email)) {
            throw new GitHubEmailUnavailable;
        }

        return User::query()->whereRaw('lower(email) = ?', [mb_strtolower($email)])->first()
            ?? new User([
                'name' => $githubUser->getName() ?: $githubUser->getNickname() ?: 'GitHub user',
                'email' => $email,
            ]);
    }
}
