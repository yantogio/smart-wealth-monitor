<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Seeds the fixed asset catalog, then the committed demo price history so a
     * fresh install has a fully populated dashboard without any API key.
     */
    public function run(): void
    {
        $this->call([
            AssetSeeder::class,
            DemoPriceSeeder::class,
        ]);
    }
}
