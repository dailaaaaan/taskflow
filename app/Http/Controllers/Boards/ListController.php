<?php

namespace App\Http\Controllers\Boards;

use App\Actions\Lists\CreateList;
use App\Actions\Lists\MoveList;
use App\Http\Controllers\Controller;
use App\Http\Requests\Boards\MoveListRequest;
use App\Http\Requests\Boards\SaveListRequest;
use App\Models\Board;
use App\Models\BoardList;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

/**
 * Lists of a board. `$board` scopes the list binding, so a list of another
 * board resolves to 404.
 */
final class ListController extends Controller
{
    public function store(SaveListRequest $request, Board $board, CreateList $createList): RedirectResponse
    {
        $createList->handle($board, $request->string('title')->toString());

        return back();
    }

    public function update(SaveListRequest $request, Board $board, BoardList $list): RedirectResponse
    {
        $list->update($request->validated());

        return back();
    }

    /**
     * Drag & drop, applied optimistically on the board page like card moves.
     */
    public function move(MoveListRequest $request, Board $board, BoardList $list, MoveList $moveList): RedirectResponse
    {
        $moveList->handle($list, $request->integer('index'));

        return back();
    }

    public function destroy(Board $board, BoardList $list): RedirectResponse
    {
        $list->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('List deleted.')]);

        return back();
    }
}
