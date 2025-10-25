# Google OAuth Integration Guide

## Overview

EpochBridge implements Google OAuth 2.0 authentication using Laravel Socialite, providing users with a seamless "Sign in with Google" experience. This integration supports both new user registration and account linking for existing users.

## Architecture

### Components

1. **SocialAuthController** (`app/Http/Controllers/Auth/SocialAuthController.php`)
   - Handles OAuth redirect and callback flows
   - Manages user creation and account linking
   - Implements error handling and validation

2. **SocialAccount Model** (`app/Models/SocialAccount.php`)
   - Stores OAuth provider credentials
   - Manages encrypted token storage
   - Tracks token expiration

3. **User Model Extensions** (`app/Models/User.php`)
   - Relationship with social accounts
   - Helper methods for provider management
   - Support for nullable password field

4. **OAuth Routes** (`routes/auth.php`)
   - Provider redirect endpoint
   - OAuth callback handler

### Database Schema

#### social_accounts Table
```sql
CREATE TABLE social_accounts (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT NOT NULL,
    provider VARCHAR(255) NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    access_token TEXT NOT NULL,
    refresh_token TEXT NULL,
    expires_in INT NULL,
    avatar VARCHAR(255) NULL,
    nickname VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY social_accounts_provider_provider_id_unique (provider, provider_id),
    INDEX social_accounts_user_id_index (user_id)
);
```

#### users Table Modifications
```sql
ALTER TABLE users 
    MODIFY COLUMN password VARCHAR(255) NULL,
    ADD COLUMN avatar VARCHAR(255) NULL AFTER email_verified_at;
```

## Setup Instructions

### 1. Google Cloud Console Configuration

