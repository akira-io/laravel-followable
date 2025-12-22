# Database Schema

This guide explains the database structure used by Laravel Followable, including the table schema, indexes, and relationships.

## The `followables` Table

Laravel Followable uses a single polymorphic table to store all follow relationships. By default, this table is named `followables`, but you can customize it in the configuration.

### Table Structure

The migration creates the following schema:

```php
Schema::create('followables', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('user_id')->index()->comment('user_id');
    $table->morphs('followable');
    $table->timestamp('accepted_at')->nullable();
    $table->timestamps();
    
    $table->index(['followable_type', 'accepted_at']);
});
```

### Column Descriptions

#### `id`

- **Type:** `BIGINT UNSIGNED` (or `UUID` if configured)
- **Purpose:** Primary key for the follow relationship
- **Auto-increment:** Yes (unless UUIDs are enabled)

#### `user_id`

- **Type:** `BIGINT UNSIGNED` (or `UUID` if configured)
- **Purpose:** References the user who is following (the follower)
- **Nullable:** No
- **Indexed:** Yes
- **Configurable:** Column name can be changed via `user_foreign_key` config

This column stores the ID of the user performing the follow action.

#### `followable_id`

- **Type:** `BIGINT UNSIGNED` (or `UUID` if configured)
- **Purpose:** References the ID of the entity being followed
- **Nullable:** No
- **Indexed:** Yes (composite index with `followable_type`)

This is the polymorphic foreign key that can reference any model using the `Followable` trait.

#### `followable_type`

- **Type:** `VARCHAR(255)`
- **Purpose:** Stores the fully qualified class name of the followed entity
- **Nullable:** No
- **Indexed:** Yes (composite index with `followable_id`)

Examples: `App\Models\User`, `App\Models\Post`, etc.

#### `accepted_at`

- **Type:** `TIMESTAMP`
- **Purpose:** Records when a follow request was accepted
- **Nullable:** Yes
- **Indexed:** Yes (composite index with `followable_type`)

- `NULL` = Follow request is pending approval
- `NOT NULL` = Follow request has been accepted

#### `created_at`

- **Type:** `TIMESTAMP`
- **Purpose:** Records when the follow relationship was created
- **Nullable:** No (default)

#### `updated_at`

- **Type:** `TIMESTAMP`
- **Purpose:** Records when the follow relationship was last modified
- **Nullable:** No (default)

### Indexes

The table includes three indexes for optimal query performance:

1. **`user_id` index**: Speeds up queries for "users a specific user follows"
2. **`followable_id` and `followable_type` composite index**: Speeds up polymorphic queries
3. **`followable_type` and `accepted_at` composite index**: Optimizes queries filtering by accepted follows

## UUID Support

If you enable UUIDs in the configuration, the migration uses UUID columns instead:

```php
'uuids' => true,
```

The migration automatically adapts:

```php
if (config('followable.uuids')) {
    $table->uuid('id')->primary();
    $table->uuid('user_id')->index();
    $table->uuidMorphs('followable');
} else {
    $table->id();
    $table->unsignedBigInteger('user_id')->index();
    $table->morphs('followable');
}
```

## Relationships

### Polymorphic Relationship

The `followable_id` and `followable_type` columns create a polymorphic relationship, allowing any model to be followed:

```php
// Users can follow users
User -> followables -> User

// Users can follow posts
User -> followables -> Post

// Users can follow any model with the Followable trait
User -> followables -> AnyModel
```

### Foreign Key Constraints

The package does **not** add explicit foreign key constraints by default. This design decision allows:

- Following models across different databases
- Soft deletes without cascade issues
- Greater flexibility with polymorphic relationships

If you need foreign key constraints, you can add them manually in a new migration:

```php
Schema::table('followables', function (Blueprint $table) {
    $table->foreign('user_id')
        ->references('id')
        ->on('users')
        ->onDelete('cascade');
});
```

## Customizing the Schema

### Custom Table Name

Change the table name in the configuration:

```php
'followables_table' => 'user_follows',
```

Then update your migration:

```php
Schema::create(config('followable.followables_table'), function (Blueprint $table) {
    // ... schema definition
});
```

### Custom User Foreign Key

Change the foreign key column name:

```php
'user_foreign_key' => 'follower_id',
```

Then update your migration:

```php
$table->unsignedBigInteger(config('followable.user_foreign_key'))
    ->index()
    ->comment('user_id');
```

### Adding Custom Columns

You can extend the schema by creating a new migration:

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('followables', function (Blueprint $table) {
            $table->text('note')->nullable()->after('accepted_at');
            $table->string('source')->default('web')->after('note');
        });
    }

    public function down(): void
    {
        Schema::table('followables', function (Blueprint $table) {
            $table->dropColumn(['note', 'source']);
        });
    }
};
```

## Example Data

Here's what the data looks like in the table:

| id | user_id | followable_id | followable_type | accepted_at | created_at | updated_at |
|----|---------|---------------|-----------------|-------------|------------|------------|
| 1  | 5       | 10            | App\Models\User | 2025-01-15 08:30:00 | 2025-01-15 08:30:00 | 2025-01-15 08:30:00 |
| 2  | 5       | 12            | App\Models\User | NULL | 2025-01-16 10:15:00 | 2025-01-16 10:15:00 |
| 3  | 8       | 5             | App\Models\User | 2025-01-17 14:20:00 | 2025-01-17 14:20:00 | 2025-01-17 14:20:00 |
| 4  | 5       | 42            | App\Models\Post | 2025-01-17 16:45:00 | 2025-01-17 16:45:00 | 2025-01-17 16:45:00 |

**Interpretation:**

- Row 1: User 5 follows User 10 (accepted)
- Row 2: User 5 requested to follow User 12 (pending - `accepted_at` is NULL)
- Row 3: User 8 follows User 5 (accepted)
- Row 4: User 5 follows Post 42 (accepted)

## Performance Considerations

### Query Optimization

The package includes strategic indexes for common queries:

```php
// Optimized: Uses user_id index
$user->followings()->get();

// Optimized: Uses followable_id + followable_type index
$user->followers()->get();

// Optimized: Uses followable_type + accepted_at index
$approvedFollowers = $followable->approvedFollowers()->get();
```

### Eager Loading

Always eager load relationships to avoid N+1 queries:

```php
// Bad: N+1 problem
$users = User::all();
foreach ($users as $user) {
    $followersCount = $user->followers()->count(); // Individual query for each user
}

// Good: Single query with join
$users = User::withCount('followers')->get();
```

### Pagination

For large datasets, use pagination:

```php
$followers = $user->followers()->paginate(20);
$followings = $user->followings()->paginate(20);
```

## Database Maintenance

### Cleaning Up Orphaned Records

If you delete models without cascade constraints, you may have orphaned follow records:

```php
use Akira\Followable\Followable;

// Remove follows for deleted users
Followable::whereDoesntHave('user')->delete();

// Remove follows for deleted followable entities (users)
Followable::where('followable_type', User::class)
    ->whereNotIn('followable_id', User::pluck('id'))
    ->delete();
```

### Analyzing Follow Statistics

```php
// Most followed users
DB::table('followables')
    ->select('followable_id', DB::raw('count(*) as followers_count'))
    ->where('followable_type', 'App\Models\User')
    ->whereNotNull('accepted_at')
    ->groupBy('followable_id')
    ->orderByDesc('followers_count')
    ->limit(10)
    ->get();

// Pending follow requests count
DB::table('followables')
    ->whereNull('accepted_at')
    ->count();
```

---

**Previous:** [Configuration](02-configuration.md) | **Next:** [Basic Usage](04-basic-usage.md)
