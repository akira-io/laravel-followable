# Basic Usage

This guide covers the fundamental operations of Laravel Followable, including following and unfollowing users, checking follow status, and retrieving followers.

## Adding Traits to Your Models

Before using Laravel Followable, you need to add the appropriate traits to your models.

### The Follower Trait

Add the `Follower` trait to models that can follow other entities (typically your User model):

```php
namespace App\Models;

use Akira\Followable\Concerns\Follower;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Follower;
    
    // ... rest of your model
}
```

### The Followable Trait

Add the `Followable` trait to models that can be followed:

```php
namespace App\Models;

use Akira\Followable\Concerns\Followable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Followable;
    
    // ... rest of your model
}
```

### Using Both Traits

Most commonly, User models use both traits so users can follow and be followed:

```php
namespace App\Models;

use Akira\Followable\Concerns\Followable;
use Akira\Followable\Concerns\Follower;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Followable, Follower;
    
    // ... rest of your model
}
```

## Following Users

To make a user follow another user:

```php
$user = User::find(1);
$targetUser = User::find(2);

$user->follow($targetUser);
```

The `follow()` method returns an array with information about the follow status:

```php
$result = $user->follow($targetUser);

// ['pending' => false] if the follow was immediately accepted
// ['pending' => true] if the target user requires approval
```

### Handling Exceptions

The `follow()` method throws exceptions in certain cases:

```php
use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;

try {
    $user->follow($targetUser);
} catch (CannotFollowYourSelfException $e) {
    // User tried to follow themselves
    return response()->json(['error' => 'You cannot follow yourself'], 422);
} catch (FollowableTraitNotFoundException $e) {
    // Target model doesn't use the Followable trait
    return response()->json(['error' => 'This entity cannot be followed'], 422);
}
```

## Unfollowing Users

To unfollow a user:

```php
$user->unfollow($targetUser);
```

The `unfollow()` method removes the follow relationship immediately, regardless of whether it was pending or accepted.

```php
// Check before unfollowing
if ($user->isFollowing($targetUser)) {
    $user->unfollow($targetUser);
}
```

## Toggle Follow

To toggle the follow status (follow if not following, unfollow if already following):

```php
$user->toggleFollow($targetUser);
```

This is useful for implementing a single button that switches between follow/unfollow states:

```php
// In a controller
public function toggleFollow(User $targetUser)
{
    auth()->user()->toggleFollow($targetUser);
    
    return back();
}
```

## Checking Follow Status

### Check if Following

To check if a user is following another user:

```php
if ($user->isFollowing($targetUser)) {
    // User is following the target user
}
```

This method only returns `true` if the follow has been accepted. Pending follows return `false`.

### Check Pending Requests

To check if a user has a pending follow request:

```php
if ($user->hasRequestedToFollow($targetUser)) {
    // User has sent a follow request that's awaiting approval
}
```

### Check if Followed By

To check if a user is being followed by another user:

```php
if ($targetUser->isFollowedBy($user)) {
    // Target user is being followed by the user
}
```

## Retrieving Followers and Following

### Get Followers

Retrieve all users following a specific user:

```php
$followers = $user->followers;

// With pagination
$followers = $user->followers()->paginate(20);

// Only approved followers
$approvedFollowers = $user->approvedFollowers;

// Only pending followers
$pendingFollowers = $user->notApprovedFollowers;
```

### Get Following

Retrieve all users a specific user is following:

```php
$following = $user->followings;

// With pagination
$following = $user->followings()->paginate(20);

// Only approved followings
$approvedFollowing = $user->approvedFollowings;

// Only pending followings (awaiting approval)
$pendingFollowing = $user->notApprovedFollowings;
```

### Counting Relationships

Get counts without loading all records:

```php
$followersCount = $user->followers()->count();
$followingCount = $user->followings()->count();

// Or use withCount for multiple users
$users = User::withCount(['followers', 'followings'])->get();

foreach ($users as $user) {
    echo "{$user->name} has {$user->followers_count} followers";
    echo " and is following {$user->followings_count} users";
}
```

## Working with Collections

### Filtering Followers

```php
// Get followers from a specific time period
$recentFollowers = $user->followers()
    ->wherePivot('created_at', '>=', now()->subDays(7))
    ->get();

// Get active followers (with at least one post)
$activeFollowers = $user->followers()
    ->has('posts')
    ->get();
```

### Eager Loading

Avoid N+1 queries by eager loading relationships:

```php
// Load users with their followers
$users = User::with('followers')->get();

// Load users with followers and their profiles
$users = User::with('followers.profile')->get();

// Load with counts
$users = User::withCount('followers')->get();
```

## Practical Examples

### Follow Button in Blade

```blade
@if(auth()->user()->isFollowing($user))
    <form action="{{ route('users.unfollow', $user) }}" method="POST">
        @csrf
        @method('DELETE')
        <button type="submit">Unfollow</button>
    </form>
@else
    <form action="{{ route('users.follow', $user) }}" method="POST">
        @csrf
        <button type="submit">Follow</button>
    </form>
@endif
```

### Controller Actions

```php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function follow(User $user)
    {
        $result = auth()->user()->follow($user);
        
        if ($result['pending']) {
            return back()->with('success', 'Follow request sent!');
        }
        
        return back()->with('success', 'You are now following ' . $user->name);
    }
    
    public function unfollow(User $user)
    {
        auth()->user()->unfollow($user);
        
        return back()->with('success', 'Unfollowed ' . $user->name);
    }
    
    public function followers(User $user)
    {
        $followers = $user->followers()->paginate(20);
        
        return view('users.followers', compact('user', 'followers'));
    }
    
    public function following(User $user)
    {
        $following = $user->followings()->paginate(20);
        
        return view('users.following', compact('user', 'following'));
    }
}
```

### API Endpoints

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('users/{user}/follow', [FollowController::class, 'follow']);
    Route::delete('users/{user}/follow', [FollowController::class, 'unfollow']);
    Route::get('users/{user}/followers', [FollowController::class, 'followers']);
    Route::get('users/{user}/following', [FollowController::class, 'following']);
});

// In your API controller
public function follow(User $user)
{
    try {
        $result = auth()->user()->follow($user);
        
        return response()->json([
            'message' => $result['pending'] ? 'Follow request sent' : 'Following user',
            'pending' => $result['pending'],
        ]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 422);
    }
}
```

## Following Other Models

You can follow any model that uses the `Followable` trait:

```php
namespace App\Models;

use Akira\Followable\Concerns\Followable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Followable;
}
```

Then follow it like any other model:

```php
$post = Post::find(1);
$user->follow($post);

if ($user->isFollowing($post)) {
    // User is following this post
}
```

---

**Previous:** [Database Schema](03-database-schema.md) | **Next:** [Follower Trait](05-follower-trait.md)
