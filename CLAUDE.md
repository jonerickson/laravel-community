# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a modern Laravel + React marketplace application. It features:
- Laravel 12 backend with Inertia.js for SPA functionality
- React 19 frontend with TypeScript
- Filament panels (Admin and Marketplace)
- Modular payment processing (default: Stripe via Laravel Cashier)
- Modular support ticket system (default: database, external services supported)
- Role-based permissions with Spatie/Laravel-permission
- Social authentication system (Discord, Roblox - extensible for other providers)
- E-commerce store with products and categories
- User marketplace for third-party sellers
- Blog system with posts and categories
- Forum platform with topics and discussions
- Policy management system
- API Platform integration

## Development Commands

### Backend (PHP/Laravel)
- `composer dev` - Run development environment with Horizon queue worker, logging, and frontend
- `composer setup` - Complete first-time setup (install, env, key, migrate, npm, build)
- `composer test` - Run all tests with Pest
- `composer test-coverage` or `composer tc` - Run tests with coverage
- `composer test-filter <pattern>` or `composer tf` - Run specific tests by pattern
- `composer analyze` or `composer tt` - Run PHPStan static analysis
- `composer cs-fix` or `composer lint` - Fix code style with Laravel Pint
- `composer ide` - Generate IDE helper files for better autocomplete
- `composer facades` - Generate facade documentation for custom facades
- `composer types` - Generate TypeScript definitions from Laravel models
- `composer reset` - Full environment reset with fresh migrations and seeding
- `composer rector` - Run automated refactoring with Rector

### Frontend (Node.js/React)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint and fix issues
- `npm run format` - Format code with Prettier
- `npm run format:check` - Check code formatting without making changes
- `npm run types` - Type check with TypeScript

### Testing
- `composer test` - Run all tests
- `composer test-coverage` or `composer tc` - Run tests with coverage
- `composer test-filter <pattern>` or `composer tf` - Run specific tests
- `composer test-suite` or `composer ts` - Run both PHPStan analysis and tests
- Uses Pest testing framework

### Git Hooks
- `composer install-hooks` - Install shared git hooks for all developers
- `.githooks/install.sh` - Direct script to install hooks
- Pre-push hook automatically formats code and runs quality checks

## Architecture

### Backend Structure
- **Actions**: Single-purpose action classes for reusable business logic
- **Contracts**: Interface contracts for extensible systems (payment processors, support tickets)
- **Data**: Data transfer objects using Spatie Laravel Data
- **Drivers**: Extensible driver implementations (PaymentProcessor, SupportTicket)
- **Enums**: Application-wide enumerations using Spatie Enum (title case naming)
- **Events & Listeners**: Event-driven architecture (auto-discovered in Laravel 12)
- **Facades**: Custom facades (PaymentProcessor, SupportTicket) for accessing managers
- **Filament**: Admin and Marketplace panels with resources, pages, exports, imports
- **Managers**: Service managers using Laravel's Manager pattern for driver extensibility
- **Models**: Core models include `User`, `Product`, `Order`, `Forum`, `Post`, `Topic`, `Policy`, `SupportTicket`
- **Controllers**: Feature-organized controllers (Auth, Blog, Forums, Store, Settings, OAuth, Support, Policies)
- **Policies**: Authorization logic for all resources
- **Providers**: Custom social auth providers (Discord, Roblox) extending Laravel Socialite
- **Services**: Business logic services for complex operations
- **Traits**: Reusable functionality (`HasSlug`, `HasFiles`, `HasAuthor`, `Sluggable`)

### Frontend Structure
- **Pages**: Inertia.js pages organized by feature (`auth/`, `blog/`, `forums/`, `store/`, `settings/`, `support/`, `policies/`)
- **Components**: Reusable React components using shadcn/ui, Radix UI, TipTap editor
- **Layouts**: App shell, auth, and settings layouts
- **Hooks**: Custom React hooks (appearance/theme, mobile detection)
- **Types**: TypeScript definitions generated from Laravel models via Spatie TypeScript Transformer
- **Utils**: Utility functions including `apiRequest` wrapper for API calls with proper error handling
- **Services**: Frontend service classes for business logic

