# Followable Model

The `Followable` model represents the pivot table records that store follow relationships. This guide explains the model's structure, relationships, and query scopes.

## Overview

The `Followable` model is located at `Akira\Followable\Followable` and extends Eloquent's `Model` class. It serves as both a pivot model and a standalone model for querying follow relationships.

```php
use Akira\Followable\Followable;

// Query follow records directly
$follows = Followable::where('followable_type', User::class)->get();
```

## Model Properties

### Table Name

The table name is configurable via the `followables_table` configuration key:

```php
// config/followable.php
'followables_table' => 'followables',
```

The model automatically uses this configuration in its constructor.

### Fillable Attributes

```php
protected $fillable = [
    'user_id',
    'followable_id',
    'followable_type',
    'accepted_at',
];
```

### Casts

```php
protected function casts(): array
{
    return [
        'accepted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

### Dispatched Events

The model automatically dispatches events on creation and deletion:

```php
protected $dispatchesEvents = [
    'created' => Followed::class,
    'deleted' => UnFollowed::class,
];
```

See the [Events](09-events.md) documentation for more details.

## Relationships

### `followable(): MorphTo`

Get the model being followed (polymorphic relationship).

**Returns:**
- `MorphTo` - The followed model

**Example:**

```php
$follow = Followable::first();

// Get the followed entity
$followed = $follow->followable;

if ($followed instanceof User) {
    echo "Following user: {$followed->name}";
} elseif ($followed instanceof Post) {
    echo "Following post: {$followed->title}";
}
```

### `user(): BelongsTo`

Get the user who created the follow (the follower).

**Returns:**
- `BelongsTo` - The user model

**Example:**

```php
$follow = Followable::first();
$follower = $follow->user;

echo "{$follower->name} is following something";
```

### `follower(): BelongsTo`

Alias for `user()` relationship. Returns the same data with a more semantic name.

**Returns:**
- `BelongsTo` - The user model

**Example:**

```php
$follow = Followable::first();
$follower = $follow->follower;

echo "Follower: {$follower->name}";
```

## Query Scopes

### `scopeWithType(Builder $query, string $type): Builder`

Filter follows by the type of entity being followed.

**Parameters:**
- `$query` - Query builder instance
- `$type` - Fully qualified class name

**Example:**

```php
use App\Models\User;
use App\Models\Post;

// Get all user follows
$userFollows = Followable::withType(User::class)->get();

// Get all post follows
$postFollows = Followable::withType(Post::class)->get();

// Count follows by type
$userFollowCount = Followable::withType(User::class)->count();
```

### `scopeOf(Builder $query, Model $model): Builder`

Filter follows of a specific model instance.

**Parameters:**
- `$query` - Query builder instance
- `$model` - The specific model to filter by

**Example:**

```php
$user = User::find(1);

// Get all follows of this specific user
$followsOfUser = Followable::of($user)->get();

// Count followers of this user
$followerCount = Followable::of($user)->count();

// Get pending follows of this user
$pendingFollows = Followable::of($user)->notAccepted()->get();
```

### `scopeFollowedBy(Builder $query, Model $follower): Builder`

Filter follows created by a specific user.

**Parameters:**
- `$query` - Query builder instance
- `$follower` - The user who created the follows

**Example:**

```php
$user = User::find(1);

// Get all follows created by this user
$follows = Followable::followedBy($user)->get();

// Get what types of entities this user follows
$types = Followable::followedBy($user)
    ->select('followable_type')
    ->distinct()
    ->pluck('followable_type');

// Count how many entities this user follows
$count = Followable::followedBy($user)->accepted()->count();
```

### `scopeAccepted(Builder $query): Builder`

Filter to only accepted follows.

**Example:**

```php
// Get all accepted follows
$acceptedFollows = Followable::accepted()->get();

// Count accepted follows for a specific user
$acceptedCount = Followable::of($user)->accepted()->count();

// Get recently accepted follows
$recentAccepted = Followable::accepted()
    ->where('accepted_at', '>=', now()->subDays(7))
    ->get();
```

### `scopeNotAccepted(Builder $query): Builder`

Filter to only pending (not accepted) follows.

**Example:**

```php
// Get all pending follows
$pendingFollows = Followable::notAccepted()->get();

// Count pending follows for a specific user
$pendingCount = Followable::of($user)->notAccepted()->count();

// Get oldest pending follows
$oldestPending = Followable::notAccepted()
    ->oldest('created_at')
    ->take(10)
    ->get();
```

## Combining Scopes

Scopes can be chained together for powerful queries:

```php
$user = User::find(1);
$follower = User::find(2);

// Check if follower has an accepted follow of user
$hasAcceptedFollow = Followable::of($user)
    ->followedBy($follower)
    ->accepted()
    ->exists();

