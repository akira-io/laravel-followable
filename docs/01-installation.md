# Installation

This guide will walk you through installing and setting up Laravel Followable in your Laravel application.

## Requirements

Before installing Laravel Followable, ensure your environment meets the following requirements:

- **PHP**: 8.3 or higher
- **Laravel**: 11.x or 12.x
- **Database**: MySQL 5.7+, PostgreSQL 9.6+, SQLite 3.8+, or SQL Server 2017+

## Installing the Package

Install Laravel Followable via Composer:

```bash
composer require akira/laravel-followable
```

The package uses Laravel's auto-discovery feature, so the service provider and facade will be registered automatically.

## Publishing the Migration

Publish the migration file to your application's `database/migrations` directory:

```bash
php artisan vendor:publish --tag="followable-migrations"
```

This will create a migration file named `YYYY_MM_DD_HHMMSS_create_followable_table.php` in your migrations directory.

## Running the Migration

Execute the migration to create the `followables` table:

```bash
php artisan migrate
```

The migration creates a table with the following structure:

- `id` - Primary key
- `user_id` - Foreign key to users table
- `followable_id` - ID of the followed entity
- `followable_type` - Type of the followed entity (polymorphic)
- `accepted_at` - Timestamp when follow request was accepted (nullable)
- `created_at` - Timestamp when the follow was created
- `updated_at` - Timestamp when the follow was last updated

## Publishing the Configuration File (Optional)

If you need to customize the package behavior, publish the configuration file:

```bash
php artisan vendor:publish --tag="followable-config"
```

This creates a `config/followable.php` file in your application with the following default values:

```php
return [
    'uuids' => false,
    'user_foreign_key' => 'user_id',
    'followables_table' => 'followables',
    'followables_model' => \Akira\Followable\Followable::class,
];
```

See the [Configuration](02-configuration.md) documentation for detailed information about each option.

## Verifying Installation

After installation, verify that everything is set up correctly:

### 1. Check if the table exists

Run this artisan command to see your tables:

```bash
php artisan migrate:status
```

You should see the `create_followable_table` migration listed as "Ran".

### 2. Check the configuration

If you published the config file, inspect it:

```bash
php artisan config:show followable
```

### 3. Test in Tinker

Open Laravel Tinker and test the basic functionality:

```bash
php artisan tinker
```

```php
// Check if the Followable model can be loaded
use Akira\Followable\Followable;

Followable::count(); // Should return 0 if no follows exist yet
```

## Updating the Package

To update Laravel Followable to the latest version:

```bash
composer update akira/laravel-followable
```

After updating, always check the [CHANGELOG](../CHANGELOG.md) for any breaking changes or new features.

If there are new migrations, publish and run them:

```bash
php artisan vendor:publish --tag="followable-migrations" --force
php artisan migrate
```

## Uninstalling the Package

If you need to remove the package:

### 1. Rollback the migration

```bash
php artisan migrate:rollback
```

Or drop the table manually:

```php
Schema::dropIfExists('followables');
```

### 2. Remove the package

```bash
composer remove akira/laravel-followable
```

### 3. Clean up published files

Remove the published configuration file if it exists:

```bash
rm config/followable.php
```

Remove any published migration files from your `database/migrations` directory.

## Troubleshooting

### Migration Already Exists

If you receive an error that the migration already exists, you may have published it multiple times. Delete the
duplicate migration file(s) from your `database/migrations` directory.

### Table Already Exists

If the `followables` table already exists, either skip the migration or drop the existing table first:

```bash
php artisan db:wipe  # Warning: This drops ALL tables
# Or manually drop just the followables table
```

### Class Not Found

If you encounter "Class not found" errors, ensure you've run:

```bash
composer dump-autoload
php artisan config:clear
php artisan cache:clear
```

## Next Steps

Now that you've installed Laravel Followable, you can:

1. Learn about [Configuration](02-configuration.md) options
2. Explore the [Database Schema](03-database-schema.md)
3. Get started with [Basic Usage](04-basic-usage.md)

---

**Previous:** [Roadmap](00-roadmap.md) | **Next:** [Configuration](02-configuration.md)
