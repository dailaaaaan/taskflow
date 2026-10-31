<?php

namespace App\Actions\Demo;

use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Support\Facades\Artisan;

final class PrepareDemoAccount
{
    /**
     * Visitors share the workspace and may delete all of it. Starting again
     * here means the next one never lands on an empty account.
     */
    public function handle(string $email): User
    {
        $user = User::query()->firstWhere('email', $email);

        if ($user?->teams()->exists()) {
            return $user;
        }

        Artisan::call('db:seed', ['--class' => DemoSeeder::class, '--force' => true]);

        return User::query()->where('email', $email)->sole();
    }
}
