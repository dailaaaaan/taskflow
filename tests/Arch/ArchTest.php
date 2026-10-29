<?php

/*
|--------------------------------------------------------------------------
| Architecture rules
|--------------------------------------------------------------------------
|
| These tests enforce the layering described in CLAUDE.md and docs/adr.
| They fail the build when code lands in the wrong layer, so the
| architecture cannot silently erode over time.
|
*/

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

arch('php preset')->preset()->php();

arch('security preset')->preset()->security();

arch('no debugging leftovers')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();

arch('models extend Eloquent')
    ->expect('App\Models')
    ->toExtend(Model::class);

arch('form requests extend FormRequest')
    ->expect('App\Http\Requests')
    ->toExtend(FormRequest::class);

arch('controllers do not talk to the database directly')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('actions are independent of the HTTP layer')
    ->expect('App\Actions')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Support\Facades\Request',
        'App\Http',
    ]);

arch('policies are suffixed with Policy')
    ->expect('App\Policies')
    ->classes()
    ->toHaveSuffix('Policy');

arch('enums live in App\Enums')
    ->expect('App\Enums')
    ->toBeEnums();
