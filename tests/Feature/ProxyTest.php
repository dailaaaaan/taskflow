<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;

/*
| Render terminates HTTPS and forwards plain HTTP. If the app ignored the
| proxy it would build http:// links, and signed invite links would fail.
*/
beforeEach(function () {
    $this->withHeaders(['X-Forwarded-Proto' => 'https', 'X-Forwarded-Host' => 'taskflow.example.com']);
});

it('knows that the visitor is on HTTPS behind the proxy', function () {
    Route::middleware('web')->get('/behind-the-proxy', fn (Request $request) => [
        'secure' => $request->secure(),
        'url' => URL::to('/somewhere'),
    ]);

    $this->get('/behind-the-proxy')
        ->assertJson(['secure' => true, 'url' => 'https://taskflow.example.com/somewhere']);
});

it('accepts a signed link made behind the proxy', function () {
    Route::middleware(['web', 'signed'])->get('/signed', fn () => 'opened')->name('signed.link');
    Route::middleware('web')->get('/make-a-link', fn () => URL::signedRoute('signed.link'));
    Route::getRoutes()->refreshNameLookups();

    $link = (string) $this->get('/make-a-link')->getContent();

    expect($link)->toStartWith('https://taskflow.example.com/signed?signature=');

    $this->get($link)->assertOk()->assertSee('opened');
});
