<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config(['app.debug' => false]);
});

it('renders the error page', function (int $status) {
    Route::middleware('web')->get('/failing', fn () => abort($status));

    $this->get('/failing')
        ->assertStatus($status)
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', $status));
})->with([403, 404, 429, 500, 503]);

it('shows the not found page for an unknown URL', function () {
    $this->get('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 404)
            ->where('signedIn', false)
            // The web middleware never ran here. Sharing its data from the
            // error page would query the user, and the database may be down.
            ->missing('auth'));
});

it('shows the forbidden page to people outside the team of a board', function () {
    [$board] = boardWithAdminAndMember();

    $this->actingAs(User::factory()->create())
        ->get(route('boards.show', $board))
        ->assertForbidden()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 403)
            ->where('signedIn', true));
});

it('shows the server error page for an unexpected exception', function () {
    Route::middleware('web')->get('/failing', function () {
        throw new RuntimeException('The database is down.');
    });

    $this->get('/failing')
        ->assertInternalServerError()
        ->assertInertia(fn (Assert $page) => $page
            ->component('errors/show')
            ->where('status', 500));
});

it('keeps the debug screen of server errors while developing', function () {
    config(['app.debug' => true]);
    Route::middleware('web')->get('/failing', function () {
        throw new RuntimeException('The database is down.');
    });

    $this->get('/failing')
        ->assertInternalServerError()
        ->assertSee('The database is down.');
});

it('leaves failed writes to the page that made them', function () {
    [$board, , $member] = boardWithAdminAndMember();

    $this->actingAs($member)
        ->withHeader('X-Inertia', 'true')
        ->patch(route('boards.cards.move', [$board, 999999]), ['list_id' => 1, 'index' => 0])
        ->assertNotFound()
        ->assertHeaderMissing('X-Inertia');
});

it('answers JSON clients with JSON', function () {
    $this->getJson('/this-page-does-not-exist')
        ->assertNotFound()
        ->assertJsonStructure(['message']);
});
