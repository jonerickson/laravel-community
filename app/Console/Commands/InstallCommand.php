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

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\table;
use function Laravel\Prompts\text;

class InstallCommand extends Command
{
    use ConfirmableTrait;

    protected $signature = 'app:install
                            {--name= : The super admin\'s name}
                            {--email= : The super admin\'s email}
                            {--password= : The super admin\'s password}
                            {--seed : Seed some demo data}
                            {--force : Force the operation to run when in production}';

    protected $description = 'Install and configure the application for use.';

    public function handle(): int
    {
        /** @phpstan-ignore-next-line larastan.noEnvCallsOutsideOfConfig  */
        if (env('DEVCONTAINER_SETUP')) {
            $this->input->setInteractive(false);
        }

        if (! $this->input->isInteractive()) {
            $this->components->info('Running in non-interactive mode.');
        }

        if ($this->isInstalled()) {
            if (! $this->option('force')) {
                $this->components->info('Application is already installed.');
                $this->components->info('Use --force to reinstall.');

                return self::SUCCESS;
            }

            if (! $this->confirmToProceed('Application is already installed. This will RESET all data!')) {
                return self::SUCCESS;
            }

            $this->components->warn('Resetting application...');
            $this->resetApplication();
        }

        $this->components->info('Installing application...');

        $this->call('migrate', [
            '--force' => true,
        ]);

        if (! $this->input->isInteractive() || confirm('Would you like to install all the required permissions? (Recommended)')) {
            Schema::disableForeignKeyConstraints();
            Permission::truncate();
            Role::truncate();
            Schema::enableForeignKeyConstraints();

            $this->components->info('Installing permissions...');
            $this->call('db:seed', [
                '--class' => PermissionSeeder::class,
                '--force' => true,
            ]);
        }

        if (! $this->input->isInteractive() || confirm('Would you like to install all the default member groups? (Recommended)')) {
            Schema::disableForeignKeyConstraints();
            Group::truncate();
            Schema::enableForeignKeyConstraints();

            $this->components->info('Installing groups...');
            $this->call('db:seed', [
                '--class' => GroupSeeder::class,
                '--force' => true,
            ]);
        }

        $shouldCreateUser = false;
        $name = null;
        $email = null;
        $password = null;

        if ($this->input->isInteractive()) {
            if (confirm('Would you like to create a new super admin account?')) {
                $shouldCreateUser = true;
                $name = $this->option('name') ?? text('Name', 'What is the name?');
                $email = $this->option('email') ?? text('Email', 'What is the email?');
                $password = $this->option('password') ?? password('Password', 'What is the password?');
            }
        } else {
            $name = $this->option('name');
            $email = $this->option('email');
            $password = $this->option('password');

            if (filled($name) && filled($email) && filled($password)) {
                $shouldCreateUser = true;
            }
        }

        if ($shouldCreateUser) {
            if (blank($name) || blank($email) || blank($password)) {
                $this->components->error('Please provide a name, email and password when creating a new account.');

                return self::FAILURE;
            }

            if (Role::count() === 0 || Permission::count() === 0) {
                $this->components->info('Installing permissions...');
                $this->call('db:seed', [
                    '--class' => PermissionSeeder::class,
                    '--force' => true,
                ]);
            }

            $user = User::updateOrCreate([
                'email' => $email,
            ], [
                'name' => $name,
                'password' => Hash::make($password),
            ]);

            $user->markEmailAsVerified();
            $user->assignRole(RoleEnum::Administrator);

            $this->components->success('User created successfully.');
        }

        if ($this->option('seed')) {
            $this->call('db:seed', [
                '--class' => DemoSeeder::class,
                '--force' => true,
            ]);
        }

        if (count($users = User::all()) > 0) {
            $this->components->info('Available user accounts:');

            table(['ID', 'Name', 'Email', 'Roles', 'Password'], $users->map(fn (User $user): array => [$user->id, $user->name, $user->email, $user->roles->map->name->implode(', '), '---']));
        }

        $this->components->success('Application installed successfully.');
        $this->components->info('You may visit the application at: '.route('home'));

        return self::SUCCESS;
    }

    protected function isInstalled(): bool
    {
        if (Schema::hasTable('users') && User::exists()) {
            return true;
        }

        return Schema::hasTable('groups') && Group::exists();
    }

    protected function resetApplication(): void
    {
        $this->components->info('Truncating all data...');

        $this->call('migrate:fresh', [
            '--force' => true,
        ]);

        $this->components->success('Application reset complete.');
    }
}
