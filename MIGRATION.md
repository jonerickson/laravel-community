# Migration System Guide

This guide explains how to use the migration system to import data from external sources into Laravel Community.

## Overview

The migration system is a sophisticated, extensible data migration framework with built-in support for:
- **Invision Community** - Complete suite migration including forums, blogs, commerce, and users

### Available Entities

The following entities can be migrated from Invision Community:
- `groups` - User groups and roles
- `users` - User accounts and profiles
- `blogs` - Blog entries
- `blog_comments` - Blog post comments
- `products` - Store products
- `subscriptions` - Subscription plans
- `user_subscriptions` - User subscription purchases
- `forums` - Forum categories
- `topics` - Forum topics/threads
- `posts` - Forum posts/replies
- `orders` - Purchase orders

### Requirements

- **Cache Driver**: Must support tags (redis, memcached, or dynamodb)
- **Database**: MySQL or PostgreSQL
- **SSH**: Key-based authentication only (for SSH tunnels)

### Key Features
- Parallel processing with concurrent workers
- Automatic dependency resolution
- SSH tunnel support for secure remote connections
- Media file downloading and processing
- Comprehensive error handling and progress tracking
- Dry run mode for testing
- Cache-based ID mapping for relationship preservation

The system is designed to be extensible, allowing you to add new sources and entity importers as needed.

## Configuration

### Invision Community

#### Database Connection

Add a database connection in `config/database.php`:

```php
'invision_community' => [
    'driver' => env('MIGRATION_IC_DRIVER', 'mysql'),
    'host' => env('MIGRATION_IC_HOST', '127.0.0.1'),
    'port' => env('MIGRATION_IC_PORT', env('MIGRATION_IC_DRIVER', 'mysql') === 'pgsql' ? '5432' : '3306'),
    'database' => env('MIGRATION_IC_DATABASE'),
    'username' => env('MIGRATION_IC_USERNAME'),
    'password' => env('MIGRATION_IC_PASSWORD'),
    'charset' => env('MIGRATION_IC_CHARSET', env('MIGRATION_IC_DRIVER', 'mysql') === 'pgsql' ? 'utf8' : 'utf8mb4'),
    'collation' => env('MIGRATION_IC_COLLATION', env('MIGRATION_IC_DRIVER', 'mysql') === 'pgsql' ? null : 'utf8mb4_unicode_ci'),
    'prefix' => env('MIGRATION_IC_PREFIX', ''),
    'strict' => false,
],
```

Add the corresponding environment variables to your `.env` file:

```env
# Invision Community Database
MIGRATION_IC_DRIVER=mysql
MIGRATION_IC_HOST=127.0.0.1
MIGRATION_IC_PORT=3306
MIGRATION_IC_DATABASE=invision
MIGRATION_IC_USERNAME=root
MIGRATION_IC_PASSWORD=
```

#### SSH Tunnel (Optional)

For secure remote database access, configure SSH tunneling in `config/migration.php`:

```php
'sources' => [
    'invision_community' => [
        'ssh' => [
            'host' => env('MIGRATION_IC_SSH_HOST'),
            'user' => env('MIGRATION_IC_SSH_USER'),
            'port' => env('MIGRATION_IC_SSH_PORT', 22),
            'key' => env('MIGRATION_IC_SSH_KEY'),
        ],
    ],
],
```

Add these environment variables:

```env
# SSH Tunnel Configuration
MIGRATION_IC_SSH_HOST=your-server.com
MIGRATION_IC_SSH_USER=your-username
MIGRATION_IC_SSH_PORT=22
MIGRATION_IC_SSH_KEY=/path/to/private/key
```

#### Media Downloads

To download and migrate media files (images, attachments), set the base URL:

```env
IC_BASE_URL=https://community.example.com
```

This is required for:
- Blog cover photos
- Forum attachments
- Product featured images
- Product category images
- Forum category icons

## Usage

### Basic Commands

#### Basic Migration

Migrate all entities from a source:

```bash
php artisan app:migrate invision-community
```

