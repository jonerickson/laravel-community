# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

This is a Laravel + React application built using the Laravel React Starter Kit. It features:
- Laravel 12 backend with Inertia.js for SPA functionality
- React 19 frontend with TypeScript
- Filament admin panels (Admin and Marketplace)
- Stripe integration via Laravel Cashier
- Role-based permissions with Spatie/Laravel-permission
- Social authentication (Discord, Roblox)
- E-commerce functionality with products and categories

## Development Commands

### Backend (PHP/Laravel)
- `composer dev` - Run development environment with Horizon queue worker, logging, and frontend
- `composer test` - Run PHPUnit tests
- `composer analyze` - Run PHPStan static analysis
- `composer cs-fix` or `composer lint` - Fix code style with Laravel Pint
- `composer ide` - Generate IDE helper files
- `composer reset` - Full environment reset with fresh migrations and seeding

### Frontend (Node.js/React)
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR support
- `npm run lint` - Run ESLint and fix issues
- `npm run format` - Format code with Prettier
- `npm run types` - Type check with TypeScript

### Testing
- `composer test` - Run all tests
- `composer test-coverage` - Run tests with coverage
- `composer test-filter <pattern>` - Run specific tests
- Uses Pest testing framework

### Git Hooks
- `composer install-hooks` - Install shared git hooks for all developers
- `.githooks/install.sh` - Direct script to install hooks
- Pre-push hook automatically formats code and runs quality checks

## Architecture

### Backend Structure
- **Models**: Core models include `User`, `Product`, `ProductCategory`, `Invoice` with Eloquent relationships
- **Controllers**: Organized by feature (Auth, Blog, Store, Settings, OAuth)
- **Filament Resources**: Admin panel resources for storeProduct/category management
- **Providers**: Custom social auth providers (Discord, Roblox)
- **Policies**: Authorization logic for resources
- **Traits**: Reusable functionality like `HasSlug`, `HasFiles`

### Frontend Structure
- **Pages**: Inertia.js pages in `resources/js/pages/`
- **Components**: Reusable React components using shadcn/ui and Radix UI
- **Layouts**: App shell, auth, and settings layouts
- **Hooks**: Custom React hooks for appearance, mobile detection
- **Types**: TypeScript definitions for API responses and props

### Key Integrations
- **Inertia.js**: Bridges Laravel backend with React frontend
- **Filament**: Provides admin panels at `/admin` and marketplace functionality
- **Laravel Cashier**: Stripe payment processing and subscription management
- **Spatie Permissions**: Role and permission-based access control
- **Laravel Socialite**: OAuth authentication with custom providers

### Database
- Uses SQLite by default (see `.env.example`)
- Migrations include users, products, categories, subscriptions, permissions
- Seeders available for development data

### Configuration
- **Code Style**: Laravel Pint with custom rules in `pint.json`
- **Static Analysis**: PHPStan level 5 configuration in `phpstan.neon`
- **TypeScript**: Strict type checking enabled
- **ESLint**: React and TypeScript rules with Prettier integration

## File Organization

### Route Files
- `routes/web.php` - Main application routes
- `routes/auth.php` - Authentication routes
- `routes/store.php` - E-commerce routes
- `routes/settings.php` - User settings routes

### Key Directories
- `app/Filament/Resources/` - Admin panel resource definitions
- `app/Http/Controllers/` - Feature-organized controllers
- `resources/js/components/ui/` - shadcn/ui component library
- `resources/js/pages/` - Inertia.js page components
- `database/migrations/` - Database schema definitions

## Development Notes

- The application uses Wayfinder for type-safe routing between Laravel and React
- Filament Shield provides role-based access to admin panels
- Custom social providers extend Laravel Socialite
- SQLite is used for local development; production likely uses MySQL/PostgreSQL
- The app includes subscription/billing functionality via Stripe
- Uses modern React patterns with hooks and functional components
- Git hooks in `.githooks/` directory ensure code quality and consistent formatting across the team

### React Component Guidelines
- Always create individual, reusable components for UI elements rather than inline JSX
- Focus on composability - components should be easily combined and reused
- Use TypeScript interfaces for proper type safety
- Follow the existing component structure in `resources/js/components/`
- Leverage shadcn/ui components as building blocks
- Always use Lucide React icons instead of Heroicons or other icon libraries
- Always use the `apiRequest` wrapper from `@/utils/api` for API calls instead of direct axios calls
- Import and use proper error handling: `import { ApiError, apiRequest } from '@/utils/api';`

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
