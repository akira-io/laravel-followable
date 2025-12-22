# Attach Follow Status

The `attachFollowStatus()` method is a powerful feature that enriches collections with follow status information. This guide covers its usage, parameters, and practical applications.

## Overview

When displaying lists of users or entities, you often need to show whether the current user follows each item. Instead of querying the database for each item (N+1 problem), `attachFollowStatus()` loads all follow statuses in a single query and attaches them to your models.

## Basic Usage

```php
$users = User::all();
auth()->user()->attachFollowStatus($users);

foreach ($users as $user) {
    if ($user->has_followed) {
        echo "You follow {$user->name}";
    }
}
```

## Method Signature

```php
public function attachFollowStatus(
    Model|Collection|LengthAwarePaginator|Paginator|LazyCollection|array $followables,
    bool $returnFirst = false,
    ?callable $resolver = null
): mixed
```

## Parameters

### `$followables`

The collection of models to attach status to. Accepts:
- Single `Model` instance
- `Collection` (Eloquent or Support)
- `LengthAwarePaginator`
- `Paginator`
- `CursorPaginator`
- `LazyCollection`
- Plain `array`

### `$returnFirst`

**Type:** `bool`  
**Default:** `false`

If `true`, returns only the first item instead of the full collection. Useful when working with a single model.

### `$resolver`

**Type:** `callable|null`  
**Default:** `null`

Optional callback to resolve the actual model from each item. Useful when your collection contains wrapped or nested data.

**Signature:** `function ($item): Model`

## Attached Properties

After calling `attachFollowStatus()`, each model in the collection will have these additional properties:

### `has_followed`

**Type:** `bool`

Whether the current user is following this model.

```php
if ($user->has_followed) {
    echo "Following";
}
```

### `followed_at`

**Type:** `Carbon|null`

Timestamp when the follow was created.

```php
if ($user->has_followed) {
    echo "Following since {$user->followed_at->diffForHumans()}";
}
```

### `follow_accepted_at`

**Type:** `Carbon|null`

Timestamp when the follow was accepted. Will be `null` for pending follows.

```php
if ($user->has_followed) {
    if ($user->follow_accepted_at) {
        echo "Accepted on {$user->follow_accepted_at->format('M d, Y')}";
    } else {
        echo "Pending approval";
    }
}
```

## Working with Collections

### Eloquent Collections

```php
$users = User::where('is_active', true)->get();
auth()->user()->attachFollowStatus($users);

foreach ($users as $user) {
    echo $user->name . ': ' . ($user->has_followed ? 'Following' : 'Not following');
}
```

### Paginated Results

```php
$users = User::paginate(20);
auth()->user()->attachFollowStatus($users);

// In Blade
@foreach($users as $user)
    <div>
        {{ $user->name }}
        <span class="badge">
            {{ $user->has_followed ? 'Following' : 'Follow' }}
        </span>
    </div>
@endforeach

{{ $users->links() }}
```

### Cursor Pagination

```php
$users = User::cursorPaginate(50);
auth()->user()->attachFollowStatus($users);
```

### Lazy Collections

```php
$users = User::lazy();
auth()->user()->attachFollowStatus($users);

foreach ($users as $user) {
    // Process with follow status attached
}
```

## Working with Single Models

### Using `$returnFirst`

```php
$user = User::find(1);
$userWithStatus = auth()->user()->attachFollowStatus($user, returnFirst: true);

if ($userWithStatus->has_followed) {
    echo "You follow this user";
}
```

### Without `$returnFirst`

```php
$user = User::find(1);
$collection = auth()->user()->attachFollowStatus([$user]);
$userWithStatus = $collection->first();
```

## Using the Resolver

The resolver is useful when your collection items are not direct models.

### Nested Data

```php
$posts = Post::with('author')->get();

auth()->user()->attachFollowStatus($posts, false, function ($post) {
    return $post->author; // Check follow status of authors
});

foreach ($posts as $post) {
    if ($post->author->has_followed) {
        echo "You follow the author of this post";
    }
}
```

### Wrapped Models

```php
$items = [
    ['user' => User::find(1), 'extra' => 'data'],
    ['user' => User::find(2), 'extra' => 'data'],
];

auth()->user()->attachFollowStatus($items, false, function ($item) {
    return $item['user'];
});

foreach ($items as $item) {
    if ($item['user']->has_followed) {
        echo "Following {$item['user']->name}";
    }
}
```

### API Resources