#### Migrate Specific Entity

Migrate only users:

```bash
php artisan app:migrate invision-community --entity=users
```

#### Dry Run

Preview what would be migrated without making changes:

```bash
php artisan app:migrate invision-community --dry-run
```

#### Custom Batch Size

Process records in batches of 500:

```bash
php artisan app:migrate invision-community --batch=500
```

#### Limit and Offset

Migrate a specific range of records:

```bash
php artisan app:migrate invision-community --entity=posts --limit=10000 --offset=5000
```

#### Filter by User ID

Migrate data for a specific user:

```bash
php artisan app:migrate invision-community --entity=orders --id=12345
```

### Advanced Options

#### Parallel Processing

Enable concurrent worker processes for faster migrations:

```bash
php artisan app:migrate invision-community --entity=posts --parallel
```

Configure parallelization:

```bash
php artisan app:migrate invision-community \
    --entity=posts \
    --parallel \
    --max-processes=8 \
    --max-records-per-process=2000
```

Options:
- `--max-processes`: Maximum concurrent workers (default: 8)
- `--max-records-per-process`: Records per worker (default: 1000)

#### SSH Tunnel

Use SSH tunnel for remote database access:

```bash
php artisan app:migrate invision-community --ssh
```

#### Check Database Connection

Verify database connection without starting migration:

```bash
php artisan app:migrate invision-community --check
```

#### View Migration Status

Display record counts for each entity before migrating:

```bash
php artisan app:migrate invision-community --status
```

For a specific entity:

```bash
php artisan app:migrate invision-community --entity=users --status
```

#### Exclude Entities

Exclude specific entities from migration:

```bash
php artisan app:migrate invision-community --excluded=blog_comments,orders
```

#### Disable Media Downloads

Skip downloading media files:

```bash
php artisan app:migrate invision-community --media=0
```

#### Disable Cleanup

Prevent automatic cleanup after migration:

```bash
php artisan app:migrate invision-community --cleanup=0
```

### Interactive Mode

If you don't specify a source, you'll be prompted to select one:

```bash
php artisan app:migrate
```

## Architecture

The migration system consists of several components working together to provide a robust, scalable migration framework:

### Core Components

#### **MigrationService**
Central orchestrator that manages migration sources and coordinates import operations.

**Responsibilities:**
- Register and manage migration sources
- Configure migration parameters
- Execute migrations with automatic dependency resolution
- Handle optional dependency prompts
- Prepare environment (disable query logs, configure logging)

**Key Methods:**
- `registerSource()` - Register a new migration source
- `configure()` - Set migration configuration
- `migrate()` - Execute migration with dependency resolution
- `getOptionalDependencies()` - Retrieve optional dependencies for user selection

---

#### **ConcurrentMigrationManager**
Advanced parallel processing manager for high-performance migrations.

**Features:**
- Spawn multiple worker processes simultaneously
- Real-time output streaming from workers
- Color-coded console output per worker
- Automatic process monitoring and failure detection
- Memory limit enforcement per worker
- Graceful termination handling

**Configuration:**
- `maxProcesses` - Maximum concurrent workers (default: 8)
- `maxRecordsPerProcess` - Records per worker (default: 1000)
- `workerMemoryLimit` - Memory limit per worker (auto-calculated)

**Worker Management:**
- Spawns: `php artisan app:migrate [source] --entity=X --offset=Y --limit=Z --worker`
- Monitors exit codes (137 = out of memory)
- Tracks completed, failed, and active chunks
- Streams stdout/stderr in real-time

---

#### **MigrationConfig**
Configuration value object for migration operations.

**Available Options:**
- `entities` - Array of entities to migrate
- `batchSize` - Records per batch (default: 1000)
- `limit` - Total records limit
- `offset` - Starting offset
- `userId` - Filter by user ID
- `isDryRun` - Preview mode (default: false)
- `useSsh` - Use SSH tunnel (default: false)
- `downloadMedia` - Download media files (default: true)
- `baseUrl` - Source base URL for media
- `parallel` - Enable parallel processing (default: false)
- `maxRecordsPerProcess` - Records per worker (default: 1000)
- `maxProcesses` - Concurrent workers (default: 8)
- `memoryLimit` - Worker memory limit (auto-calculated)
- `excluded` - Array of entities to exclude

