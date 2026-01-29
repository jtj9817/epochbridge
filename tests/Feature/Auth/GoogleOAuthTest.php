<?php

use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

test('oauth redirect includes required parameters', function () {
    $response = $this->get(route('social.redirect', 'google'));

    $response->assertRedirect();

    $location = $response->headers->get('Location');
    expect($location)
        ->toContain('client_id')
        ->toContain('redirect_uri')
        ->toContain('response_type=code')
        ->toContain('state')
        ->toContain('access_type=offline')
        ->toContain('prompt=consent');
});

test('user can authenticate with google', function () {
    mockSocialite('[email protected]', '123', 'Test User');

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs(
        User::where('email', '[email protected]')->first()
    );

    $this->assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_id' => '123',
    ]);

    $this->assertDatabaseHas('users', [
        'email' => '[email protected]',
        'name' => 'Test User',
    ]);
});

test('existing user can link google account', function () {
    $user = User::factory()->create([
        'email' => '[email protected]',
        'password' => 'password',
    ]);

    mockSocialite('[email protected]', 'new_123', 'Test User');

    $response = $this->get(route('social.callback', 'google'));

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('social_accounts', [
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'new_123',
    ]);

    expect($user->fresh()->socialAccounts()->count())->toBe(1);
});

test('oauth error is handled gracefully', function () {
    $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('enablePKCE')->andReturn($provider);
    $provider->shouldReceive('user')->andThrow(new \Exception('OAuth error'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['oauth']);
});

test('oauth callback rejects invalid state', function () {
    $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('enablePKCE')->andReturn($provider);
    $provider->shouldReceive('user')->andThrow(new \Laravel\Socialite\Two\InvalidStateException());

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['oauth']);
});

test('missing email from provider is rejected', function () {
    mockSocialite(null, '123', 'Test User');

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['oauth']);
    $this->assertGuest();
});

test('user cancelled oauth flow is handled', function () {
    $response = $this->get(route('social.callback', 'google').'?error=access_denied&error_description=User+cancelled');

    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['oauth']);
});

test('returning user has tokens updated', function () {
    $user = User::factory()->create(['email' => '[email protected]']);

    $socialAccount = SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => '123',
        'access_token' => 'old_token',
        'refresh_token' => 'old_refresh',
        'expires_in' => 3600,
    ]);

    mockSocialite('[email protected]', '123', 'Test User', 'new_token', 'new_refresh');

    $response = $this->get(route('social.callback', 'google'));

    $response->assertRedirect('/dashboard');

    $socialAccount->refresh();
    expect($socialAccount->access_token)->toBe('new_token');
    expect($socialAccount->refresh_token)->toBe('new_refresh');
});

test('oauth user has email automatically verified', function () {
    mockSocialite('[email protected]', '123', 'Test User');

    $this->get(route('social.callback', 'google'));

    $user = User::where('email', '[email protected]')->first();
    expect($user->email_verified_at)->not->toBeNull();
});

test('oauth only user has null password', function () {
    mockSocialite('[email protected]', '123', 'Test User');

    $this->get(route('social.callback', 'google'));

    $user = User::where('email', '[email protected]')->first();
    expect($user->password)->toBeNull();
    expect($user->hasPassword())->toBeFalse();
});

test('invalid provider is rejected', function () {
    $response = $this->get('/auth/facebook/redirect');

    $response->assertNotFound();
});

function mockSocialite($email = '[email protected]', $id = '123', $name = 'Test User', $token = 'mock_access_token', $refreshToken = 'mock_refresh_token')
{
    $socialiteUser = \Mockery::mock(SocialiteUser::class);
    $socialiteUser->shouldReceive('getId')->andReturn($id);
    $socialiteUser->shouldReceive('getEmail')->andReturn($email);
    $socialiteUser->shouldReceive('getName')->andReturn($name);
    $socialiteUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');
    $socialiteUser->shouldReceive('getNickname')->andReturn('testuser');
    $socialiteUser->token = $token;
    $socialiteUser->refreshToken = $refreshToken;
    $socialiteUser->expiresIn = 3600;

    $provider = \Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($socialiteUser);
    $provider->shouldReceive('enablePKCE')->andReturn($provider);
    $provider->shouldReceive('with')->andReturn($provider);
    $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth?client_id=test&redirect_uri=http://localhost/auth/google/callback&response_type=code&state=test&access_type=offline&prompt=consent'));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->andReturn($provider);
}