// Get all pending user-to-user follows
$pendingUserFollows = Followable::withType(User::class)
    ->notAccepted()
    ->with(['user', 'followable'])
    ->get();

// Get accepted follows created today
$todaysFollows = Followable::accepted()
    ->whereDate('created_at', today())
    ->get();
```

## Automatic User ID Assignment

The model automatically sets the `user_id` from the authenticated user if not provided:

```php
// In the model's boot method
self::saving(function (Followable $follower): void {
    $userForeignKey = config('followable.user_foreign_key', 'user_id');
    $follower->setAttribute(
        $userForeignKey, 
        $follower->{$userForeignKey} ?: auth()->id()
    );
    
    // UUID support
    if (config('followable.uuids')) {
        $follower->setAttribute(
            $follower->getKeyName(), 
            $follower->{$follower->getKeyName()} ?: (string) Str::orderedUuid()
        );
    }
});
```

## Direct Model Usage

While you typically interact with follows through the `Follower` and `Followable` traits, you can work directly with the model:

### Creating Follows Manually

```php
use Akira\Followable\Followable;

$follow = Followable::create([
    'user_id' => 1,
    'followable_id' => 2,
    'followable_type' => User::class,
    'accepted_at' => now(), // Or null for pending
]);
```

### Updating Follows

```php
// Accept a pending follow
$follow = Followable::where('user_id', 1)
    ->where('followable_id', 2)
    ->first();

$follow->update(['accepted_at' => now()]);

// Or bulk update
Followable::where('followable_id', $userId)
    ->notAccepted()
    ->update(['accepted_at' => now()]);
```

### Deleting Follows

```php
// Delete a specific follow
$follow->delete();

// Delete all follows of a user
Followable::of($user)->delete();

// Delete all pending follows
Followable::notAccepted()
    ->where('created_at', '<', now()->subDays(30))
    ->delete();
```

## Querying Follow Statistics

### Overall Statistics

```php
// Total follows in system
$totalFollows = Followable::count();

// Accepted vs pending
$accepted = Followable::accepted()->count();
$pending = Followable::notAccepted()->count();

// Follows by type
$followsByType = Followable::selectRaw('followable_type, count(*) as count')
    ->groupBy('followable_type')
    ->get();
```

### User Statistics

```php
// Most active followers (users who follow the most)
$topFollowers = Followable::selectRaw('user_id, count(*) as follows_count')
    ->accepted()
    ->groupBy('user_id')
    ->orderByDesc('follows_count')
    ->limit(10)
    ->with('user')
    ->get();

// Most followed users
$mostFollowed = Followable::selectRaw('followable_id, count(*) as followers_count')
    ->where('followable_type', User::class)
    ->accepted()
    ->groupBy('followable_id')
    ->orderByDesc('followers_count')
    ->limit(10)
    ->get();
```

### Time-Based Statistics

```php
// Follows created today
$todayFollows = Followable::whereDate('created_at', today())->count();

// Accepted follows this week
$weeklyAccepted = Followable::accepted()
    ->where('accepted_at', '>=', now()->subWeek())
    ->count();

// Follow trends (daily counts for last 30 days)
$trends = Followable::selectRaw('DATE(created_at) as date, count(*) as count')
    ->where('created_at', '>=', now()->subDays(30))
    ->groupBy('date')
    ->orderBy('date')
    ->get();
```

## Advanced Queries

### Find Mutual Follows

```php
$user1 = User::find(1);
$user2 = User::find(2);

$user1FollowsUser2 = Followable::followedBy($user1)
    ->of($user2)
    ->accepted()
    ->exists();

$user2FollowsUser1 = Followable::followedBy($user2)
    ->of($user1)
    ->accepted()
    ->exists();

$areMutual = $user1FollowsUser2 && $user2FollowsUser1;
```

### Find Users Who Follow Multiple Targets

```php
$targetUsers = User::whereIn('id', [1, 2, 3])->get();

// Users who follow all targets
$usersWhoFollowAll = User::whereHas('followings', function ($query) use ($targetUsers) {
    $query->whereIn('followable_id', $targetUsers->pluck('id'))
        ->where('followable_type', User::class)
        ->accepted();
}, '=', $targetUsers->count())->get();
```

### Cleanup Orphaned Records

```php
// Remove follows for deleted users
Followable::whereDoesntHave('user')->delete();

// Remove follows for deleted followable entities
Followable::where('followable_type', User::class)
    ->whereNotIn('followable_id', User::pluck('id'))
    ->delete();
```

---

**Previous:** [Approval Workflow](07-approval-workflow.md) | **Next:** [Events](09-events.md)
