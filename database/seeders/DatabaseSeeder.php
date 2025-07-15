<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ShieldSeeder::class,
        ]);

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@deschutesdesigngroup.com',
        ])->assignRole(Utils::getSuperAdminName());

        $category = ProductCategory::factory()->create();

        Product::factory()
            ->count(5)
            ->recycle($category)
            ->hasAttached($category, relationship: 'categories')
            ->create();
    }
}
