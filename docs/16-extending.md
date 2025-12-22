# Extending the Package

This guide explains how to extend Laravel Followable with custom functionality.

## Extending the Followable Model

Create a custom model that extends the base `Followable` model:

```php
namespace App\Models;

use Akira\Followable\Followable as BaseFollowable;

class CustomFollowable extends BaseFollowable
{
    /**
     * Add custom relationship
     */
    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }
    
    /**
     * Add custom scope
     */
    public function scopeRecent($query)
    {
        return $query->where('created_at', '>=', now()->subDays(7));
    }
    
    /**
     * Add custom attribute
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->created_at->gt(now()->subDays(7));
    }
}
```

Update configuration:

```php
// config/followable.php
'followables_model' => \App\Models\CustomFollowable::class,
```

## Adding Custom Migration Columns

Create a new migration to add custom columns:

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
            $table->ipAddress('ip_address')->nullable()->after('source');
        });
    }
    
    public function down(): void
    {
        Schema::table('followables', function (Blueprint $table) {
            $table->dropColumn(['note', 'source', 'ip_address']);
        });
    }
};
```

## Custom Events

Create additional events for custom actions:

```php
namespace App\Events;

use Akira\Followable\Followable;
use Illuminate\Foundation\Events\Dispatchable;

class FollowRequestAccepted
{
    use Dispatchable;
    
    public function __construct(public Followable $follow) {}
}
```

Dispatch in your code:

```php
$user->acceptFollowRequestFrom($follower);
FollowRequestAccepted::dispatch($follow);
```

## Custom Traits

Add additional traits for specialized functionality:

```php
namespace App\Concerns;

trait BlockableFollower
{
    public function blockedUsers()
    {
        return $this->belongsToMany(User::class, 'blocked_users');
    }
    
    public function follow(Model $followable): array
    {
        if ($followable->blockedUsers->contains($this)) {
            throw new \Exception('You are blocked by this user');
        }
        
        return parent::follow($followable);
    }
}
```

---

**Previous:** [Testing](15-testing.md) | **Next:** [API Reference](17-api-reference.md)
