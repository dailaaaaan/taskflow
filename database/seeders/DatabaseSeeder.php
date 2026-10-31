<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed a ready-to-explore workspace: a demo account that administers a
     * team with one teammate and two boards full of lists and cards.
     */
    public function run(): void
    {
        $this->call(DemoSeeder::class);
    }
}