### Extensible Architecture (Manager Pattern)
- **Payment Processing**: Modular system supporting multiple drivers (default: Stripe via `StripeDriver`)
  - Located in `app/Drivers/Payments/` and `app/Managers/PaymentManager.php`
  - Access via `PaymentProcessor` facade
  - Implement `PaymentProcessor` contract for custom drivers
- **Support Tickets**: Modular ticket system supporting multiple backends (default: database via `DatabaseDriver`)
  - Located in `app/Drivers/SupportTickets/` and `app/Managers/SupportTicketManager.php`
  - Access via `SupportTicket` facade
  - Implement `SupportTicketProvider` contract for external integrations (Zendesk, etc.)

### Key Integrations
- **Inertia.js v2**: Bridges Laravel backend with React frontend (deferred props, prefetching, infinite scroll)
- **Filament v4**: Two admin panels - `/admin` for administration and `/marketplace` for seller dashboard
- **Laravel Cashier v15**: Default payment processor integration with Stripe
- **Laravel Passport v13**: OAuth2 server for API authentication
- **Spatie Permissions**: Role and permission-based access control
- **Spatie Settings**: Application-wide settings management
- **Laravel Socialite**: OAuth authentication with extensible custom providers
- **Laravel Scout**: Full-text search capabilities
- **Laravel Horizon**: Redis queue monitoring and management
- **Laravel Telescope**: Application debugging and monitoring (dev only)
- **API Platform**: RESTful API framework integration

### Database
- **Development**: MySQL (configured in `.env`)
- **Production**: MySQL/PostgreSQL recommended
- **SQLite**: Available as alternative (see `.env.example`)
- Migrations include users, products, categories, subscriptions, permissions, blog, forums, policies, support tickets
- Comprehensive seeders available for development data

### Configuration
- **Code Style**: Laravel Pint with custom rules in `pint.json`
- **Static Analysis**: PHPStan level 5 in `phpstan.neon`
- **Automated Refactoring**: Rector configuration with Laravel-specific rules
- **TypeScript**: Strict type checking enabled in `tsconfig.json`
- **ESLint v9**: React and TypeScript rules with Prettier integration
- **Prettier v3**: Code formatting for JS/TS/Blade with plugins for Tailwind and imports

## File Organization

### Route Files
- `routes/web.php` - Main application routes and homepage
- `routes/api.php` - API routes with versioning
- `routes/auth.php` - Authentication routes (login, register, verify, etc.)
- `routes/blog.php` - Blog posts and categories
- `routes/forums.php` - Forum topics, posts, categories
- `routes/policies.php` - Legal policies and terms
- `routes/settings.php` - User settings and preferences
- `routes/store.php` - E-commerce and product catalog
- `routes/support.php` - Support ticket system
- `routes/console.php` - Artisan console commands
- `routes/cashier.php` - Stripe Cashier webhook routes
- `routes/passport.php` - Laravel Passport OAuth routes

### Key Directories
- `app/Actions/` - Single-purpose action classes
- `app/Contracts/` - Interface contracts for extensibility
- `app/Data/` - Data transfer objects (Spatie Data)
- `app/Drivers/` - Driver implementations (Payments, SupportTickets)
- `app/Enums/` - Application enumerations
- `app/Facades/` - Custom facades (PaymentProcessor, SupportTicket)
- `app/Filament/Admin/` - Admin panel resources and pages
- `app/Filament/Marketplace/` - Marketplace seller dashboard
- `app/Filament/Exports/` - Export definitions
- `app/Filament/Imports/` - Import definitions
- `app/Http/Controllers/` - Feature-organized controllers
- `app/Managers/` - Service managers using Manager pattern
- `app/Models/` - Eloquent models
- `app/Policies/` - Authorization policies
- `app/Services/` - Business logic services
- `resources/js/components/ui/` - shadcn/ui component library
- `resources/js/pages/` - Inertia.js page components by feature
- `resources/css/filament/` - Filament panel custom styles
- `database/migrations/` - Database schema definitions
- `database/factories/` - Model factories for testing
- `database/seeders/` - Database seeders

