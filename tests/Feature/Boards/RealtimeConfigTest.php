<?php

use Inertia\Testing\AssertableInertia as Assert;

it('keeps live updates off unless broadcasting goes through Pusher', function () {
    [$board, , $member] = boardWithAdminAndMember();

    $this->actingAs($member)
        ->get(route('boards.show', $board))
        ->assertInertia(fn (Assert $page) => $page->where('realtime', null));
});

it('keeps live updates off while the Pusher key is empty', function () {
    config(['broadcasting.default' => 'pusher', 'broadcasting.connections.pusher.key' => '']);
    [$board, , $member] = boardWithAdminAndMember();

    $this->actingAs($member)
        ->get(route('boards.show', $board))
        ->assertInertia(fn (Assert $page) => $page->where('realtime', null));
});

it('gives the browser the public settings of Pusher, never the secret', function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'public-key',
        'broadcasting.connections.pusher.secret' => 'do-not-leak',
        'broadcasting.connections.pusher.options.cluster' => 'eu',
        'broadcasting.connections.pusher.options.host' => 'api-eu.pusher.com',
    ]);
    [$board, , $member] = boardWithAdminAndMember();

    $response = $this->actingAs($member)->get(route('boards.show', $board));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('realtime.key', 'public-key')
        ->where('realtime.cluster', 'eu')
        // Empty: pusher-js builds the socket host from the cluster.
        ->where('realtime.host', null)
        ->missing('realtime.secret'));

    expect($response->getContent())->not->toContain('do-not-leak');
});

it('points the browser at a Pusher-compatible server when one is configured', function () {
    config([
        'broadcasting.default' => 'pusher',
        'broadcasting.connections.pusher.key' => 'local-key',
        'broadcasting.connections.pusher.options.host' => '127.0.0.1',
        'broadcasting.connections.pusher.options.port' => '8095',
        'broadcasting.connections.pusher.options.scheme' => 'http',
    ]);
    [$board, , $member] = boardWithAdminAndMember();

    $this->actingAs($member)
        ->get(route('boards.show', $board))
        ->assertInertia(fn (Assert $page) => $page
            ->where('realtime.host', '127.0.0.1')
            ->where('realtime.port', 8095)
            ->where('realtime.scheme', 'http'));
});
