<?php

use App\Enums\TeamRole;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/** The SQL that a request runs. */
function queriesOf(callable $request): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $request();
    DB::disableQueryLog();

    return array_column(DB::getQueryLog(), 'query');
}

function fillBoard(Board $board, User $assignee, int $lists, int $cards): void
{
    BoardList::factory()->count($lists)->for($board)->create()->each(
        fn (BoardList $list) => Card::factory()->count($cards)->for($list, 'boardList')->create(['assignee_id' => $assignee->id]),
    );
}

/** The partial reload that follows every drag & drop move. */
function reloadLists(Board $board): void
{
    test()->withHeaders([
        'X-Inertia' => 'true',
        'X-Inertia-Version' => (string) app(HandleInertiaRequests::class)->version(request()),
        'X-Inertia-Partial-Component' => 'boards/show',
        'X-Inertia-Partial-Data' => 'lists',
    ])->get(route('boards.show', $board))->assertOk();
}

it('runs the same queries however big the board is', function () {
    [$small, , $member] = boardWithAdminAndMember();
    fillBoard($small, $member, 1, 1);

    $big = Board::factory()->for($small->team)->create();
    fillBoard($big, $member, 4, 6);

    $this->actingAs($member);

    expect(queriesOf(fn () => $this->get(route('boards.show', $big))))
        ->toHaveCount(count(queriesOf(fn () => $this->get(route('boards.show', $small)))));
});

it('only loads the lists on a partial reload', function () {
    [$board, , $member] = boardWithAdminAndMember();
    fillBoard($board, $member, 2, 2);

    $this->actingAs($member);

    // Board, team, the membership that authorizes the route, lists, cards and assignees.
    expect(queriesOf(fn () => reloadLists($board)))->toHaveCount(6);
});

it('asks for the role of the user once per request', function (string $routeName) {
    [$board, , $member] = boardWithAdminAndMember();

    $this->actingAs($member);

    $parameter = $routeName === 'boards.show' ? $board : $board->team;

    $roleLookups = array_filter(
        queriesOf(fn () => $this->get(route($routeName, $parameter))),
        fn (string $query): bool => str_contains($query, 'from "memberships"') && str_contains($query, '"user_id" = ?'),
    );

    expect($roleLookups)->toHaveCount(1);
})->with(['boards.show', 'teams.show', 'teams.edit']);

it('never mixes up the roles of two users of the same team', function () {
    [$board, $admin, $member] = boardWithAdminAndMember();
    $outsider = User::factory()->create();
    $team = Team::query()->findOrFail($board->team_id);

    expect($team->roleOf($admin)->value)->toBe('admin')
        ->and($team->roleOf($member)->value)->toBe('member')
        ->and($team->roleOf($outsider))->toBeNull()
        ->and($team->roleOf($admin)->value)->toBe('admin');
});

it('forgets a role as soon as the membership changes', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();

    expect($team->roleOf($user))->toBeNull();

    $team->members()->attach($user, ['role' => TeamRole::Member]);

    expect($team->roleOf($user))->toBe(TeamRole::Member);

    $membership = $team->memberships()->firstWhere('user_id', $user->id);
    $membership?->update(['role' => TeamRole::Admin]);

    expect($team->roleOf($user))->toBe(TeamRole::Admin);

    $membership?->delete();

    expect($team->roleOf($user))->toBeNull();
});