## Development Notes

### Application-Specific Features
- **Type-Safe Routing**: Ziggy provides type-safe routing between Laravel and React
- **TypeScript Generation**: Use `composer types` to generate TypeScript definitions from Laravel models
- **Custom Facades**: `PaymentProcessor` and `SupportTicket` facades provide access to extensible managers
- **Manager Pattern**: Payment and support ticket systems use Laravel's Manager pattern for driver extensibility
- **Filament Panels**: Two separate panels - Admin (`/admin`) and Marketplace (`/marketplace`) for sellers
- **Social Auth**: Extensible OAuth system with Discord and Roblox providers (custom provider support)
- **API Platform**: RESTful API with versioning and API resources
- **Email**: Always create email using Mailable classes (never inline)
- **Webhooks**: Stripe webhooks handled at `/stripe/webhook` when using default payment driver

### Laravel 12 Conventions
- Events auto-discover and don't need manual registration
- No `app/Http/Middleware/` directory - register middleware in `bootstrap/app.php`
- No `app/Console/Kernel.php` - commands auto-register from `app/Console/Commands/`
- Service providers register in `bootstrap/providers.php`

### Development Workflow
- MySQL is configured by default (see `.env`) - SQLite available as alternative
- Git hooks in `.githooks/` ensure code quality and consistent formatting
- Use `composer dev` to run full dev environment (Horizon, logs, Vite)
- Horizon handles queued jobs and provides dashboard at `/horizon`
- Telescope available at `/telescope` for debugging (dev only)
- **Do not run tests after a prompt unless otherwise instructed**

### Code Generation
- Use `composer types` to update TypeScript definitions after model changes
- Use `composer facades` to generate documentation for custom facades
- Use `composer ide` to regenerate IDE helper files after significant changes

### React Component Guidelines
- Always create individual, reusable components for UI elements rather than inline JSX
- Focus on composability - components should be easily combined and reused
- Use TypeScript interfaces for proper type safety
- Follow the existing component structure in `resources/js/components/`
- Leverage shadcn/ui components as building blocks
- Always use Lucide React icons instead of Heroicons or other icon libraries
- Always use the `apiRequest` wrapper from `@/utils/api` for API calls instead of direct axios calls
- Import and use proper error handling: `import { ApiError, apiRequest } from '@/utils/api';`
- All headings should be sentence case
- All buttons should be sentence case unless a part of a page header or with an icon.

### Laravel Development Guidelines
- Always use Facades instead of helper functions (e.g., `Auth::id()` not `auth()->id()`)
- Never use SoftDeletes - use hard deletes only
- Use enums instead of constants, with title case naming (e.g., `AnnouncementType::Info`)
- In migrations, use `->string('slug')` never `->slug()`
- Implement `HasAuthor` trait for models that need creator tracking
- Use proper Sluggable contract implementation with `HasSlug` trait
- Use Carbon implementation for date management
- Always use Attributes instead of Laravel's `getAttributeNameAttribute()` pattern
- Use built in Laravel helpers such as Collections, Str, Arr, over standard conventions whenever possible
- Do not add unnecessary doc blocks

## Documentation Maintenance

- Always keep README.md updated when making significant changes to:
  - Project features or architecture
  - Installation or setup process
  - Development workflow or commands
  - Environment configuration requirements
  - New integrations or dependencies
