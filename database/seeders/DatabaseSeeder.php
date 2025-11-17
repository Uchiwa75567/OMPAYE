<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Exécuter les seeders
        $this->call([
            AdminUserSeeder::class,
            MarchandSeeder::class,
            ClientSeeder::class,
        ]);
    }
}
