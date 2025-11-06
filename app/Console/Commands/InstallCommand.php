<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role as RoleEnum;
use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\GroupSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'app:install
                            {--name= : The super admin\'s name}
                            {--email= : The super admin\'s email}
                            {--password= : The super admin\'s password}
                            {--seed : Seed some demo data}';

    protected $description = 'Install and configure the application for use.';

    public function handle(): int
    {
        if (! $this->confirmToProceed()) {
            return self::SUCCESS;
        }

        if ($this->confirm('Would you like to install all the required permissions?', true)) {
            Schema::disableForeignKeyConstraints();
            Permission::truncate();
            Role::truncate();
            Schema::enableForeignKeyConstraints();

            $this->comment('Installing permissions...');
            $this->call('db:seed', ['--class' => PermissionSeeder::class]);
        }

        if ($this->confirm('Would you like to install all the default member groups?', true)) {
            Schema::disableForeignKeyConstraints();
            Group::truncate();
            Schema::enableForeignKeyConstraints();

            $this->comment('Installing groups...');
            $this->call('db:seed', ['--class' => GroupSeeder::class]);
        }

        if ($this->confirm('Would you like to create a new super admin account?', true)) {
            $name = $this->option('name') ?? $this->ask('Name');
            $email = $this->option('email') ?? $this->ask('Email');
            $password = $this->option('password') ?? $this->secret('Password');

            if (blank($name) || blank($email) || blank($password)) {
                $this->error('Please provide a name, email and password when creating a new account.');

                return self::FAILURE;
            }

            if (Role::count() === 0 || Permission::count() === 0) {
                $this->comment('Installing permissions...');
                $this->call('db:seed', ['--class' => PermissionSeeder::class]);
            }

            $user = User::updateOrCreate([
                'email' => $email,
            ], [
                'name' => $name,
                'password' => Hash::make($password),
            ]);

            $user->markEmailAsVerified();
            $user->assignRole(RoleEnum::Administrator);

            $this->comment('User created successfully.');
        }

        if ($this->option('seed')) {
            $this->call('db:seed', [
                '--class' => DemoSeeder::class,
            ]);
        }

        $this->comment('Application installed successfully.');

        return self::SUCCESS;
    }
}