- The README should accurately reflect the current state of Mountain Interactive, not generic Laravel starter kit information

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.17
- filament/filament (FILAMENT) - v4
- inertiajs/inertia-laravel (INERTIA) - v2
- laravel/cashier (CASHIER) - v16
- laravel/framework (LARAVEL) - v12
- laravel/horizon (HORIZON) - v5
- laravel/nightwatch (NIGHTWATCH) - v1
- laravel/passport (PASSPORT) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/scout (SCOUT) - v10
- laravel/socialite (SOCIALITE) - v5
- laravel/telescope (TELESCOPE) - v5
- livewire/livewire (LIVEWIRE) - v3
- tightenco/ziggy (ZIGGY) - v2
- larastan/larastan (LARASTAN) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v3
- phpunit/phpunit (PHPUNIT) - v11
- rector/rector (RECTOR) - v2
- react (REACT) - v19
- @inertiajs/react (INERTIA) - v2
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure - don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.


=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs
- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries - package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll" - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms


=== php rules ===

## PHP

- Always use strict typing at the head of a `.php` file: `declare(strict_types=1);`.
- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over comments. Never use comments within the code itself unless there is something _very_ complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- That being said, keys in an Enum should follow existing application Enum conventions.


=== herd rules ===

## Laravel Herd

- The application is served by Laravel Herd and will be available at: https?://[kebab-case-project-dir].test. Use the `get-absolute-url` tool to generate URLs for the user to ensure valid URLs.
- You must not run any commands to make the site available via HTTP(s). It is _always_ available through Laravel Herd.


=== inertia-laravel/core rules ===

## Inertia Core

- Inertia.js components should be placed in the `resources/js/Pages` directory unless specified differently in the JS bundler (vite.config.js).
- Use `Inertia::render()` for server-side routing instead of traditional Blade views.
- Use `search-docs` for accurate guidance on all things Inertia.

<code-snippet lang="php" name="Inertia::render Example">
// routes/web.php example
Route::get('/users', function () {
    return Inertia::render('Users/Index', [
        'users' => User::all()
    ]);
});
</code-snippet>


=== inertia-laravel/v2 rules ===

## Inertia v2

- Make use of all Inertia features from v1 & v2. Check the documentation before making any changes to ensure we are taking the correct approach.

### Inertia v2 New Features
- Polling
- Prefetching
- Deferred props
- Infinite scrolling using merging props and `WhenVisible`
- Lazy loading data on scroll

### Deferred Props & Empty States
- When using deferred props on the frontend, you should add a nice empty state with pulsing / animated skeleton.

### Inertia Form General Guidance
- The recommended way to build forms when using Inertia is with the `<Form>` component - a useful example is below. Use `search-docs` with a query of `form component` for guidance.
- Forms can also be built using the `useForm` helper for more programmatic control, or to follow existing conventions. Use `search-docs` with a query of `useForm helper` for guidance.
- `resetOnError`, `resetOnSuccess`, and `setDefaultsOnSuccess` are available on the `<Form>` component. Use `search-docs` with a query of 'form component resetting' for guidance.


=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.


=== laravel/v12 rules ===

## Laravel 12

- Use the `search-docs` tool to get version specific documentation.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

### Laravel 12 Structure
- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database
- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models
- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.


=== livewire/core rules ===

## Livewire Core
- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices
- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

    ```blade
    @foreach ($items as $item)
        <div wire:key="item-{{ $item->id }}">
            {{ $item->name }}
        </div>
    @endforeach
    ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()` for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>


## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>


    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>


=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2
- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
    - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
    - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
    - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
    - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives
- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine
- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks
- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });
});
</code-snippet>


=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.


=== pest/core rules ===

## Pest
### Testing
- If you need to verify a feature is working, write or update a Unit / Feature test.

