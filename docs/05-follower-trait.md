# Follower Trait

The `Follower` trait provides methods for models that can follow other entities. This guide covers all available methods, relationships, and advanced features.

## Overview

Add the `Follower` trait to any model that needs the ability to follow other models:

```php
namespace App\Models;

use Akira\Followable\Concerns\Follower;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use Follower;
}
```

## Public Methods

### `follow(Model $followable): array`

Follow another model. Returns an array indicating whether the follow is pending approval.

**Parameters:**
- `$followable` - The model to follow (must use the `Followable` trait)

**Returns:**
- `['pending' => bool]` - Whether the follow requires approval

**Throws:**
- `CannotFollowYourSelfException` - If trying to follow the same model instance
- `FollowableTraitNotFoundException` - If target model doesn't use `Followable` trait

**Example:**

```php
$user = User::find(1);
$target = User::find(2);

$result = $user->follow($target);

if ($result['pending']) {
    echo "Follow request sent and awaiting approval";
} else {
    echo "Now following user";
}
```

**Use Cases:**

```php
// Following a user
$user->follow($anotherUser);

// Following a post
$post = Post::find(1);
$user->follow($post);

// Following any model with Followable trait
$channel = Channel::find(5);
$user->follow($channel);
```

### `unfollow(Model $followable): void`

Unfollow a previously followed model. Removes both pending and accepted follows.

**Parameters:**
- `$followable` - The model to unfollow

**Throws:**
- `FollowableTraitNotFoundException` - If target model doesn't use `Followable` trait

**Example:**

```php
$user->unfollow($target);

// Verify unfollowed
if (!$user->isFollowing($target)) {
    echo "Successfully unfollowed";
}
```

### `toggleFollow(Model $followable): void`

Toggle the follow state. If following, will unfollow. If not following, will follow.

**Parameters:**
- `$followable` - The model to toggle

**Throws:**
- `FollowableTraitNotFoundException` - If target model doesn't use `Followable` trait
- `CannotFollowYourSelfException` - If trying to follow self

**Example:**

```php
// First call: follows the user
$user->toggleFollow($target);

// Second call: unfollows the user
$user->toggleFollow($target);

// Useful for toggle buttons
public function toggleFollow(User $target)
{
    auth()->user()->toggleFollow($target);
    return back();
}
```

### `isFollowing(Model $followable): bool`

Check if currently following a model. Returns `true` only for accepted follows.

**Parameters:**
- `$followable` - The model to check

**Returns:**
- `bool` - `true` if following (and accepted), `false` otherwise

**Throws:**
- `FollowableTraitNotFoundException` - If target model doesn't use `Followable` trait

**Example:**

```php
if ($user->isFollowing($target)) {
    echo "You are following this user";
}

// Check multiple follows
$users = User::all();
foreach ($users as $otherUser) {
    if ($user->isFollowing($otherUser)) {
        echo "Following {$otherUser->name}";
    }
}
```

**Performance Note:**

If the `followings` relationship is already loaded, this method uses the loaded collection instead of querying the database:

```php
// Efficient: Only one query
$user->load('followings');
foreach ($users as $target) {
    $isFollowing = $user->isFollowing($target); // No additional query
}
```

### `hasRequestedToFollow(Model $followable): bool`

Check if there's a pending follow request for a model.

**Parameters:**
- `$followable` - The model to check

**Returns:**
- `bool` - `true` if there's a pending follow request, `false` otherwise

**Throws:**
- `FollowableTraitNotFoundException` - If target model doesn't use `Followable` trait

**Example:**

```php
if ($user->hasRequestedToFollow($privateUser)) {
    echo "Your follow request is pending";
}

// Show appropriate button
if ($user->isFollowing($target)) {
    echo "Unfollow";
} elseif ($user->hasRequestedToFollow($target)) {
    echo "Pending";
} else {
    echo "Follow";
}
```

## Relationships

### `followings(): HasMany`

Get all follow records (both pending and accepted).

**Returns:**
- `HasMany` - Relationship to `Followable` model

**Example:**

```php
// Get all followings
$followings = $user->followings;

// With query builder
$recentFollowings = $user->followings()
    ->where('created_at', '>=', now()->subDays(7))
    ->get();

// Count followings
$count = $user->followings()->count();
```

### `approvedFollowings(): HasMany`

Get only accepted follow records.

**Returns:**
- `HasMany` - Relationship filtered to accepted follows

**Example:**

```php
$approvedFollowings = $user->approvedFollowings;

// Only show accepted followings
$acceptedUsers = $user->approvedFollowings()
    ->with('followable')
    ->get()
    ->pluck('followable');
```

