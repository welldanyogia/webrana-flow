<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package; // Pastikan Model Package diimport

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Hobby',
                'slug' => 'hobby',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'max_applications' => 1,
                'max_databases' => 1,
                'cpu_limit' => '0.5',
                'ram_limit' => '256M',
                'is_shared_db' => true,
                'allow_custom_domain' => false,
                'allow_high_availability' => false,
                'is_featured' => false,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'price_monthly' => 69000,
                'price_yearly' => 690000,
                'max_applications' => 3,
                'max_databases' => 3,
                'cpu_limit' => '1',
                'ram_limit' => '512M',
                'is_shared_db' => true,
                'allow_custom_domain' => true,
                'allow_high_availability' => false,
                'is_featured' => true,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'price_monthly' => 199000,
                'price_yearly' => 1990000,
                'max_applications' => 10,
                'max_databases' => 10,
                'cpu_limit' => '2',
                'ram_limit' => '1G',
                'is_shared_db' => false, // Dedicated DB
                'allow_custom_domain' => true,
                'allow_high_availability' => true,
                'is_featured' => false,
            ]
        ];

        foreach ($packages as $package) {
            Package::updateOrCreate(
                ['slug' => $package['slug']], // Kunci pencarian
                $package // Data yang diupdate/create
            );
        }
    }
}