### Pest Tests
- All tests must be written using Pest. Use `php artisan make:test --pest {name}`.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files - these are core to the application.
- Tests should test all of the happy paths, failure paths, and weird paths.
- Tests live in the `tests/Feature` and `tests/Unit` directories.
- Pest tests look and behave like this:
<code-snippet name="Basic Pest Test Example" lang="php">
it('is true', function () {
    expect(true)->toBeTrue();
});
</code-snippet>

### Running Tests
- Run the minimal number of tests using an appropriate filter before finalizing code edits.
- To run all tests: `php artisan test`.
- To run all tests in a file: `php artisan test tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --filter=testName` (recommended after making a change to a related file).
- When the tests relating to your changes are passing, ask the user if they would like to run the entire test suite to ensure everything is still passing.

### Pest Assertions
- When asserting status codes on a response, use the specific method like `assertForbidden` and `assertNotFound` instead of using `assertStatus(403)` or similar, e.g.:
<code-snippet name="Pest Example Asserting postJson Response" lang="php">
it('returns all', function () {
    $response = $this->postJson('/api/docs', []);

    $response->assertSuccessful();
});
</code-snippet>

### Mocking
- Mocking can be very helpful when appropriate.
- When mocking, you can use the `Pest\Laravel\mock` Pest function, but always import it via `use function Pest\Laravel\mock;` before using it. Alternatively, you can use `$this->mock()` if existing tests do.
- You can also create partial mocks using the same import or self method.

### Datasets
- Use datasets in Pest to simplify tests which have a lot of duplicated data. This is often the case when testing validation rules, so consider going with this solution when writing tests for validation rules.

<code-snippet name="Pest Dataset Example" lang="php">
it('has emails', function (string $email) {
    expect($email)->not->toBeEmpty();
})->with([
    'james' => 'james@laravel.com',
    'taylor' => 'taylor@laravel.com',
]);
</code-snippet>


=== inertia-react/core rules ===

## Inertia + React

- Use `router.visit()` or `<Link>` for navigation instead of traditional links.

<code-snippet name="Inertia Client Navigation" lang="react">

import { Link } from '@inertiajs/react'
<Link href="/">Home</Link>

</code-snippet>


=== inertia-react/v2/forms rules ===

## Inertia + React Forms

<code-snippet name="`<Form>` Component Example" lang="react">

import { Form } from '@inertiajs/react'

export default () => (
    <Form action="/users" method="post">
        {({
            errors,
            hasErrors,
            processing,
            wasSuccessful,
            recentlySuccessful,
            clearErrors,
            resetAndClearErrors,
            defaults
        }) => (
        <>
        <input type="text" name="name" />

        {errors.name && <div>{errors.name}</div>}

        <button type="submit" disabled={processing}>
            {processing ? 'Creating...' : 'Create User'}
        </button>

        {wasSuccessful && <div>User created successfully!</div>}
        </>
    )}
    </Form>
)

</code-snippet>


=== tailwindcss/core rules ===

## Tailwind Core

- Use Tailwind CSS classes to style HTML, check and use existing tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc..)
- Think through class placement, order, priority, and defaults - remove redundant classes, add classes to parent or child carefully to limit repetition, group elements logically
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing, don't use margins.

    <code-snippet name="Valid Flex Gap Spacing Example" lang="html">
        <div class="flex gap-8">
            <div>Superior</div>
            <div>Michigan</div>
            <div>Erie</div>
        </div>
    </code-snippet>


### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.


=== tailwindcss/v4 rules ===

## Tailwind 4

- Always use Tailwind CSS v4 - do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.
<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>


### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option - use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |


=== filament/filament rules ===

## Filament
- Filament is used by this application, check how and where to follow existing application conventions.
- Filament is a Server-Driven UI (SDUI) framework for Laravel. It allows developers to define user interfaces in PHP using structured configuration objects. It is built on top of Livewire, Alpine.js, and Tailwind CSS.
- You can use the `search-docs` tool to get information from the official Filament documentation when needed. This is very useful for Artisan command arguments, specific code examples, testing functionality, relationship management, and ensuring you're following idiomatic practices.
- Utilize static `make()` methods for consistent component initialization.

