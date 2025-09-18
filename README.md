# Mountain Interactive

A modern Laravel + React marketplace application built with Inertia.js, featuring user authentication, storeProduct management, subscription billing, and administrative tools.

## Features

- **User Management**: Registration, authentication, email verification, and social login (Discord, Roblox)
- **E-Commerce Store**: Product catalog with categories, user-generated content, and file attachments
- **User Marketplace**: User-provided products for customers to purchase with management dashboard
- **Blog System**: Content management with posts and categories
- **Forum Platform**: Community discussions with topics, posts, and categories
- **Subscription Billing**: Stripe integration with Laravel Cashier for payments and subscriptions
- **Admin Panel**: Filament-powered admin interface for managing products, categories, and users
- **Role-Based Access**: Permission system using Spatie Laravel Permission
- **Modern Frontend**: React 19 with TypeScript, Tailwind CSS, and shadcn/ui components

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: React 19, TypeScript, Inertia.js
- **Styling**: Tailwind CSS v4, shadcn/ui, Lucide React
- **Database**: SQLite (development), MySQL/PostgreSQL (production)
- **Payment Processing**: Stripe via Laravel Cashier or custom implementation
- **Support Tickets**: Default database driver or custom implementation
- **Admin Interface**: Filament v4
- **User Marketplace Interface**: Filament v4
- **Build Tools**: Vite
- **Code Quality**: Laravel Pint, PHPStan, Rector, ESLint, Prettier
- **Testing**: Pest

## Getting Started

### Prerequisites

- PHP 8.2+
- Node.js 22+
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
- `composer facades` - Generate Facade documentation

**JavaScript/TypeScript:**
- `npm run lint` - Run ESLint
- `npm run format` - Format with Prettier
- `npm run types` - TypeScript type checking
- `composer types` - Generate typescript definitions

**Testing:**
- `composer test` - Run all tests
- `composer tf` - Run tests with coverage
- `composer tc` - Run test suite with type coverage

## Webhooks

When using Stripe as the payment processor, you can listen for webhooks locally with:

```bash
stripe listen --forward-to=https://mi.test/stripe/webhook --events="customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,customer.updated,customer.deleted,payment_method.automatically_updated,invoice.payment_action_required,invoice.payment_succeeded"
```

## Project Structure

```
app/
├── Actions/               # Action classes
├── Contracts/             # Interface contracts
├── Data/                  # Data transfer objects
├── Drivers/               # Driver implementations (Payments, SupportTickets)
├── Enums/                 # Application enumerations
├── Events/                # Event classes
├── Exceptions/            # Custom exception classes
├── Facades/               # Application facades
├── Filament/              # Filament admin and marketplace resources
├── Http/                  # Controllers, middleware, requests, resources
├── Listeners/             # Event listeners
├── Managers/              # Service managers (Payment, SupportTicket)
├── Models/                # Eloquent models
├── Policies/              # Authorization policies
├── Providers/             # Service providers
├── Services/              # Business logic services
└── Traits/                # Reusable traits

resources/
├── css/
│   └── filament/
│       ├── admin/        # Admin panel styles
│       └── marketplace/  # Marketplace panel styles
├── js/
│   ├── components/
│   │   └── ui/           # shadcn/ui component library
│   ├── hooks/            # Custom React hooks
│   ├── layouts/
│   │   ├── app/          # Main application layout
│   │   ├── auth/         # Authentication layout
│   │   └── settings/     # Settings layout
│   ├── lib/              # Utility libraries
│   ├── pages/            # Inertia.js pages
│   │   ├── auth/         # Authentication pages
│   │   ├── blog/         # Blog pages
│   │   ├── forums/       # Forum pages
│   │   │   ├── categories/
│   │   │   ├── posts/
│   │   │   └── topics/
│   │   ├── oauth/        # OAuth callback pages
│   │   ├── policies/     # Policy pages
│   │   ├── settings/     # User settings pages
│   │   ├── store/        # Store pages
│   │   │   ├── categories/
│   │   │   └── products/
│   │   └── support/      # Support ticket pages
│   ├── services/         # Service classes
│   ├── types/            # TypeScript type definitions
│   └── utils/            # Utility functions
└── views/
    ├── errors/           # Error page templates
    └── filament/
        ├── admin/
        │   ├── pages/    # Custom admin pages
        │   └── reports/  # Report templates
        └── components/   # Custom Filament components

routes/
├── web.php               # Main web routes
├── api.php               # API routes
├── auth.php              # Authentication routes
├── blog.php              # Blog routes
├── console.php           # Console commands
├── forums.php            # Forum routes
├── policies.php          # Policy routes
├── settings.php          # User settings routes
├── store.php             # Store routes
└── support.php           # Support ticket routes
```

## Key Features

### Authentication
- Email/password registration and login
- Email verification
- Social authentication (Discord, Roblox)
- Password reset functionality

### Store
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

### Payment Processing
- Modular payment processor architecture using the Manager pattern
- Default Stripe driver implementation
- Any payment processor can be implemented by creating a driver that implements the `PaymentProcessor` contract
- Supports product/price management, payment methods, subscriptions, and checkout flows
- Configure payment driver in `config/payment.default`

### Support Ticket Management
- Flexible support ticket system using the Manager pattern
- Default database driver for local ticket storage
- External service integration capability through driver implementation
- Any support service can be integrated by implementing the `SupportTicketProvider` contract
- Features include ticket CRUD, comments, assignments, status management, and file attachments
- Configure support driver in `config/support-tickets.default`

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
