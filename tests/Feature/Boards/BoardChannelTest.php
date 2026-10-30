<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
| The auth endpoint signs locally with the app secret, so this exercises the
| real Pusher broadcaster without any network. Channels are registered on the
| driver that was active at boot, so they are registered again on this one.
*/
beforeEach(function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'test-key',
        'broadcasting.connections.pusher.secret' => 'test-secret',
        'broadcasting.connections.pusher.app_id' => 'test-app',
    ]);

    Broadcast::forgetDrivers();

    require base_path('routes/channels.php');
});

function joinBoardChannel(Board $board): array
{
    return [
        'channel_name' => "presence-board.{$board->id}",
        'socket_id' => '1234.5678',
    ];
}

it('lets members join the presence channel of their board', function (string $role) {
    [$board, $admin, $member] = boardWithAdminAndMember();
    // Reloaded like a session user, with every column.
    $user = ($role === 'admin' ? $admin : $member)->fresh();

    $response = $this->actingAs($user)
        ->post('/broadcasting/auth', joinBoardChannel($board))
        ->assertOk();

    expect($response->json('auth'))->toStartWith('test-key:')
        ->and(json_decode($response->json('channel_data'), true))->toBe([
            'user_id' => (string) $user->id,
            'user_info' => ['id' => $user->id, 'name' => $user->name, 'avatar' => $user->avatar],
        ]);
})->with(['admin', 'member']);

it('forbids non-members', function () {
    [$board] = boardWithAdminAndMember();

    $this->actingAs(User::factory()->create())
        ->post('/broadcasting/auth', joinBoardChannel($board))
        ->assertForbidden();
});

it('forbids members of another team', function () {
    [$board] = boardWithAdminAndMember();
    [, , $foreignMember] = boardWithAdminAndMember();

    $this->actingAs($foreignMember)
        ->post('/broadcasting/auth', joinBoardChannel($board))
        ->assertForbidden();
});

it('forbids guests', function () {
    [$board] = boardWithAdminAndMember();

    $this->post('/broadcasting/auth', joinBoardChannel($board))->assertForbidden();
});