### Artisan
- You must use the Filament specific Artisan commands to create new files or components for Filament. You can find these with the `list-artisan-commands` tool, or with `php artisan` and the `--help` option.
- Inspect the required options, always pass `--no-interaction`, and valid arguments for other options when applicable.

### Filament's Core Features
- Actions: Handle doing something within the application, often with a button or link. Actions encapsulate the UI, the interactive modal window, and the logic that should be executed when the modal window is submitted. They can be used anywhere in the UI and are commonly used to perform one-time actions like deleting a record, sending an email, or updating data in the database based on modal form input.
- Forms: Dynamic forms rendered within other features, such as resources, action modals, table filters, and more.
- Infolists: Read-only lists of data.
- Notifications: Flash notifications displayed to users within the application.
- Panels: The top-level container in Filament that can include all other features like pages, resources, forms, tables, notifications, actions, infolists, and widgets.
- Resources: Static classes that are used to build CRUD interfaces for Eloquent models. Typically live in `app/Filament/Resources`.
- Schemas: Represent components that define the structure and behavior of the UI, such as forms, tables, or lists.
- Tables: Interactive tables with filtering, sorting, pagination, and more.
- Widgets: Small component included within dashboards, often used for displaying data in charts, tables, or as a stat.

### Relationships
- Determine if you can use the `relationship()` method on form components when you need `options` for a select, checkbox, repeater, or when building a `Fieldset`:

<code-snippet name="Relationship example for Form Select" lang="php">
Forms\Components\Select::make('user_id')
    ->label('Author')
    ->relationship('author')
    ->required(),
</code-snippet>


## Testing
- It's important to test Filament functionality for user satisfaction.
- Ensure that you are authenticated to access the application within the test.
- Filament uses Livewire, so start assertions with `livewire()` or `Livewire::test()`.

### Example Tests

<code-snippet name="Filament Table Test" lang="php">
    livewire(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($users->first()->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($users->last()->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
</code-snippet>

<code-snippet name="Filament Create Resource Test" lang="php">
    livewire(CreateUser::class)
        ->fillForm([
            'name' => 'Howdy',
            'email' => 'howdy@example.com',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas(User::class, [
        'name' => 'Howdy',
        'email' => 'howdy@example.com',
    ]);
</code-snippet>

<code-snippet name="Testing Multiple Panels (setup())" lang="php">
    use Filament\Facades\Filament;

    Filament::setCurrentPanel('app');
</code-snippet>

<code-snippet name="Calling an Action in a Test" lang="php">
    livewire(EditInvoice::class, [
        'invoice' => $invoice,
    ])->callAction('send');

    expect($invoice->refresh())->isSent()->toBeTrue();
</code-snippet>


### Important Version 4 Changes
- File visibility is now `private` by default.
- The `deferFilters` method from Filament v3 is now the default behavior in Filament v4, so users must click a button before the filters are applied to the table. To disable this behavior, you can use the `deferFilters(false)` method.
- The `Grid`, `Section`, and `Fieldset` layout components no longer span all columns by default.
- The `all` pagination page method is not available for tables by default.
- All action classes extend `Filament\Actions\Action`. No action classes exist in `Filament\Tables\Actions`.
- The `Form` & `Infolist` layout components have been moved to `Filament\Schemas\Components`, for example `Grid`, `Section`, `Fieldset`, `Tabs`, `Wizard`, etc.
- A new `Repeater` component for Forms has been added.
- Icons now use the `Filament\Support\Icons\Heroicon` Enum by default. Other options are available and documented.

### Organize Component Classes Structure
- Schema components: `Schemas/Components/`
- Table columns: `Tables/Columns/`
- Table filters: `Tables/Filters/`
- Actions: `Actions/`
</laravel-boost-guidelines>