---

#### **MigrationResult**
Tracks migration statistics and stores detailed records using cache.

**Features:**
- Migrated, skipped, and failed counts per entity
- Detailed records stored in cache with tags
- 7-day TTL for cached records
- Table-formatted output support

**Methods:**
- `addEntity()` - Add entity statistics
- `recordMigrated/Skipped/Failed()` - Store detailed records in cache
- `toTableRows()` - Generate summary table

---

#### **ImporterDependency**
Defines relationships between importers with type safety.

**Dependency Types:**
- **Pre-dependencies**: Must run before the importer
- **Post-dependencies**: Must run after the importer
- **Required**: Mandatory for operation
- **Optional**: User can choose to include

**Factory Methods:**
```php
ImporterDependency::requiredPre('users', 'Description')
ImporterDependency::optionalPre('groups', 'Description')
ImporterDependency::requiredPost('orders', 'Description')
ImporterDependency::optionalPost('subscriptions', 'Description')
```

---

#### **AbstractImporter**
Base class providing shared functionality for all importers.

**Features:**
- Media download and processing
- HTML image parsing and URL replacement
- File extension detection
- Random filename generation
- Error logging with stack traces
- Cache management (7-day TTL)

**Key Methods:**
- `downloadAndStoreFile()` - Download and store remote files
- `parseAndReplaceImagesInHtml()` - Parse HTML, download images, replace URLs

---

### Contracts (Interfaces)

#### **MigrationSource Contract**
```php
public function getName(): string;
public function getConnection(): string;
public function getImporters(): array;
public function getImporter(string $entity): ?EntityImporter;
public function getSshConfig(): ?array;
public function getBaseUrl(): ?string;
public function setBaseUrl(?string $url): void;
public function cleanup(): void;
```

#### **EntityImporter Contract**
```php
public function getEntityName(): string;
public function getSourceTable(): string;
public function getDependencies(): array;
public function import(MigrationConfig $config, MigrationResult $result, OutputStyle $output): void;
public function isCompleted(): bool;
public function markCompleted(): void;
public function cleanup(): void;
```

### Dependency System

The migration system supports automatic dependency resolution. Importers can declare dependencies that will be automatically handled:

- **Required Pre-Dependencies** - Entities that must be migrated before the current entity (e.g., groups must exist before users)
- **Optional Pre-Dependencies** - Entities that can optionally be migrated first (user is prompted)
- **Required Post-Dependencies** - Entities that must be migrated after the current entity
- **Optional Post-Dependencies** - Entities that can optionally be migrated after (user is prompted)

When you migrate users, the system will:
1. Automatically migrate groups first (required pre-dependency)
2. Then migrate users
3. Assign users to their groups using cached ID mappings

Example in code:
```php
public function getDependencies(): array
{
    return [
        ImporterDependency::requiredPre('groups', 'Users require groups for role assignment'),
        ImporterDependency::optionalPost('posts', 'Optionally migrate user posts'),
    ];
}
```

### Adding New Sources

1. Create a new source class in `app/Services/Migration/Sources/YourSource/`
2. Implement the `MigrationSource` interface
3. Create importers in `app/Services/Migration/Sources/YourSource/Importers/`
4. Register the source in `MigrationServiceProvider`

### Adding New Entities

1. Create a new importer class implementing `EntityImporter`
2. Implement the `getDependencies()` method to declare dependencies
3. Add the importer to your source's `getImporters()` method

## Migration Behavior

### Dependency Management
- **Automatic Dependency Resolution** - Required dependencies are automatically migrated in the correct order
- **Optional Dependencies** - User is prompted to select which optional dependencies to include
- **Dependency Graph Execution** - Complex dependency chains are resolved automatically

