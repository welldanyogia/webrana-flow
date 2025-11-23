<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pastikan baris ini TIDAK dikoemntar
        $this->call([
            RolesSeeder::class,
            PackageSeeder::class,
        ]);
    }
}
