# Mountain Interactive

A modern Laravel + React marketplace application built with Inertia.js, featuring user authentication, storeProduct management, subscription billing, and administrative tools.

## Features

- **User Management**: Registration, authentication, email verification, and social login (Discord, Roblox)
- **Product Marketplace**: Product catalog with categories, user-generated content, and file attachments
- **Subscription Billing**: Stripe integration with Laravel Cashier for payments and subscriptions
- **Admin Panel**: Filament-powered admin interface for managing products, categories, and users
- **Role-Based Access**: Permission system using Spatie Laravel Permission
- **Modern Frontend**: React 19 with TypeScript, Tailwind CSS, and shadcn/ui components

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: React 19, TypeScript, Inertia.js
- **Styling**: Tailwind CSS, shadcn/ui, Radix UI
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Payment Processing**: Stripe via Laravel Cashier
- **Admin Interface**: Filament
- **Build Tools**: Vite, Laravel Mix

## Getting Started

### Prerequisites

- PHP 8.2+
- Node.js 18+
- Composer
- SQLite (for development)

### Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd mi
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install JavaScript dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   touch database/database.sqlite
   php artisan migrate --seed
   ```

6. **Install git hooks** (recommended)
   ```bash
   composer install-hooks
   ```

### Development

Start the development environment:

```bash
# Start all development services (queue, logs, frontend)
composer dev

# Or start services individually:
php artisan serve          # Laravel server
npm run dev               # Vite development server
php artisan horizon       # Queue worker
php artisan pail          # Real-time logs
```

### Building for Production

```bash
npm run build
```

## Development Workflow

### Code Quality

This project includes automated code quality tools:

- **PHP**: Laravel Pint for code formatting, PHPStan for static analysis
- **JavaScript/TypeScript**: ESLint for linting, Prettier for formatting
- **Git Hooks**: Pre-push hooks automatically format code and run quality checks

### Available Commands

**PHP/Laravel:**
- `composer test` - Run PHPUnit tests
- `composer cs-fix` - Fix code style with Pint
- `composer analyze` - Run PHPStan analysis
- `composer ide` - Generate IDE helper files

**JavaScript/TypeScript:**
- `npm run lint` - Run ESLint
- `npm run format` - Format with Prettier
- `npm run types` - TypeScript type checking

**Testing:**
- `composer test` - Run all tests
- `composer test-coverage` - Run tests with coverage
- Uses Pest testing framework

## Project Structure

```
app/
├── Filament/Resources/     # Admin panel resources
├── Http/Controllers/       # API and web controllers
├── Models/                # Eloquent models
├── Policies/              # Authorization policies
└── Providers/             # Service providers

resources/
├── js/
│   ├── components/        # React components
│   ├── pages/            # Inertia.js pages
│   ├── layouts/          # Page layouts
│   └── hooks/            # Custom React hooks
└── css/                  # Stylesheets

routes/
├── web.php               # Web routes
├── auth.php              # Authentication routes
├── store.php             # Marketplace routes
└── settings.php          # User settings routes
```

## Key Features

### Authentication
- Email/password registration and login
- Email verification
- Social authentication (Discord, Roblox)
- Password reset functionality

### Marketplace
- Product catalog with categories
- File uploads and attachments
- User-generated storeProduct listings
- Search and filtering

### Billing & Subscriptions
- Stripe payment processing
- Subscription management
- Invoice generation
- Payment method management

### Administration
- Filament admin panel at `/admin`
- User and role management
- Product and category administration
- Permission-based access control

## Environment Configuration

Key environment variables:

```env
APP_NAME="Mountain Interactive"
APP_URL=http://mi.test

# Database
DB_CONNECTION=sqlite

# Stripe
STRIPE_KEY=your-stripe-key
STRIPE_SECRET=your-stripe-secret
STRIPE_WEBHOOK_SECRET=your-stripe-webhook-secret

# Social Authentication
DISCORD_CLIENT_ID=
DISCORD_CLIENT_SECRET=
ROBLOX_CLIENT_ID=
ROBLOX_CLIENT_SECRET=
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Install git hooks: `composer install-hooks`
4. Make your changes
5. Run tests: `composer test`
6. Submit a pull request

## License

This project is licensed under the MIT License.