### `notApprovedFollowings(): HasMany`

Get only pending follow records.

**Returns:**
- `HasMany` - Relationship filtered to pending follows

**Example:**

```php
$pendingFollowings = $user->notApprovedFollowings;

// Show pending requests
foreach ($user->notApprovedFollowings as $following) {
    echo "Waiting for {$following->followable->name} to accept";
}

// Count pending
$pendingCount = $user->notApprovedFollowings()->count();
```

## Advanced Features

### `attachFollowStatus()`

Attach follow status to a collection of models. This is extremely useful for displaying follow buttons in lists.

**Signature:**

```php
attachFollowStatus(
    Model|Collection|LengthAwarePaginator|Paginator|LazyCollection|array $followables,
    bool $returnFirst = false,
    ?callable $resolver = null
): mixed
```

**Parameters:**
- `$followables` - Models to attach status to
- `$returnFirst` - If `true`, returns only the first item instead of collection
- `$resolver` - Optional callback to resolve the actual model from each item

**Returns:**
- `Collection` - Collection with follow status attached (or single model if `$returnFirst` is `true`)

**Attached Properties:**
- `has_followed` (bool) - Whether the user is following this model
- `followed_at` (Carbon|null) - When the follow was created
- `follow_accepted_at` (Carbon|null) - When the follow was accepted

**Example:**

```php
// Attach to a collection
$users = User::all();
auth()->user()->attachFollowStatus($users);

foreach ($users as $user) {
    if ($user->has_followed) {
        echo "Following since {$user->followed_at->diffForHumans()}";
        if ($user->follow_accepted_at) {
            echo " (Accepted on {$user->follow_accepted_at->format('M d, Y')})";
        } else {
            echo " (Pending)";
        }
    } else {
        echo "Not following";
    }
}
```

**With Pagination:**

```php
$users = User::paginate(20);
auth()->user()->attachFollowStatus($users);

// In Blade
@foreach($users as $user)
    <div>
        {{ $user->name }}
        @if($user->has_followed)
            <button>Unfollow</button>
        @else
            <button>Follow</button>
        @endif
    </div>
@endforeach

{{ $users->links() }}
```

**With Custom Resolver:**

```php
// When working with nested data
$posts = Post::with('author')->get();

auth()->user()->attachFollowStatus($posts, false, function ($post) {
    return $post->author; // Check follow status of authors, not posts
});

foreach ($posts as $post) {
    if ($post->author->has_followed) {
        echo "You follow this author";
    }
}
```

**Return Single Model:**

```php
$user = User::find(1);
$userWithStatus = auth()->user()->attachFollowStatus($user, true);

if ($userWithStatus->has_followed) {
    echo "Following";
}
```

## Query Scopes on Followable Model

When working with the `Followable` model directly through the `followings()` relationship, you have access to additional scopes:

```php
use Akira\Followable\Followable;

// Get follows of a specific type
$userFollows = $user->followings()
    ->withType(User::class)
    ->get();

// Get follows of a specific model
$specificFollow = $user->followings()
    ->of($targetUser)
    ->first();

// Only accepted follows
$accepted = $user->followings()
    ->accepted()
    ->get();

// Only pending follows
$pending = $user->followings()
    ->notAccepted()
    ->get();
```

## Practical Examples

### Follow Suggestions

```php
public function suggestUsers(User $user)
{
    // Users not currently followed
    $suggestions = User::whereNotIn('id', function ($query) use ($user) {
        $query->select('followable_id')
            ->from('followables')
            ->where('user_id', $user->id)
            ->where('followable_type', User::class);
    })
    ->where('id', '!=', $user->id)
    ->limit(10)
    ->get();
    
    $user->attachFollowStatus($suggestions);
    
    return $suggestions;
}
```

### Mutual Follows

```php
public function mutualFollows(User $user)
{
    $following = $user->followings()
        ->where('followable_type', User::class)
        ->pluck('followable_id');
    
    $followers = $user->followables()
        ->pluck('user_id');
    
    $mutualIds = $following->intersect($followers);
    
    return User::whereIn('id', $mutualIds)->get();
}
```

### Following Activity Feed

```php
public function followingActivity(User $user)
{
    $followingIds = $user->approvedFollowings()
        ->where('followable_type', User::class)
        ->pluck('followable_id');
    
    return Post::whereIn('user_id', $followingIds)
        ->latest()
        ->paginate(20);
}
```

---

**Previous:** [Basic Usage](04-basic-usage.md) | **Next:** [Followable Trait](06-followable-trait.md)
