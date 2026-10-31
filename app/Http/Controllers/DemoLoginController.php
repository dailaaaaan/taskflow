<?php

namespace App\Http\Controllers;

use App\Actions\Demo\PrepareDemoAccount;
use App\Models\Board;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * One-click entry to the public demo, so a visitor can try the product without
 * creating an account. Registered always and checked here: `route:cache` would
 * otherwise freeze the setting.
 */
final class DemoLoginController extends Controller
{
    public function __invoke(Request $request, PrepareDemoAccount $prepareDemoAccount, string $account): RedirectResponse
    {
        abort_unless(config()->boolean('demo.enabled'), 404);

        $user = $prepareDemoAccount->handle(config()->string("demo.accounts.{$account}"));

        Auth::login($user);
        $request->session()->regenerate();

        $board = Board::query()->whereBelongsTo($user->teams()->firstOrFail())->orderBy('id')->first();

        return $board ? to_route('boards.show', $board) : to_route('dashboard');
    }
}
