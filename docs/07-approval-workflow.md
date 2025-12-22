# Approval Workflow

This guide explains how to implement follow request approvals for private accounts and custom approval logic.

## Understanding Approval States

Laravel Followable supports two follow states:

1. **Accepted** - The follow is active (`accepted_at` is NOT NULL)
2. **Pending** - The follow awaits approval (`accepted_at` is NULL)

## Enabling Approval Requirements

To require approval for follows, override the `needsToApproveFollowRequests()` method in your model:

```php
namespace App\Models;

use Akira\Followable\Concerns\Followable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Followable;
    
    public function needsToApproveFollowRequests(): bool
    {
        return $this->is_private;
    }
}
```

Don't forget to add the `is_private` column to your users table:

```php
Schema::table('users', function (Blueprint $table) {
    $table->boolean('is_private')->default(false);
});
```

## How Follow Approval Works

### Following a Public Account

When a user follows a public account (where `needsToApproveFollowRequests()` returns `false`):

```php
$user->follow($publicUser);
// Result: ['pending' => false]
// The follow is immediately accepted (accepted_at is set to now())
```

### Following a Private Account

When a user follows a private account (where `needsToApproveFollowRequests()` returns `true`):

```php
$user->follow($privateUser);
// Result: ['pending' => true]
// The follow is created but accepted_at remains NULL
```

## Checking Follow Status

### For the Follower

```php
// Check if following (only returns true for accepted follows)
if ($user->isFollowing($privateUser)) {
    echo "You are following this user";
}

// Check if request is pending
if ($user->hasRequestedToFollow($privateUser)) {
    echo "Your follow request is pending approval";
}
```

### For the Followable

```php
// Check if followed by someone
if ($privateUser->isFollowedBy($user)) {
    echo "This user is following you";
}

// Get all pending requests
$pendingRequests = $privateUser->notApprovedFollowers;

// Get all approved followers
$approvedFollowers = $privateUser->approvedFollowers;
```

## Managing Follow Requests

### Accepting Requests

```php
$privateUser = auth()->user();
$requester = User::find($requesterId);

// Accept a single request
$privateUser->acceptFollowRequestFrom($requester);

// Accept all pending requests
foreach ($privateUser->notApprovedFollowers as $follower) {
    $privateUser->acceptFollowRequestFrom($follower);
}
```

### Rejecting Requests

```php
// Reject a single request
$privateUser->rejectFollowRequestFrom($requester);

// Reject all pending requests from specific users
$spamUsers = User::where('is_spam', true)->get();
foreach ($spamUsers as $spammer) {
    if ($privateUser->notApprovedFollowers->contains($spammer)) {
        $privateUser->rejectFollowRequestFrom($spammer);
    }
}
```

## Controller Implementation

### Follow Request Dashboard

```php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FollowRequestController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        
        $pendingRequests = $user->notApprovedFollowers()
            ->with('profile')
            ->latest('followables.created_at')
            ->paginate(20);
        
        return view('follow-requests.index', compact('pendingRequests'));
    }
    
    public function accept(User $follower)
    {
        $user = auth()->user();
        
        try {
            $user->acceptFollowRequestFrom($follower);
            return back()->with('success', "You accepted {$follower->name}'s follow request");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to accept follow request');
        }
    }
    
    public function reject(User $follower)
    {
        $user = auth()->user();
        
        try {
            $user->rejectFollowRequestFrom($follower);
            return back()->with('success', "You rejected {$follower->name}'s follow request");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to reject follow request');
        }
    }
    
    public function acceptAll()
    {
        $user = auth()->user();
        
        $count = 0;
        foreach ($user->notApprovedFollowers as $follower) {
            $user->acceptFollowRequestFrom($follower);
            $count++;
        }
        
        return back()->with('success', "Accepted {$count} follow requests");
    }
}
```

### Routes

```php
// routes/web.php
Route::middleware('auth')->group(function () {
    Route::get('follow-requests', [FollowRequestController::class, 'index'])
        ->name('follow-requests.index');
    
    Route::post('follow-requests/{follower}/accept', [FollowRequestController::class, 'accept'])
        ->name('follow-requests.accept');
    
    Route::delete('follow-requests/{follower}/reject', [FollowRequestController::class, 'reject'])
        ->name('follow-requests.reject');
    
    Route::post('follow-requests/accept-all', [FollowRequestController::class, 'acceptAll'])
        ->name('follow-requests.accept-all');
});
```

## View Implementation

### Follow Requests List

