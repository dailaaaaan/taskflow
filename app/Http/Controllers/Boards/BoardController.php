<?php

namespace App\Http\Controllers\Boards;

use App\Actions\Boards\CreateBoard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Boards\SaveBoardRequest;
use App\Http\Resources\BoardListResource;
use App\Http\Resources\BoardResource;
use App\Http\Resources\MemberResource;
use App\Http\Resources\TeamResource;
use App\Models\Board;
use App\Models\Team;
use App\Models\User;
use App\Support\RealtimeConfig;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Authorization is declared on the routes (->can()), so it runs before
 * request validation and these methods only coordinate.
 */
final class BoardController extends Controller
{
    public function store(SaveBoardRequest $request, Team $team, CreateBoard $createBoard, #[CurrentUser] User $user): RedirectResponse
    {
        $board = $createBoard->handle($team, $user, $request->string('title')->toString(), $request->color());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board created.')]);

        return to_route('boards.show', $board);
    }

    public function show(Board $board, #[CurrentUser] User $user): Response
    {
        // Closures are only evaluated when the prop is requested, so the partial
        // reload after a drag & drop move (`only: ['lists']`) skips the rest.
        return Inertia::render('boards/show', [
            'team' => TeamResource::make($board->team),
            'board' => BoardResource::make($board),
            // One query per level instead of one per list and per card.
            'lists' => fn () => BoardListResource::collection($board->lists()->with('cards.assignee')->get()),
            // Possible assignees for the card modal.
            'members' => fn () => MemberResource::collection($board->team->memberships()->with('user')->adminsFirst()->get()),
            'realtime' => RealtimeConfig::forBrowser(),
            'can' => [
                'update' => $user->can('update', $board),
                'delete' => $user->can('delete', $board),
                'collaborate' => $user->can('collaborate', $board),
            ],
        ]);
    }

    public function update(SaveBoardRequest $request, Board $board): RedirectResponse
    {
        $board->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board updated.')]);

        return back();
    }

    public function destroy(Board $board): RedirectResponse
    {
        $board->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Board deleted.')]);

        return to_route('teams.show', $board->team_id);
    }
}
