<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the reset password link screen', function () {
    $response = $this->get(route('password.request'));

    $response->assertStatus(200);
});

it('requests a reset password link via the Fortify endpoint', function () {
    Notification::fake();

    $user = User::factory()->create();

    $response = $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    $response->assertSessionHasNoErrors();

    Notification::assertSentTo($user, ResetPassword::class);
})->skip(fn () => ! Features::enabled(Features::resetPasswords()), 'Password resetting is not enabled.');

it('renders the reset password screen with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertStatus(200);

        return true;
    });
})->skip(fn () => ! Features::enabled(Features::resetPasswords()), 'Password resetting is not enabled.');

it('resets the password with a valid token', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post(route('password.email'), [
        'email' => $user->email,
    ]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->post(route('password.update'), [
            'token' => $notification->token,
            'email' => $user->email,
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('login', absolute: false));

        return true;
    });
})->skip(fn () => ! Features::enabled(Features::resetPasswords()), 'Password resetting is not enabled.');

it('does not send a reset link when the email address is unknown', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), [
        'email' => 'unknown@example.com',
    ]);

    $response->assertSessionHasErrors(['email']);

    Notification::assertNothingSent();
})->skip(fn () => ! Features::enabled(Features::resetPasswords()), 'Password resetting is not enabled.');

it('keeps the password reset endpoints unavailable while the feature is disabled', function () {
    Notification::fake();

    $user = User::factory()->create();

    $requestLink = $this->post('/forgot-password', [
        'email' => $user->email,
    ]);

    expect($requestLink->status())->toBeIn([404, 405]);

    Notification::assertNothingSent();

    $reset = $this->post('/reset-password/some-token', [
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    expect($reset->status())->toBeIn([404, 405]);
})->skip(fn () => Features::enabled(Features::resetPasswords()), 'Password resetting is enabled.');
