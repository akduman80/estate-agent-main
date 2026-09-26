<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            LegalDocumentSeeder::class,
            SettingsSeeder::class,
            LocationSeeder::class,
            ListingSeeder::class,
            ListingLimitSeeder::class,
            PostSeeder::class,
            ProjectSeeder::class,
        ]);
    }
}
