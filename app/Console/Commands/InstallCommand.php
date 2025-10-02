<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Group;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\GroupSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class InstallCommand extends Command
{
    protected $signature = 'mi:install
                            {--name= : The super admin\'s name}
                            {--email= : The super admin\'s email}
                            {--password= : The super admin\'s password}';

    protected $description = 'Install and configure the application for use.';

    public function handle(): void
    {
        if ($this->confirm('Would you like to install all the required permissions?', true)) {
            Schema::disableForeignKeyConstraints();
            Permission::truncate();
            Role::truncate();
            Schema::enableForeignKeyConstraints();

            $this->comment('Installing permissions...');
            $this->call('db:seed', ['--class' => PermissionSeeder::class]);
        }

        if ($this->confirm('Would you like to install all the required permissions?', true)) {
            Schema::disableForeignKeyConstraints();
            Group::truncate();
            Schema::enableForeignKeyConstraints();

            $this->comment('Installing groups...');
            $this->call('db:seed', ['--class' => GroupSeeder::class]);
        }

        if ($this->confirm('Would you like to create a new super admin account?', true)) {
            $name = $this->ask('Name', $this->option('name'));
            $email = $this->ask('Email', $this->option('email'));
            $password = $this->ask('Password', $this->option('password'));

            if (blank($name) || blank($email) || blank($password)) {
                $this->error('Please provide a name, email and password when creating a new account.');

                return;
            }

            $user = User::updateOrCreate([
                'email' => $email,
            ], [
                'name' => $name,
                'password' => Hash::make($password),
            ]);

            $user->assignRole('super-admin');

            $this->comment('User created successfully.');
        }

        $this->comment('Application installed successfully.');
    }
}
