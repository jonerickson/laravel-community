<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'guard_name' => 'web',
        ];
    }
}
