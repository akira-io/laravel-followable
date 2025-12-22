# Exceptions

Laravel Followable throws specific exceptions for common error scenarios. This guide explains each exception and how to handle them.

## Available Exceptions

### `CannotFollowYourSelfException`

**Namespace:** `Akira\Followable\Exceptions\CannotFollowYourSelfException`

**Thrown when:** A user attempts to follow themselves

**Message:** "You cannot follow yourself"

**Example:**

```php
$user = User::find(1);

try {
    $user->follow($user); // Following self
} catch (\Akira\Followable\Exceptions\CannotFollowYourSelfException $e) {
    echo $e->getMessage(); // "You cannot follow yourself"
}
```

### `FollowableTraitNotFoundException`

**Namespace:** `Akira\Followable\Exceptions\FollowableTraitNotFoundException`

**Thrown when:** Attempting to follow a model that doesn't use the `Followable` trait

**Message:** "The followable model must use the Followable trait."

**Example:**

```php
class Post extends Model
{
    // Missing: use Followable;
}

$user = User::find(1);
$post = Post::find(1);

try {
    $user->follow($post);
} catch (\Akira\Followable\Exceptions\FollowableTraitNotFoundException $e) {
    echo $e->getMessage(); // "The followable model must use the Followable trait."
}
```

### `FollowerTraitNotFoundException`

**Namespace:** `Akira\Followable\Exceptions\FollowerTraitNotFoundException`

**Thrown when:** A model without the `Follower` trait is passed to methods expecting a follower

**Message:** "The follower model must use the Follower trait."

**Example:**

```php
class Admin extends Model
{
    use Followable;
    // Missing: use Follower;
}

$user = User::find(1);
$admin = Admin::find(1);

try {
    $user->acceptFollowRequestFrom($admin); // Admin can't be a follower
} catch (\Akira\Followable\Exceptions\FollowerTraitNotFoundException $e) {
    echo $e->getMessage(); // "The follower model must use the Follower trait."
}
```

## Exception Hierarchy

All Laravel Followable exceptions extend PHP's base `Exception` class:

```
Exception
├── CannotFollowYourSelfException
├── FollowableTraitNotFoundException
└── FollowerTraitNotFoundException
```

## Handling Exceptions

### Basic Try-Catch

```php
use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;

try {
    $user->follow($target);
} catch (CannotFollowYourSelfException $e) {
    return response()->json([
        'error' => 'You cannot follow yourself',
    ], 422);
} catch (FollowableTraitNotFoundException $e) {
    return response()->json([
        'error' => 'This entity cannot be followed',
    ], 422);
}
```

### Catch All Follow Exceptions

```php
use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;

try {
    $user->follow($target);
} catch (CannotFollowYourSelfException | FollowableTraitNotFoundException $e) {
    return back()->withErrors(['follow' => $e->getMessage()]);
}
```

### Controller-Level Exception Handling

```php
namespace App\Http\Controllers;

use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    public function follow(User $user)
    {
        try {
            $result = auth()->user()->follow($user);
            
            $message = $result['pending'] 
                ? 'Follow request sent!' 
                : "You are now following {$user->name}";
            
            return back()->with('success', $message);
            
        } catch (CannotFollowYourSelfException $e) {
            return back()->withErrors([
                'follow' => 'You cannot follow yourself',
            ]);
            
        } catch (FollowableTraitNotFoundException $e) {
            return back()->withErrors([
                'follow' => 'This user cannot be followed',
            ]);
        }
    }
}
```

### API Exception Handling

```php
namespace App\Http\Controllers\Api;

use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class FollowController extends Controller
{
    public function follow(User $user): JsonResponse
    {
        try {
            $result = auth()->user()->follow($user);
            
            return response()->json([
                'message' => $result['pending'] 
                    ? 'Follow request sent' 
                    : 'Following user',
                'pending' => $result['pending'],
            ], 200);
            
        } catch (CannotFollowYourSelfException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => 'CANNOT_FOLLOW_SELF',
            ], 422);
            
        } catch (FollowableTraitNotFoundException $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'code' => 'NOT_FOLLOWABLE',
            ], 422);
        }
    }
}
```

## Global Exception Handling

### Laravel Exception Handler

Register custom exception handling in `app/Exceptions/Handler.php`:

