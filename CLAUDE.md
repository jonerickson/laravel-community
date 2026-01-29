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

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

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
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- rector/rector (RECTOR) - v2
- react (REACT) - v19
- @inertiajs/react (INERTIA) - v2
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3
- tailwindcss (TAILWINDCSS) - v4

## Skills Activation

This project has domain-specific skills available. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

- `pest-testing` — Tests applications using the Pest 4 PHP framework. Activates when writing tests, creating unit or feature tests, adding assertions, testing Livewire components, browser testing, debugging test failures, working with datasets or mocking; or when the user mentions test, spec, TDD, expects, assertion, coverage, or needs to verify functionality works.
- `inertia-react-development` — Develops Inertia.js v2 React client-side applications. Activates when creating React pages, forms, or navigation; using &lt;Link&gt;, &lt;Form&gt;, useForm, or router; working with deferred props, prefetching, or polling; or when user mentions React with Inertia, React pages, React forms, or React navigation.
- `tailwindcss-development` — Styles applications using Tailwind CSS v4 utilities. Activates when adding styles, restyling components, working with gradients, spacing, layout, flex, grid, responsive design, dark mode, colors, typography, or borders; or when the user mentions CSS, styling, classes, Tailwind, restyle, hero section, cards, buttons, or any visual/UI changes.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs

- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before trying other approaches when working with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries at once. For example: `['rate limiting', 'routing rate limiting', 'routing']`. The most relevant results will be returned first.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

# PHP

- Always use strict typing at the head of a `.php` file: `declare(strict_types=1);`.
- Always use curly braces for control structures, even for single-line bodies.

## Constructors

- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

## Type Declarations

- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Enums

- That being said, keys in an Enum should follow existing application Enum conventions.

## Comments

- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless the logic is exceptionally complex.

## PHPDoc Blocks

- Add useful array shape type definitions when appropriate.

=== herd rules ===

# Laravel Herd

- The application is served by Laravel Herd and will be available at: `https?://[kebab-case-project-dir].test`. Use the `get-absolute-url` tool to generate valid URLs for the user.
- You must not run any commands to make the site available via HTTP(S). It is always available through Laravel Herd.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/Pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

=== inertia-laravel/v2 rules ===

# Inertia v2

- Use all Inertia features from v1 and v2. Check the documentation before making changes to ensure the correct approach.
- New features: deferred props, infinite scrolling (merging props + `WhenVisible`), lazy loading on scroll, polling, prefetching.
- When using deferred props, add an empty state with a pulsing or animated skeleton.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

## Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

## Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

## Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v12 rules ===

# Laravel 12

- CRITICAL: ALWAYS use `search-docs` tool for version-specific Laravel documentation and updated code examples.
- Since Laravel 11, Laravel has a new streamlined file structure which this project uses.

## Laravel 12 Structure

- In Laravel 12, middleware are no longer registered in `app/Http/Kernel.php`.
- Middleware are configured declaratively in `bootstrap/app.php` using `Application::configure()->withMiddleware()`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- The `app\Console\Kernel.php` file no longer exists; use `bootstrap/app.php` or `routes/console.php` for console configuration.
- Console commands in `app/Console/Commands/` are automatically available and do not require manual registration.

## Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 12 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

=== pint/core rules ===

# Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.
- CRITICAL: ALWAYS use `search-docs` tool for version-specific Pest documentation and updated code examples.
- IMPORTANT: Activate `pest-testing` every time you're working with a Pest or testing-related task.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

=== tailwindcss/core rules ===

# Tailwind CSS

- Always use existing Tailwind conventions; check project patterns before adding new ones.
- IMPORTANT: Always use `search-docs` tool for version-specific Tailwind CSS documentation and updated code examples. Never rely on training data.
- IMPORTANT: Activate `tailwindcss-development` every time you're working with a Tailwind CSS or styling-related task.
</laravel-boost-guidelines>