### Data Processing
- **Batch Processing** - Records processed in configurable batches (default: 1000)
- **Offset & Limit Support** - Resume migrations or migrate specific ranges
- **User Filtering** - Filter migrations by specific user ID
- **Duplicate Detection** - Records checked to prevent duplicates (users by email, groups by name, etc.)
- **Skipped Records** - Existing records are skipped and counted
- **Failed Records** - Errors caught and counted without stopping the migration

### Progress & Feedback
- **Progress Tracking** - Real-time progress bar with percentage and ETA
- **Verbose Logging** - Detailed logging with `-vvv` flag
- **Statistics Summary** - Table-formatted summary showing migrated/skipped/failed counts
- **Worker Output** - Color-coded output when using parallel processing

### Special Modes
- **Dry Run Mode** - Preview what would be migrated without making changes
  - Skips database writes
  - Skips file downloads
  - Shows detailed statistics
  - Does not mark entities as completed
- **Parallel Processing** - Spawns multiple workers for faster migrations
  - Configurable worker count
  - Automatic load distribution
  - Memory limit enforcement
  - Real-time output streaming

### Cache Management
- **ID Mapping** - Source IDs mapped to target IDs using cache for relationship preservation
- **7-Day TTL** - All ID mappings cached for 7 days
- **Cache Tags** - Organized by source and entity for easy cleanup
- **Completion Tracking** - Entity completion status stored in cache

### Media Processing
- **Configurable Downloads** - Enable/disable media downloads per migration
- **Base URL Validation** - Requires valid base URL for media downloads
- **Random Filenames** - 40-character random names prevent collisions
- **Extension Detection** - Automatic file extension detection from MIME type
- **HTML Image Parsing** - Downloads embedded images and updates URLs
- **Error Handling** - Continues on failed downloads with logging

### Completion Behavior
- **Completion Tracking** - Entities marked completed after full migration
- **Partial Migrations** - Using limit/offset prevents completion marking (enables resumable migrations)
- **Worker Mode** - Worker processes never mark entities completed

## Important Notes

### Security
- **Password Migration** - Passwords replaced with random hashes for security
- **Password Reset Required** - Users must use password reset to set new password
- **SSH Tunnel Support** - Secure remote database access via SSH tunneling

### Data Handling
- **HTML Content** - HTML preserved in content fields, stripped from signatures
- **Email Verification** - Email verification status preserved when possible (from bit flags)
- **Timestamps** - All timestamps converted to Carbon instances
- **Language Resolution** - Invision Community language keys resolved automatically (cached 1 hour)
- **Hierarchical Data** - Two-phase imports handle parent relationships correctly
- **Default Values** - Falls back to admin user when author not found

### Performance
- **Query Log Disabled** - Query logging disabled during migration for performance
- **Batch Processing** - Prevents memory exhaustion on large datasets
- **Parallel Workers** - Up to 8+ concurrent workers supported
- **Memory Limits** - Worker memory limits prevent OOM crashes

### Caching
- **Cache Duration** - All migration caches last 7 days
- **ID Mappings** - Essential for preserving relationships across migrations
- **Language Cache** - Language translations cached for 1 hour
- **Cleanup** - Use `cleanup()` methods to clear caches

### Media Files
- **Storage Location** - All files stored in `storage/app/public/`
- **Public Visibility** - Downloaded files have public visibility
- **Subdirectories** - Organized by entity type (post-images/, products/featured-images/, etc.)
- **Fallback Handling** - Migration continues if media download fails

### Error Handling
- **Record-Level Errors** - Individual record failures don't stop batch
- **Detailed Logging** - Full stack traces logged with file/line numbers
- **Exit Codes** - Worker exit code 137 indicates out of memory
- **Graceful Failures** - System recovers and continues processing

### Limitations
- **Completion Tracking** - Entities not marked completed when using limit/offset
- **Worker Mode** - Must be spawned by ConcurrentMigrationManager
- **SSH Requirements** - SSH key-based authentication only (no password auth)
- **Database Support** - MySQL and PostgreSQL supported
