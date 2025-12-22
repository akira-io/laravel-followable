# Configuration

Laravel Followable provides several configuration options to customize its behavior. This guide explains each configuration key and how to use them effectively.

## Configuration File

The configuration file is located at `config/followable.php`. If you haven't published it yet, do so with:

```bash
php artisan vendor:publish --tag="followable-config"
```

## Configuration Options

### `uuids`

**Type:** `boolean`  
**Default:** `false`

Determines whether to use UUIDs or auto-incrementing integers as primary keys for your followable models.

```php
'uuids' => false,
```

**When to use UUIDs:**

- Your application uses UUIDs globally for better security
- You need globally unique identifiers across distributed systems
- You want to prevent ID enumeration attacks
- You're building a multi-tenant application

**Example with UUIDs enabled:**

```php
// config/followable.php
'uuids' => true,
```

After changing this setting, you must create a new migration or modify the existing one before running it:

```php
// In your migration
Schema::create('followables', function (Blueprint $table) {
    if (config('followable.uuids')) {
        $table->uuid('id')->primary();
        $table->uuid('user_id')->index();
        $table->uuidMorphs('followable');
    } else {
        $table->id();
        $table->unsignedBigInteger('user_id')->index();
        $table->morphs('followable');
    }
    // ... rest of the schema
});
```

### `user_foreign_key`

**Type:** `string`  
**Default:** `'user_id'`

Specifies the name of the foreign key column that references your user model in the followables table.

```php
'user_foreign_key' => 'user_id',
```

**When to customize:**

- You've renamed the user foreign key in your database schema
- You're working with legacy databases with different naming conventions
- You need compatibility with existing database structures

**Example with custom foreign key:**

```php
// config/followable.php
'user_foreign_key' => 'follower_id',
```

After changing this setting, update your migration:

```php
// In your migration
$table->unsignedBigInteger(config('followable.user_foreign_key', 'user_id'))
    ->index()
    ->comment('user_id');
```

### `followables_table`

**Type:** `string`  
**Default:** `'followables'`

Defines the name of the table that stores follower relationships.

```php
'followables_table' => 'followables',
```

**When to customize:**

- You need a different table name to match your application's naming conventions
- You're integrating with an existing database schema
- You want to avoid table name conflicts

**Example with custom table name:**

```php
// config/followable.php
'followables_table' => 'user_follows',
```

After changing this setting, update your migration file name and the table name inside:

```php
// In your migration
Schema::create(config('followable.followables_table', 'followables'), function (Blueprint $table) {
    // ... schema definition
});
```

### `followables_model`

**Type:** `string`  
**Default:** `\Akira\Followable\Followable::class`

Specifies the fully qualified class name for the followables model. This allows you to extend the base model with custom functionality.

```php
'followables_model' => \Akira\Followable\Followable::class,
```

**When to customize:**

- You need to add custom methods to the Followable model
- You want to add additional relationships
- You need to override default model behavior
- You want to add custom query scopes

**Example with custom model:**

First, create your custom model:

```php
// app/Models/CustomFollowable.php
namespace App\Models;

use Akira\Followable\Followable as BaseFollowable;

class CustomFollowable extends BaseFollowable
{
    /**
     * Add a custom relationship
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Add a custom scope
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }

    /**
     * Override behavior
     */
    public function user()
    {
        return parent::user()->with('profile');
    }
}
```

Then update your configuration:

```php
// config/followable.php
'followables_model' => \App\Models\CustomFollowable::class,
```

## Complete Configuration Example

Here's a complete example configuration for an application using UUIDs and custom naming:

```php
<?php

use App\Models\CustomFollowable;

return [
    // Enable UUID primary keys
    'uuids' => true,

    // Custom foreign key name
    'user_foreign_key' => 'follower_id',

    // Custom table name
    'followables_table' => 'user_follows',

    // Custom model
    'followables_model' => CustomFollowable::class,
];
```

## Caching Configuration

After making changes to the configuration file, clear your configuration cache:

```bash
php artisan config:clear
```

In production, cache your configuration for better performance:

```bash
php artisan config:cache
```

## Environment-Specific Configuration

You can override configuration values in your `.env` file by publishing and modifying the config file to read from environment variables:

```php
// config/followable.php
return [
    'uuids' => env('FOLLOWABLE_UUIDS', false),
    'user_foreign_key' => env('FOLLOWABLE_USER_FK', 'user_id'),
    'followables_table' => env('FOLLOWABLE_TABLE', 'followables'),
    'followables_model' => env('FOLLOWABLE_MODEL', \Akira\Followable\Followable::class),
];
```

Then in your `.env`:

```
FOLLOWABLE_UUIDS=true
FOLLOWABLE_USER_FK=follower_id
FOLLOWABLE_TABLE=user_follows
```

## Best Practices

1. **Set UUIDs before migrating**: Decide on UUID usage before running your first migration to avoid complex data migrations later.

2. **Don't change foreign key names mid-project**: Changing `user_foreign_key` after you have data requires a careful migration.

3. **Test custom models thoroughly**: When extending the base `Followable` model, ensure all package features still work correctly.

4. **Document customizations**: Keep notes about why you customized certain values for future team members.

5. **Use environment variables**: For settings that might differ between environments, use `.env` variables.

## Accessing Configuration Values

Access configuration values in your code:

```php
// Get a configuration value
$tableName = config('followable.followables_table');
$usesUuids = config('followable.uuids');
$modelClass = config('followable.followables_model');

// Get with fallback
$userFk = config('followable.user_foreign_key', 'user_id');
```

---

**Previous:** [Installation](01-installation.md) | **Next:** [Database Schema](03-database-schema.md)
