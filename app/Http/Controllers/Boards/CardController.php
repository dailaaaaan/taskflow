<?php

namespace App\Http\Controllers\Boards;

use App\Actions\Cards\CreateCard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Boards\StoreCardRequest;
use App\Http\Requests\Boards\UpdateCardRequest;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Cards of a board. `$board` scopes the list and card bindings, so a list or
 * card of another board resolves to 404.
 */
final class CardController extends Controller
{
    public function store(StoreCardRequest $request, Board $board, BoardList $list, CreateCard $createCard, #[CurrentUser] User $user): RedirectResponse
    {
        $createCard->handle($list, $user, $request->string('title')->toString());

        return back();
    }

    public function update(UpdateCardRequest $request, Board $board, Card $card): RedirectResponse
    {
        $card->update($request->validated());

        return back();
    }

    public function destroy(Board $board, Card $card): RedirectResponse
    {
        $card->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Card deleted.')]);

        return back();
    }
}
