# Relationships

Laravel Followable provides several Eloquent relationships for accessing follow data. This guide explains all available relationships and how to use them effectively.

## Relationships on Follower Trait

Models with the `Follower` trait have access to these relationships:

### `followings()`

**Type:** `HasMany`  
**Related Model:** `Followable`  
**Returns:** All follow records created by this model (both pending and accepted)

```php
$user = User::find(1);

// Get all followings
$followings = $user->followings;

// With query builder
$recentFollowings = $user->followings()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// Count
$count = $user->followings()->count();

// Eager load
$users = User::with('followings')->get();
```

### `approvedFollowings()`

**Type:** `HasMany`  
**Related Model:** `Followable`  
**Returns:** Only accepted follow records

```php
// Get approved followings
$approved = $user->approvedFollowings;

// Only accepted follows
$acceptedUsers = $user->approvedFollowings()
    ->with('followable')
    ->get()
    ->pluck('followable');

// Count approved
$approvedCount = $user->approvedFollowings()->count();
```

### `notApprovedFollowings()`

**Type:** `HasMany`  
**Related Model:** `Followable`  
**Returns:** Only pending follow records

```php
// Get pending followings
$pending = $user->notApprovedFollowings;

// Show pending requests
foreach ($user->notApprovedFollowings as $following) {
    echo "Waiting for {$following->followable->name}";
}

// Count pending
$pendingCount = $user->notApprovedFollowings()->count();
```

## Relationships on Followable Trait

Models with the `Followable` trait have access to these relationships:

### `followables()`

**Type:** `HasMany`  
**Related Model:** `Followable`  
**Returns:** All follow records for this model (both pending and accepted)

```php
$user = User::find(1);

// Get all follow records
$followRecords = $user->followables;

// Filter by date
$recentFollows = $user->followables()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// Count total
$totalFollows = $user->followables()->count();
```

### `followers()`

**Type:** `BelongsToMany`  
**Related Model:** User model (configured in `config/auth.php`)  
**Pivot Table:** `followables`  
**Returns:** All users following this model

```php
// Get all followers
$followers = $user->followers;

// With pagination
$followers = $user->followers()->paginate(20);

// Access pivot data
foreach ($user->followers as $follower) {
    echo "Followed at: {$follower->pivot->accepted_at}";
}

// Order by follow date
$followers = $user->followers()
    ->orderBy('followables.created_at', 'desc')
    ->get();

// Eager load
$users = User::with('followers')->get();
```

### `approvedFollowers()`

**Type:** `BelongsToMany`  
**Related Model:** User model  
**Returns:** Only accepted followers

```php
// Get approved followers
$approvedFollowers = $user->approvedFollowers;

// Count
$approvedCount = $user->approvedFollowers()->count();

// With user details
$followers = $user->approvedFollowers()
    ->with('profile')
    ->get();
```

### `notApprovedFollowers()`

**Type:** `BelongsToMany`  
**Related Model:** User model  
**Returns:** Only pending followers

```php
// Get pending followers
$pendingFollowers = $user->notApprovedFollowers;

// Show requests in UI
foreach ($user->notApprovedFollowers as $follower) {
    echo "{$follower->name} wants to follow you";
}

// Count
$pendingCount = $user->notApprovedFollowers()->count();
```

## Relationships on Followable Model

The `Followable` model itself has these relationships:

### `followable()`

**Type:** `MorphTo`  
**Returns:** The model being followed

```php
use Akira\Followable\Followable;

$follow = Followable::first();
$followed = $follow->followable;

if ($followed instanceof User) {
    echo "Following user: {$followed->name}";
}
```

### `user()` / `follower()`

**Type:** `BelongsTo`  
**Returns:** The user who created the follow

```php
$follow = Followable::first();
$follower = $follow->user; // or $follow->follower

echo "{$follower->name} is following";
```

## Eager Loading

Always eager load relationships to avoid N+1 queries:

```php
// Bad: N+1 problem
$users = User::all();
foreach ($users as $user) {
    $count = $user->followers()->count();
}

// Good: Single query
$users = User::withCount('followers')->get();
foreach ($users as $user) {
    echo $user->followers_count;
}

// Load relationships
$users = User::with(['followers', 'followings'])->get();

// Load nested relationships
$users = User::with(['followers.profile', 'followings.followable'])->get();

// Conditional eager loading
$users = User::with(['followers' => function ($query) {
    $query->where('followables.accepted_at', '>=', now()->subDays(7));
}])->get();
```

## Counting Relationships

```php
// With counts
$users = User::withCount(['followers', 'followings'])->get();

foreach ($users as $user) {
    echo "{$user->name}: {$user->followers_count} followers, {$user->followings_count} following";
}

// Conditional counts
$users = User::withCount([
    'followers',
    'followers as approved_followers_count' => function ($query) {
        $query->whereNotNull('followables.accepted_at');
    },
    'followers as pending_followers_count' => function ($query) {
        $query->whereNull('followables.accepted_at');
    },
])->get();
```

## Exists Checks

```php
// Check if has any followers
$hasFollowers = $user->followers()->exists();

// Check if has pending followers
$hasPending = $user->notApprovedFollowers()->exists();

// Using whereHas
$popularUsers = User::whereHas('followers', function ($query) {
    $query->where('followables.accepted_at', '>=', now()->subMonth());
}, '>=', 100)->get();
```

---

**Previous:** [Query Scopes](12-query-scopes.md) | **Next:** [Advanced Usage](14-advanced-usage.md)
