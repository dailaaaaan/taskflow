<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\AuthenticateWithGitHub;
use App\Exceptions\GitHubEmailUnavailable;
use App\Http\Controllers\Controller;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Events\TwoFactorAuthenticationChallenged;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

final class GitHubController extends Controller
{
    /**
     * Send the user to GitHub to authorize TaskFlow.
     */
    public function redirect(): SymfonyRedirectResponse
    {
        $this->ensureEnabled();

        return Socialite::driver('github')->redirect();
    }

    /**
     * Handle the user returning from GitHub.
     */
    public function callback(Request $request, AuthenticateWithGitHub $authenticate): RedirectResponse
    {
        $this->ensureEnabled();

        if ($request->filled('error')) {
            return $this->failed(__('GitHub sign-in was cancelled.'));
        }

        try {
            $user = $authenticate->handle(Socialite::driver('github')->user());
        } catch (InvalidStateException) {
            return $this->failed(__('Your GitHub sign-in expired. Please try again.'));
        } catch (ClientException) {
            return $this->failed(__('GitHub sign-in failed. Please try again.'));
        } catch (GitHubEmailUnavailable) {
            return $this->failed(__('Your GitHub account has no verified email address.'));
        }

        if ($user->hasEnabledTwoFactorAuthentication()) {
            // Same hand-off as Fortify's password login: signing in with
            // GitHub never bypasses the user's second factor.
            $request->session()->put([
                'login.id' => $user->getKey(),
                'login.remember' => true,
            ]);

            TwoFactorAuthenticationChallenged::dispatch($user);

            return to_route('two-factor.login');
        }

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect()->intended(config()->string('fortify.home'));
    }

    private function ensureEnabled(): void
    {
        abort_unless(config()->boolean('services.github.enabled'), 404);
    }

    private function failed(string $message): RedirectResponse
    {
        return to_route('login')->withErrors(['email' => $message]);
    }
}
