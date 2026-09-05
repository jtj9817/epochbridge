<?php

use App\Models\User;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the registration screen', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

it('registers new users via the Fortify registration endpoint', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();

    expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
})->skip(fn () => ! Features::enabled(Features::registration()), 'Registration is not enabled.');

it('validates registration input', function () {
    $response = $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertSessionHasErrors(['password']);

    $this->assertGuest();
})->skip(fn () => ! Features::enabled(Features::registration()), 'Registration is not enabled.');

it('keeps the registration endpoint unavailable while the feature is disabled', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeIn([404, 405]);

    $this->assertGuest();
    expect(User::where('email', 'test@example.com')->exists())->toBeFalse();
})->skip(fn () => Features::enabled(Features::registration()), 'Registration is enabled.');
