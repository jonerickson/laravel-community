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

## Architecture

### Backend Structure
- **Models**: Core models include `User`, `Product`, `ProductCategory`, `Invoice` with Eloquent relationships
- **Controllers**: Organized by feature (Auth, News, Store, Settings, OAuth)
- **Filament Resources**: Admin panel resources for product/category management
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