```blade
{{-- resources/views/follow-requests/index.blade.php --}}
<x-app-layout>
    <div class="container">
        <h1>Follow Requests</h1>
        
        @if($pendingRequests->count() > 0)
            <div class="mb-4">
                <form action="{{ route('follow-requests.accept-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-primary">
                        Accept All ({{ $pendingRequests->total() }})
                    </button>
                </form>
            </div>
            
            <div class="requests-list">
                @foreach($pendingRequests as $follower)
                    <div class="request-card">
                        <img src="{{ $follower->avatar_url }}" alt="{{ $follower->name }}">
                        
                        <div class="request-info">
                            <h3>{{ $follower->name }}</h3>
                            <p>{{ '@' . $follower->username }}</p>
                            <p class="text-muted">
                                Requested {{ $follower->pivot->created_at->diffForHumans() }}
                            </p>
                        </div>
                        
                        <div class="request-actions">
                            <form action="{{ route('follow-requests.accept', $follower) }}" 
                                  method="POST" 
                                  style="display: inline;">
                                @csrf
                                <button type="submit" class="btn btn-success">Accept</button>
                            </form>
                            
                            <form action="{{ route('follow-requests.reject', $follower) }}" 
                                  method="POST" 
                                  style="display: inline;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">Reject</button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
            
            {{ $pendingRequests->links() }}
        @else
            <p class="text-muted">No pending follow requests</p>
        @endif
    </div>
</x-app-layout>
```

### Follow Button with States

```blade
{{-- Show different states based on follow status --}}
@auth
    @php
        $isFollowing = auth()->user()->isFollowing($user);
        $isPending = auth()->user()->hasRequestedToFollow($user);
    @endphp
    
    @if($user->id === auth()->id())
        {{-- Can't follow yourself --}}
    @elseif($isFollowing)
        <form action="{{ route('users.unfollow', $user) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-secondary">Following</button>
        </form>
    @elseif($isPending)
        <form action="{{ route('users.unfollow', $user) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-secondary">Requested</button>
        </form>
    @else
        <form action="{{ route('users.follow', $user) }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-primary">Follow</button>
        </form>
    @endif
@endauth
```

## Advanced Approval Logic

### Reputation-Based Auto-Approval

```php
public function needsToApproveFollowRequests(): bool
{
    $follower = auth()->user();
    
    if (!$follower) {
        return true;
    }
    
    // Auto-approve verified users
    if ($follower->is_verified) {
        return false;
    }
    
    // Auto-approve users with high reputation
    if ($follower->reputation_score >= 100) {
        return false;
    }
    
    // Require approval for others if private
    return $this->is_private;
}
```

### Time-Based Approval

```php
public function needsToApproveFollowRequests(): bool
{
    $follower = auth()->user();
    
    if (!$follower) {
        return true;
    }
    
    // Auto-approve during specific hours
    $hour = now()->hour;
    if ($hour >= 9 && $hour <= 17 && !$this->is_private) {
        return false;
    }
    
    // Check if follower account is older than 30 days
    if ($follower->created_at->lt(now()->subDays(30))) {
        return false;
    }
    
    return true;
}
```

### Mutual Connection Approval

```php
public function needsToApproveFollowRequests(): bool
{
    $follower = auth()->user();
    
    if (!$follower) {
        return true;
    }
    
    // Auto-approve if we follow each other's friends
    $myFollowings = $this->followings()->pluck('followable_id');
    $theirFollowings = $follower->followings()->pluck('followable_id');
    
    $mutualConnections = $myFollowings->intersect($theirFollowings)->count();
    
    // Auto-approve if 3+ mutual connections
    if ($mutualConnections >= 3) {
        return false;
    }
    
    return $this->is_private;
}
```

## Notifications

### Notify on Follow Request

```php
use Akira\Followable\Events\Followed;
use Illuminate\Support\Facades\Event;
use App\Notifications\FollowRequestNotification;

Event::listen(Followed::class, function (Followed $event) {
    $follow = $event->follow;
    
    // If pending, notify about the request
    if (!$follow->accepted_at) {
        $follow->followable->notify(
            new FollowRequestNotification($follow->user)
        );
    }
});
```

### Notify on Request Acceptance

Create a listener or use events:

```php
// In FollowRequestController::accept()
$user->acceptFollowRequestFrom($follower);

// Notify the follower
$follower->notify(new FollowRequestAcceptedNotification($user));
```

## API Endpoints

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::get('follow-requests', [ApiFollowRequestController::class, 'index']);
    Route::post('follow-requests/{user}/accept', [ApiFollowRequestController::class, 'accept']);
    Route::delete('follow-requests/{user}/reject', [ApiFollowRequestController::class, 'reject']);
    Route::get('follow-requests/count', [ApiFollowRequestController::class, 'count']);
});

// Controller
class ApiFollowRequestController extends Controller
{
    public function index()
    {
        $requests = auth()->user()
            ->notApprovedFollowers()
            ->with('profile')
            ->latest('followables.created_at')
            ->paginate(20);
        
        return response()->json($requests);
    }
    
    public function count()
    {
        $count = auth()->user()->notApprovedFollowers()->count();
        
        return response()->json(['count' => $count]);
    }
    
    public function accept(User $user)
    {
        auth()->user()->acceptFollowRequestFrom($user);
        
        return response()->json(['message' => 'Follow request accepted']);
    }
    
    public function reject(User $user)
    {
        auth()->user()->rejectFollowRequestFrom($user);
        
        return response()->json(['message' => 'Follow request rejected']);
    }
}
```

---

**Previous:** [Followable Trait](06-followable-trait.md) | **Next:** [Followable Model](08-followable-model.md)