```php
namespace App\Exceptions;

use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;
use Akira\Followable\Exceptions\FollowerTraitNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (CannotFollowYourSelfException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'You cannot follow yourself',
                    'code' => 'SELF_FOLLOW_ATTEMPT',
                ], 422);
            }
            
            return back()->withErrors(['follow' => $e->getMessage()]);
        });
        
        $this->renderable(function (FollowableTraitNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'This entity cannot be followed',
                    'code' => 'NOT_FOLLOWABLE',
                ], 422);
            }
            
            return back()->withErrors(['follow' => $e->getMessage()]);
        });
        
        $this->renderable(function (FollowerTraitNotFoundException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'This entity cannot follow others',
                    'code' => 'NOT_FOLLOWER',
                ], 422);
            }
            
            return back()->withErrors(['follow' => $e->getMessage()]);
        });
    }
}
```

## Preventing Exceptions

### Check Before Following

```php
// Prevent self-follow
if ($user->id !== $target->id) {
    $user->follow($target);
}

// Check if model has trait
if (in_array(\Akira\Followable\Concerns\Followable::class, class_uses($target))) {
    $user->follow($target);
}
```

### Helper Methods

Create helper methods to check before attempting actions:

```php
class User extends Model
{
    use Follower, Followable;
    
    public function canFollow(Model $target): bool
    {
        // Can't follow self
        if ($this->is($target)) {
            return false;
        }
        
        // Must have Followable trait
        if (!in_array(Followable::class, class_uses($target))) {
            return false;
        }
        
        // Already following
        if ($this->isFollowing($target)) {
            return false;
        }
        
        return true;
    }
    
    public function canBeFollowedBy(Model $follower): bool
    {
        // Follower must have Follower trait
        if (!in_array(Follower::class, class_uses($follower))) {
            return false;
        }
        
        // Can't be followed by self
        if ($this->is($follower)) {
            return false;
        }
        
        return true;
    }
}
```

Usage:

```php
if ($user->canFollow($target)) {
    $user->follow($target);
} else {
    return back()->withErrors(['follow' => 'Cannot follow this user']);
}
```

## Form Request Validation

Validate before controller action:

```php
namespace App\Http\Requests;

use Akira\Followable\Concerns\Followable;
use Illuminate\Foundation\Http\FormRequest;

class FollowUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $target = $this->route('user');
        
        // Can't follow self
        if ($user->id === $target->id) {
            return false;
        }
        
        // Must be followable
        if (!in_array(Followable::class, class_uses($target))) {
            return false;
        }
        
        return true;
    }
    
    public function rules(): array
    {
        return [];
    }
    
    public function messages(): array
    {
        return [
            'authorize' => 'You cannot follow this user',
        ];
    }
}
```

Controller:

```php
public function follow(FollowUserRequest $request, User $user)
{
    auth()->user()->follow($user);
    
    return back()->with('success', 'Following user');
}
```

## Testing Exception Handling

```php
use Akira\Followable\Exceptions\CannotFollowYourSelfException;
use Akira\Followable\Exceptions\FollowableTraitNotFoundException;

test('throws exception when following self', function () {
    $user = User::factory()->create();
    
    expect(fn() => $user->follow($user))
        ->toThrow(CannotFollowYourSelfException::class);
});

test('throws exception when following non-followable model', function () {
    $user = User::factory()->create();
    $nonFollowable = new class extends Model {};
    
    expect(fn() => $user->follow($nonFollowable))
        ->toThrow(FollowableTraitNotFoundException::class);
});

test('api returns error for self follow', function () {
    $user = User::factory()->create();
    
    $this->actingAs($user)
        ->postJson(route('api.users.follow', $user))
        ->assertStatus(422)
        ->assertJson([
            'error' => 'You cannot follow yourself',
        ]);
});
```

## Logging Exceptions

```php
try {
    $user->follow($target);
} catch (\Exception $e) {
    logger()->error('Follow failed', [
        'user_id' => $user->id,
        'target_id' => $target->id,
        'exception' => get_class($e),
        'message' => $e->getMessage(),
    ]);
    
    throw $e;
}
```

## Custom Exception Messages

If you need localized or custom messages, catch and re-throw with your own message:

```php
try {
    $user->follow($target);
} catch (CannotFollowYourSelfException $e) {
    throw new \Exception(__('messages.cannot_follow_self'));
}
```

Or extend the exceptions:

```php
namespace App\Exceptions;

use Akira\Followable\Exceptions\CannotFollowYourSelfException as BaseException;

class CannotFollowYourSelfException extends BaseException
{
    public function __construct()
    {
        parent::__construct();
        $this->message = __('You cannot follow your own account');
    }
}
```

---

**Previous:** [Events](09-events.md) | **Next:** [Attach Follow Status](11-attach-follow-status.md)
