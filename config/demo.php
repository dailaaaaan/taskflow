<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public demo
    |--------------------------------------------------------------------------
    |
    | Lets visitors enter a shared workspace with one click. These accounts
    | are public, so they have a random password in production and everything
    | that asks for the current password stays out of reach.
    |
    */

    'enabled' => (bool) env('DEMO_ENABLED', false),

    'accounts' => [
        'demo' => 'demo@taskflow.test',
        'sam' => 'sam@taskflow.test',
    ],

];