1. **Create OAuth 2.0 Credentials**
   - Visit [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select existing
   - Navigate to "APIs & Services" > "Credentials"
   - Click "Create Credentials" > "OAuth client ID"
   - Select "Web application"

2. **Configure OAuth Consent Screen**
   - Set application name (e.g., "EpochBridge")
   - Add authorized domains (e.g., `localhost:8080` for development)
   - Configure scopes: `email`, `profile`, `openid`

3. **Add Authorized Redirect URIs**
   - Development: `http://localhost:8080/auth/google/callback`
   - Production: `https://yourdomain.com/auth/google/callback`

4. **Save Credentials**
   - Copy Client ID
   - Copy Client Secret

### 2. Environment Configuration

Add the following to your `.env` file:

```bash
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
```

### 3. Database Migration

Run migrations to add the social accounts table:

```bash
php artisan migrate
```

This will execute:
- `2025_10_24_204329_create_social_accounts_table.php`
- `2025_10_24_204408_modify_users_table_for_social_auth.php`

## Authentication Flow

### OAuth Flow Diagram

```
┌──────────┐                                      ┌──────────┐
│  User    │                                      │  Google  │
└────┬─────┘                                      └────┬─────┘
     │                                                 │
     │  1. Click "Sign in with Google"                │
     │ ─────────────────────────────────>             │
     │         (redirectToProvider)                   │
     │                                                 │
     │  2. Redirect to Google                         │
     │ ──────────────────────────────────────────────>│
     │         (OAuth consent screen)                 │
     │                                                 │
     │  3. User authorizes application                │
     │ <──────────────────────────────────────────────│
     │         (authorization code)                   │
     │                                                 │
     │  4. Callback with auth code                    │
     │ ─────────────────────────────────>             │
     │     (handleProviderCallback)                   │
     │                                                 │
     │  5. Exchange code for tokens                   │
     │ ──────────────────────────────────────────────>│
     │                                                 │
     │  6. Retrieve user profile                      │
     │ <──────────────────────────────────────────────│
     │    (access_token, refresh_token, profile)      │
     │                                                 │
     │  7. Create/link account & login                │
     │ ─────────────────────────────────>             │
     │                                                 │
     │  8. Redirect to dashboard                      │
     │ <─────────────────────────────────             │
     │                                                 │
```

### Step-by-Step Flow

#### 1. Initiate OAuth Flow
```php
public function redirectToProvider(string $provider)
{
    return Socialite::driver($provider)
        ->with([
            'access_type' => 'offline',  // Request refresh token
            'prompt' => 'consent',        // Force consent screen
        ])
        ->redirect();
}
```

**Parameters**:
- `access_type=offline`: Requests refresh token for long-term access
- `prompt=consent`: Forces consent screen to ensure refresh token is always issued

#### 2. Handle OAuth Callback
```php
public function handleProviderCallback(Request $request, string $provider)
{
    // Error handling
    if ($request->has('error')) {
        return $this->handleOAuthError($request);
    }

    // Exchange authorization code for tokens
    $socialUser = Socialite::driver($provider)->stateless()->user();

    // Validate email
    if (!$socialUser->getEmail()) {
        return redirect()->route('login')
            ->withErrors(['oauth' => 'Email not provided by provider.']);
    }

    // Find or create user
    DB::beginTransaction();
    $user = $this->findOrCreateUser($socialUser, $provider);
    DB::commit();

    // Login
    Auth::login($user, true);

    return redirect()->intended(config('fortify.home', '/dashboard'));
}
```

#### 3. User Creation/Linking Logic
```php
protected function findOrCreateUser($socialUser, string $provider)
{
    // Check if social account exists
    $socialAccount = SocialAccount::where('provider', $provider)
        ->where('provider_id', $socialUser->getId())
        ->first();

    if ($socialAccount) {
        // Update tokens and return existing user
        $this->updateSocialAccount($socialAccount, $socialUser);
        return $socialAccount->user;
    }

    // Check if user exists by email
    $user = User::where('email', $socialUser->getEmail())->first();

    if (!$user) {
        // Create new user
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getEmail(),
            'email' => $socialUser->getEmail(),
            'avatar' => $socialUser->getAvatar(),
            'email_verified_at' => now(),  // Auto-verify OAuth users
            'password' => null,             // No password for OAuth-only
        ]);
    }

    // Link social account
    $this->createSocialAccount($user, $provider, $socialUser);

    return $user;
}
```

### Account Linking Scenarios

#### Scenario 1: New OAuth User
```
User visits site → Clicks "Sign in with Google" → Authorizes
→ No existing user found → Create new user with email_verified_at set
→ Create social_account record → Login user
```

#### Scenario 2: Existing User + New OAuth Provider
```
Existing user (email: john@example.com) → Clicks "Sign in with Google"
→ OAuth returns email: john@example.com → User found by email
→ Link new social_account to existing user → Login user
```

#### Scenario 3: Returning OAuth User
```
User with linked Google account → Clicks "Sign in with Google"
→ OAuth callback → social_account found → Update tokens
→ Return existing user → Login user
```

## Frontend Integration

### Login Page Component

The login page includes a Google Sign-In button:

```blade
<!-- resources/views/livewire/auth/login.blade.php -->
<div class="mt-6">
    <div class="relative">
        <div class="absolute inset-0 flex items-center">
            <div class="w-full border-t border-gray-300"></div>
        </div>
        <div class="relative flex justify-center text-sm">
            <span class="px-2 bg-white text-gray-500">Or continue with</span>
        </div>
    </div>

    <div class="mt-6">
        <a href="{{ route('social.redirect', 'google') }}" 
           class="w-full flex items-center justify-center px-4 py-2 border border-gray-300 
                  rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 
                  hover:bg-gray-50 transition-colors">
            <svg class="h-5 w-5 mr-2" viewBox="0 0 24 24">
                <!-- Google logo SVG -->
            </svg>
            Sign in with Google
        </a>
    </div>
</div>
```

### Route Configuration

```php
// routes/auth.php
Route::middleware('guest')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('{provider}/redirect', [SocialAuthController::class, 'redirectToProvider'])
            ->name('social.redirect')
            ->where('provider', 'google');

        Route::get('{provider}/callback', [SocialAuthController::class, 'handleProviderCallback'])
            ->name('social.callback')
            ->where('provider', 'google');
    });
});
```

## Security Considerations

### 1. Token Encryption

All OAuth tokens are encrypted at rest using Laravel's encrypted casting:

```php
// app/Models/SocialAccount.php
protected function casts(): array
{
    return [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_in' => 'integer',
    ];
}
```

### 2. Token Hiding

Tokens are hidden from JSON serialization:

```php
protected $hidden = [
    'access_token',
    'refresh_token',
];
```

### 3. Provider Validation

Only whitelisted providers are allowed:

```php
protected $allowedProviders = ['google'];

protected function validateProvider(string $provider): void
{
    if (!in_array($provider, $this->allowedProviders)) {
        abort(404, 'Invalid provider');
    }
}
```

### 4. CSRF Protection

Socialite automatically handles CSRF protection via the `state` parameter.

### 5. Database Transactions

User creation and account linking are wrapped in database transactions:

```php
DB::beginTransaction();
try {
    $user = $this->findOrCreateUser($socialUser, $provider);
    DB::commit();
} catch (Exception $e) {
    DB::rollBack();
    // Handle error
}
```

### 6. Error Logging

All OAuth errors are logged with context:

```php
Log::error('OAuth callback error', [
    'provider' => $provider,
    'error' => $e->getMessage(),
    'ip' => $request->ip(),
]);
```

### 7. Email Validation

Email is required from OAuth provider:

```php
if (!$socialUser->getEmail()) {
    return redirect()->route('login')
        ->withErrors(['oauth' => 'Email not provided by provider.']);
}
```

## Testing

### Feature Tests

The integration includes comprehensive tests (`tests/Feature/Auth/GoogleOAuthTest.php`):

```php
test('can redirect to Google OAuth', function () {
    $response = $this->get(route('social.redirect', 'google'));
    
    $response->assertRedirect();
    expect($response->headers->get('Location'))
        ->toContain('accounts.google.com/o/oauth2/auth');
});

test('can handle OAuth callback and create new user', function () {
    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getId')->andReturn('google-user-123');
    $mockUser->shouldReceive('getEmail')->andReturn('newuser@example.com');
    $mockUser->shouldReceive('getName')->andReturn('New User');
    $mockUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    
    Socialite::shouldReceive('driver->stateless->user')->andReturn($mockUser);

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    
    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'name' => 'New User',
    ]);
    
    $this->assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_id' => 'google-user-123',
    ]);
});

test('can link Google account to existing user', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    $mockUser = Mockery::mock('Laravel\Socialite\Two\User');
    $mockUser->shouldReceive('getId')->andReturn('google-user-456');
    $mockUser->shouldReceive('getEmail')->andReturn('existing@example.com');
    
    Socialite::shouldReceive('driver->stateless->user')->andReturn($mockUser);

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticatedAs($user);
    
    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-user-456',
    ]);
});
```

### Running Tests

```bash
# Run all OAuth tests
php artisan test --filter GoogleOAuthTest

# Run with verbose output
php artisan test --filter GoogleOAuthTest --verbose
```

## Troubleshooting

### Common Issues

#### 1. Redirect URI Mismatch
**Error**: `redirect_uri_mismatch`

**Solution**: Ensure the redirect URI in Google Cloud Console exactly matches your configured URI:
```bash
# .env
GOOGLE_REDIRECT_URI=http://localhost:8080/auth/google/callback
```

#### 2. Invalid Client
**Error**: `invalid_client`

**Solution**: Verify `GOOGLE_CLIENT_ID` and `GOOGLE_CLIENT_SECRET` are correct in `.env` file.

#### 3. Missing Email Scope
**Error**: Email not provided by provider

**Solution**: Ensure `email` scope is requested in OAuth consent screen configuration.

#### 4. Token Encryption Error
**Error**: `The payload is invalid`

**Solution**: Ensure `APP_KEY` is set in `.env`:
```bash
php artisan key:generate
```

#### 5. Database Constraint Error
**Error**: Duplicate entry for 'provider-provider_id'

**Solution**: User is already linked to this Google account. Use existing social account.

### Debug Logging

Enable detailed OAuth logging:

```php
// config/logging.php
'channels' => [
    'oauth' => [
        'driver' => 'single',
        'path' => storage_path('logs/oauth.log'),
        'level' => 'debug',
    ],
],
```

Then use in controller:

```php
Log::channel('oauth')->debug('OAuth flow initiated', [
    'provider' => $provider,
    'session_id' => session()->getId(),
]);
```

## Future Enhancements

### Potential Features

1. **Multiple OAuth Providers**
   - Add Facebook, GitHub, Twitter support
   - Update `allowedProviders` array
   - Add provider-specific UI buttons

2. **Account Management Page**
   - View linked providers
   - Unlink social accounts
   - Manage connected services

3. **Token Refresh**
   - Automatic refresh token usage
   - Background token renewal
   - Token expiration notifications

4. **Profile Sync**
   - Periodic profile data sync from OAuth provider
   - Avatar update on login
   - Name change detection

5. **OAuth Scopes**
   - Request additional Google API scopes
   - Store scope permissions per account
   - Scope-based feature access

## References

- [Laravel Socialite Documentation](https://laravel.com/docs/socialite)
- [Google OAuth 2.0 Documentation](https://developers.google.com/identity/protocols/oauth2)
- [Google Cloud Console](https://console.cloud.google.com/)
- [OAuth 2.0 RFC 6749](https://tools.ietf.org/html/rfc6749)
