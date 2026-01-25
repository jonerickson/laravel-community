<p align="center"><img src="https://raw.githubusercontent.com/jonerickson/laravel-community/refs/heads/master/art/header.png" alt="Logo"></p>

<div align="center">

# 🚀 Laravel Community

#### A modern, full-featured marketplace platform built for communities

Laravel Community is an open-source platform that combines the power of an e-commerce store, user marketplace, community forums, and content management into one seamless experience. Built with modern technologies and designed for scalability, it's perfect for communities looking to create their own digital ecosystem.

### 🌐 [See it Live](https://reactstudios.com/)

See Laravel Community powering a real community in production

![GitHub Release](https://img.shields.io/github/v/release/jonerickson/laravel-community?display_name=release)
![Tests](https://github.com/jonerickson/laravel-community/actions/workflows/tests.yml/badge.svg)
[![codecov](https://codecov.io/gh/jonerickson/laravel-community/graph/badge.svg)](https://codecov.io/gh/jonerickson/laravel-community)
![PHPStan](https://img.shields.io/badge/CodeStyle-Laravel-green.svg)
![PHPStan](https://img.shields.io/badge/PHPStan-level%201-yellow.svg)

</div>

## ✨ What Makes It Special

### 🛍️ **Dual Commerce System**
Run your own official store while empowering your community to sell their creations. Our platform features both a managed e-commerce store and a user marketplace where creators can list and sell their products, complete with automated payouts and revenue sharing.

### 💬 **Community Hub**
Keep your community engaged with built-in forums for discussions, a blog system for announcements and content, and real-time notifications. Everything your community needs to connect and grow, all in one place.

### 🎯 **User Dashboard**
Every user gets their own personalized dashboard where they can manage purchases, track orders, handle subscriptions, submit support tickets, and if they're a seller, manage their marketplace listings and view earnings.

### 💳 **Flexible Payment System**
Accept payments through multiple processors with our modular architecture. Stripe comes ready out of the box, but you can easily plug in any payment provider. Handle one-time purchases, recurring subscriptions, and marketplace payouts seamlessly.

### 🛡️ **Advanced Security**
Protect your platform with built-in fraud detection, threat monitoring, and comprehensive security features. Role-based permissions, OAuth authentication, and extensible security integrations keep your community safe.

### 📊 **Powerful Admin Tools**
Manage everything from a beautiful, intuitive admin panel. Handle users, products, orders, content, and marketplace submissions with ease. Built-in analytics and reporting help you make data-driven decisions.

### 🎫 **Support System**
Keep your users happy with an integrated support ticket system. Whether you want to handle tickets in-house or connect to external services like Zendesk, the modular architecture makes it simple.

### 🚀 **Deploy Anywhere**
Ready for production with Docker and Kubernetes support. Scale from a small community to enterprise-level with confidence. Deploy on your own infrastructure or use cloud providers—the choice is yours.

## 🎨 Key Features

- 🔐 **Flexible Authentication** - Email, password, and social login with support for custom OAuth providers
- 🏪 **Official Store** - Full-featured e-commerce with products, categories, and file attachments
- 🤝 **User Marketplace** - Let your community members become sellers with their own dashboard
- 💰 **Subscription Management** - Recurring billing, invoice generation, and payment method handling
- 📝 **Content Management** - Blog system with posts, categories, and rich text editing
- 💭 **Community Forums** - Topics, discussions, and moderated conversations
- 📄 **Policy System** - Terms of service, privacy policies, and legal document management
- 👥 **Role-Based Access** - Granular permissions for users, sellers, moderators, and admins
- 🌐 **API Platform** - RESTful APIs for integrations and mobile apps
- 📱 **Modern Interface** - Responsive design that works beautifully on any device
- 🔔 **Real-time Updates** - Keep users informed with notifications and live data
- 🎨 **Customizable** - Themes, branding, and extensible architecture

## 🏗️ Architecture Highlights

Built with a modular, extensible architecture:

- **Modular Payment Processing** - Swap payment providers without changing your code
- **Extensible Support System** - Connect to external ticketing services or use the built-in system
- **Driver-Based Design** - Easy to extend with new integrations and features
- **Event-Driven** - React to platform events with custom listeners and integrations
- **API-First** - Full API support for mobile apps and external integrations
- **Type-Safe** - TypeScript on the frontend, strict typing throughout

## 🚀 Quick Start

### Prerequisites

- PHP 8.4 or higher
- Node.js 22 or higher
- Composer
- Database Service (SQLite for local, MySQL/PostgreSQL for production)
- Cache Service (Redis required for production, or Local cache options for development)
- Mail Service (SMTP, Sendgrid, Mailgun, SES, Postmark, Resend)
- File Storage (Local storage or S3 for production)

[![Open in GitHub Codespaces](https://github.com/codespaces/badge.svg)](https://github.com/codespaces/new?hide_repo_select=true&ref=master&repo=1018219148)

### Installation

Running the setup command will install all the necessary dependencies, build the frontend assets, and guide you through an installation process that will seed the required permissions, groups and optionally create a superuser account.

```bash
# Clone the repository
git clone https://github.com/jonerickson/laravel-community
cd laravel-community

# One-command setup
composer setup
```

That's it! The setup command handles installation, configuration, and database seeding automatically.

### Running Locally

Laravel Community ships with support for Docker but may also be used if you have PHP and a database installed locally, such as with using **Laravel Herd**.

```bash
# Laravel Herd/Local: Start the full development environment
composer dev

# Docker: See below if using Docker
docker compose up
```

This starts the queue workers, log viewer, and frontend build tools all at once.

### First Time Install

Optional: These commands are run when running `composer setup` for the first time but may also be manually run to reset the app environment.

```bash
# Reset the database
php artisan migrate:fresh

# Install the necessary data to run the app
php artisan app:install
```

## 🛠️ Development

### Code Quality

We take code quality seriously with automated tools:

- 🔍 **Static Analysis** - Catch bugs before they happen
- ✨ **Auto-formatting** - Consistent code style across the project
- 🧪 **Comprehensive Testing** - Test coverage for peace of mind
- 🪝 **Git Hooks** - Automated quality checks on every commit

### Useful Commands

```bash
# Testing
composer test              # Run the test suite
composer test-coverage     # Generate coverage reports

# Code Quality
composer lint              # Format PHP code
composer analyze           # Run static analysis
npm run lint              # Check JavaScript/TypeScript
npm run format            # Format frontend code

# Development
composer types            # Generate TypeScript types from models
composer ide             # Update IDE autocomplete
```

## 🐳 Deployment

### Docker

Ready to containerize with included Docker configuration. Requires [Docker Compose](https://docs.docker.com/compose/).

```bash
docker compose up
```

### Kubernetes

Production-ready Helm charts included for scalable deployments. Perfect for handling growth from small communities to large-scale platforms.

**Deploy with:**
- [DigitalOcean Kubernetes (DOKS)](docs/DIGITALOCEAN.md) - Complete setup guide for DOKS deployment
- [Amazon EKS](docs/AWS.md) - Complete setup guide for AWS EKS deployment
- [Google Kubernetes Engine (GKE)](docs/GCP.md) - Complete setup guide for GKE deployment

See [helm/README.md](helm/README.md) for detailed Kubernetes deployment documentation.

### Platforms

We are working on one-click deployment solutions across a range of cloud platforms. Use the links below to launch production-ready instances of Laravel Community.

[![Deploy on Railway](https://railway.com/button.svg)](https://railway.com/deploy/laravel-community?referralCode=O-oe8s&utm_medium=integration&utm_source=template&utm_campaign=generic)

See [docs/RAILWAY.md](docs/RAILWAY.md) for more information on hosting with Railway.

## 🤝 Contributing

We welcome contributions from the community! Whether it's bug fixes, new features, or documentation improvements, here's how to get started:

1. 🍴 Fork the repository
2. 🌿 Create a feature branch
3. 🔧 Make your changes
4. ✅ Run tests and quality checks
5. 📬 Submit a pull request

Check out our [Contributing Guide](.github/CONTRIBUTING.md) for detailed guidelines.

## 📖 Documentation

- [**CLAUDE.md**](CLAUDE.md) - Development guidelines and architecture overview
- [**Code of Conduct**](.github/CODE_OF_CONDUCT.md) - Community standards and expectations
- [**License**](LICENSE.md) - O'Saasy License for open source freedom

## 🔒 Security

Security is a top priority. We implement:

- Advanced fraud detection algorithms
- Threat monitoring and prevention
- Secure OAuth authentication flows
- Role-based access control
- Input validation and sanitization
- Protection against common vulnerabilities

Found a security issue? Please report it responsibly by contacting the maintainers directly.

## 📝 License

Laravel Community is open source software licensed under the [O'Saasy License](LICENSE.md).

## 💙 Built With

Modern technologies for a modern platform:

- **Backend** - Laravel 12 with PHP 8.4
- **Frontend** - React 19 with TypeScript
- **Styling** - Tailwind CSS v4 with beautiful UI components
- **Admin** - Filament v4 for powerful dashboards
- **Real-time** - Queue processing with Horizon
- **Testing** - Pest for elegant tests
- **Quality** - PHPStan, Pint, ESLint, Prettier

---

<div align="center">

**[Documentation](CLAUDE.md)** • **[Contributing](.github/CONTRIBUTING.md)** • **[License](LICENSE.md)**

Made with ❤️ by the Laravel Community

</div>
