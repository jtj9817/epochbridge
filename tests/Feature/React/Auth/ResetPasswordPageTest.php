<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React reset password page', function () {
    Notification::fake();

    $user = User::factory()->create();

    Notification::send($user, new ResetPassword('test-token'));

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get(route('password.reset', $notification->token));

        $response->assertSuccessful();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Auth/ResetPasswordPage')
            ->has('token')
            ->has('email')
            ->has('status')
        );

        return true;
    });
});

it('passes token and email to the reset password page', function () {
    Notification::fake();

    $user = User::factory()->create();

    Notification::send($user, new ResetPassword('my-test-token'));

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) use ($user) {
        $response = $this->get(route('password.reset', ['token' => $notification->token, 'email' => $user->email]));

        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->where('token', $notification->token)
            ->where('email', $user->email)
        );

        return true;
    });
});
