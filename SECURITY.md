# Security Policy

## Supported Versions

We release patches for security vulnerabilities for the following versions:

| Version | Supported          |
|---------|--------------------|
| 0.2.x   | :white_check_mark: |
| 0.1.x   | :x:                |
| < 0.1   | :x:                |

## Supported Laravel Versions

Laravel Followable supports:

- Laravel 12.x
- Laravel 11.x
- PHP 8.3+

## Reporting a Vulnerability

We take the security of Laravel Followable seriously. If you discover a security vulnerability, please follow these
steps:

### Please DO NOT

- Open a public GitHub issue for security vulnerabilities
- Disclose the vulnerability publicly before it has been addressed

### Please DO

1. **Email us directly** at kidiatoliny@akira-io.com with:

- A description of the vulnerability
- Steps to reproduce the issue
- Potential impact
- Any suggested fixes (optional)

2. **Use the subject line:** `[SECURITY] Laravel Followable - Brief Description`

3. **Include:**

- Your contact information
- Laravel Followable version affected
- Laravel version
- PHP version
- Detailed description with code examples if possible

### What to Expect

- **Acknowledgment:** We will acknowledge receipt of your report within 48 hours
- **Assessment:** We will assess the vulnerability and determine its severity
- **Updates:** We will keep you informed of our progress
- **Resolution:** We will work to resolve the issue as quickly as possible
- **Credit:** With your permission, we will credit you in the security advisory

### Response Timeline

- **Initial Response:** Within 48 hours
- **Status Update:** Within 7 days
- **Patch Release:** Depends on severity
- Critical: Within 7 days
- High: Within 14 days
- Medium: Within 30 days
- Low: Next scheduled release

## Security Best Practices

When using Laravel Followable, follow these security best practices:

### Input Validation

Always validate user input before passing to follow methods:

```php
// In your FormRequest
public function authorize(): bool
{
    $target = $this->route('user');
    return $target->id !== $this->user()->id; // Prevent self-follow
}
```

### Rate Limiting

Implement rate limiting for follow/unfollow actions:

```php
// In routes/web.php
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::post('users/{user}/follow', [FollowController::class, 'follow']);
});
```

### CSRF Protection

Ensure all follow/unfollow forms include CSRF protection:

```blade
<form method="POST" action="{{ route('users.follow', $user) }}">
    @csrf
    <button type="submit">Follow</button>
</form>
```

### Authorization

Always verify the authenticated user has permission to perform actions:

```php
public function follow(User $user)
{
    if (auth()->user()->cannot('follow', $user)) {
        abort(403);
    }
    
    auth()->user()->follow($user);
}
```

### SQL Injection Prevention

The package uses Eloquent ORM and query builder, which provides protection against SQL injection. However, always:

- Use parameter binding
- Never concatenate user input into queries
- Validate and sanitize user input

### Mass Assignment Protection

Configure `$fillable` or `$guarded` properties on models:

```php
class User extends Model
{
    protected $fillable = ['name', 'email'];
}
```

## Known Security Considerations

### Public Follow Data

By default, follow relationships are public. If you need private follows:

- Implement authorization policies
- Add visibility fields to track private follows
- Control access in your application logic

### Rate Limiting

The package does not include built-in rate limiting. Implement this at the application level:

```php
// In your controller
use Illuminate\Support\Facades\RateLimiter;

public function follow(User $user)
{
    $key = 'follow-' . auth()->id();
    
    if (RateLimiter::tooManyAttempts($key, 100)) {
        return back()->withErrors(['Too many follow attempts']);
    }
    
    RateLimiter::hit($key, 3600); // 1 hour
    
    auth()->user()->follow($user);
}
```

### Data Privacy

Be aware of data privacy regulations (GDPR, CCPA, etc.):

- Allow users to export their follow data
- Implement right to erasure (delete follow data)
- Document data retention policies
- Obtain consent where required

## Security Updates

Security updates will be released as patch versions and announced through:

- GitHub Security Advisories
- Package changelog
- Email notification to kidiatoliny@akira-io.com subscribers

## Acknowledgments

We appreciate security researchers and the Laravel community for helping keep this package secure.

## Contact

For security concerns: kidiatoliny@gmail.com  
For general inquiries: https://github.com/kidiatoliny/laravel-followable/issues