```php
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_following' => $this->has_followed ?? false,
            'followed_at' => $this->followed_at,
            'follow_status' => $this->getFollowStatus(),
        ];
    }
    
    protected function getFollowStatus(): string
    {
        if (!isset($this->has_followed) || !$this->has_followed) {
            return 'not_following';
        }
        
        if ($this->follow_accepted_at) {
            return 'following';
        }
        
        return 'pending';
    }
}

// In controller
$users = User::paginate(20);
auth()->user()->attachFollowStatus($users);

return UserResource::collection($users);
```

## Performance Optimization

### Eager Load Before Attaching

```php
// Load followings relationship once
$currentUser = auth()->user()->load('followings');

// Now attachFollowStatus won't query the database
$users = User::all();
$currentUser->attachFollowStatus($users);
```

### Use with Query Scopes

```php
// Get users and attach status efficiently
$users = User::with('profile')
    ->where('is_verified', true)
    ->get();

auth()->user()->load('followings'); // Load once
auth()->user()->attachFollowStatus($users);
```

## Blade Components

Create a reusable Blade component:

```php
// app/View/Components/FollowButton.php
namespace App\View\Components;

use App\Models\User;
use Illuminate\View\Component;

class FollowButton extends Component
{
    public function __construct(
        public User $user
    ) {
        if (auth()->check() && !isset($user->has_followed)) {
            auth()->user()->attachFollowStatus($user, true);
        }
    }
    
    public function render()
    {
        return view('components.follow-button');
    }
}
```

```blade
{{-- resources/views/components/follow-button.blade.php --}}
@auth
    @if($user->has_followed)
        <button class="btn-following">
            Following
            @if(!$user->follow_accepted_at)
                (Pending)
            @endif
        </button>
    @else
        <button class="btn-follow">Follow</button>
    @endif
@endauth
```

Usage:

```blade
<x-follow-button :user="$user" />
```

## Livewire Integration

```php
namespace App\Http\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

class UsersList extends Component
{
    use WithPagination;
    
    public function render()
    {
        $users = User::paginate(20);
        
        if (auth()->check()) {
            auth()->user()->attachFollowStatus($users);
        }
        
        return view('livewire.users-list', [
            'users' => $users,
        ]);
    }
}
```

## Vue/React Integration

### API Endpoint

```php
public function index(Request $request)
{
    $users = User::paginate(20);
    
    if (auth()->check()) {
        auth()->user()->attachFollowStatus($users);
    }
    
    return response()->json([
        'data' => $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'avatar' => $user->avatar_url,
                'is_following' => $user->has_followed ?? false,
                'follow_status' => $this->getFollowStatus($user),
            ];
        }),
        'meta' => [
            'current_page' => $users->currentPage(),
            'last_page' => $users->lastPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
        ],
    ]);
}

private function getFollowStatus($user): string
{
    if (!isset($user->has_followed) || !$user->has_followed) {
        return 'not_following';
    }
    
    return $user->follow_accepted_at ? 'following' : 'pending';
}
```

## Practical Examples

### User Directory

```php
public function directory(Request $request)
{
    $search = $request->get('search');
    
    $users = User::query()
        ->when($search, function ($query, $search) {
            $query->where('name', 'like', "%{$search}%");
        })
        ->paginate(20);
    
    if (auth()->check()) {
        auth()->user()->attachFollowStatus($users);
    }
    
    return view('users.directory', compact('users', 'search'));
}
```

```blade
@foreach($users as $user)
    <div class="user-card">
        <img src="{{ $user->avatar }}" alt="{{ $user->name }}">
        <h3>{{ $user->name }}</h3>
        
        @auth
            @if($user->has_followed)
                <span class="badge badge-success">
                    Following since {{ $user->followed_at->format('M Y') }}
                </span>
                @if(!$user->follow_accepted_at)
                    <span class="badge badge-warning">Pending</span>
                @endif
            @else
                <button onclick="follow({{ $user->id }})">Follow</button>
            @endif
        @endauth
    </div>
@endforeach
```

### Suggestions Widget

```php
public function suggestions(Request $request)
{
    $suggestions = User::whereNotIn('id', function ($query) {
        $query->select('followable_id')
            ->from('followables')
            ->where('user_id', auth()->id())
            ->where('followable_type', User::class);
    })
    ->where('id', '!=', auth()->id())
    ->inRandomOrder()
    ->limit(5)
    ->get();
    
    auth()->user()->attachFollowStatus($suggestions);
    
    return view('partials.suggestions', compact('suggestions'));
}
```

### Search Results

```php
public function search(Request $request)
{
    $query = $request->get('q');
    
    $users = User::search($query)->paginate(20);
    
    if (auth()->check()) {
        auth()->user()->attachFollowStatus($users);
    }
    
    return view('search.results', compact('users', 'query'));
}
```

---

**Previous:** [Exceptions](10-exceptions.md) | **Next:** [Query Scopes](12-query-scopes.md)
