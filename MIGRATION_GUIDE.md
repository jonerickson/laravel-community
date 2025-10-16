# Migration System Guide

This guide explains how to use the migration system to import data from external sources.

## Overview

The migration system supports importing data from multiple sources, with built-in support for:
- Invision Community

The system is designed to be extensible, allowing you to add new sources and entity importers as needed.

## Configuration

### Invision Community

Add the following environment variables to your `.env` file:

```env
# Invision Community Migration Configuration
MIGRATION_IC_DRIVER=mysql          # Database driver (mysql or pgsql)
MIGRATION_IC_HOST=127.0.0.1       # Database host
MIGRATION_IC_PORT=3306            # Database port
MIGRATION_IC_DATABASE=invision    # Database name
MIGRATION_IC_USERNAME=root        # Database username
MIGRATION_IC_PASSWORD=            # Database password
MIGRATION_IC_PREFIX=              # Table prefix (if any)
MIGRATION_IC_CHARSET=utf8mb4      # Character set
```

## Usage

### Basic Migration

Migrate all entities from a source:

```bash
php artisan mi:migrate invision-community
```

### Migrate Specific Entity

Migrate only users:

```bash
php artisan mi:migrate invision-community --entity=users
```

### Dry Run

Preview what would be migrated without making changes:

```bash
php artisan mi:migrate invision-community --dry-run
```

### Custom Batch Size

Process records in batches of 50:

```bash
php artisan mi:migrate invision-community --batch-size=50
```

### Interactive Mode

If you don't specify a source, you'll be prompted to select one:

```bash
php artisan mi:migrate
```

## Supported Entities

### Invision Community

- **groups** - Migrates user groups, including:
  - Group name
  - Description
  - Color (extracted from group prefix)
  - Automatically maps source group IDs to target group IDs for user assignment

- **users** - Migrates user accounts, including:
  - Name
  - Email
  - Email verification status
  - Signature
  - Last seen timestamp
  - Account creation date
  - Password (replaced with random hash for security)
  - Primary group (member_group_id)
  - Secondary groups (mgroup_others)

## Architecture

The migration system consists of several components:

### Core Components

- `MigrationService` - Orchestrates the migration process and dependency resolution
- `MigrationResult` - Tracks migration statistics
- `MigrationSource` - Interface for migration sources
- `EntityImporter` - Interface for entity importers
- `ImporterDependency` - Defines dependencies between entities

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

- **Dependency Resolution** - Required dependencies are automatically migrated in the correct order
- **Optional Dependencies** - User is prompted to select which optional dependencies to include
- **Duplicate Detection** - Records are checked to prevent duplicates (users by email, groups by name)
- **Skipped Records** - Existing records are skipped and counted
- **Failed Records** - Errors are caught and counted without stopping the migration
- **Progress Tracking** - A progress bar shows real-time migration progress
- **Dry Run Mode** - Test migrations without modifying the database
- **ID Mapping** - Source IDs are mapped to target IDs using cache for relationship preservation

## Notes

- Passwords from external sources are replaced with random hashes for security
- Users will need to use password reset to set a new password
- HTML content is stripped from signatures
- Email verification status is preserved when possible
- All timestamps are converted to Carbon instances