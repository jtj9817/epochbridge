<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;

it('rate limits registration requests per ip', function () {
    foreach (range(1, 10) as $i) {
        // Invalid payload on purpose: throttle middleware runs before
        // validation, so failed attempts still count against the limiter.
        $this->post(route('register'), [
            'name' => 'Test User',
            'email' => "user{$i}@example.com",
            'password' => 'password',
            'password_confirmation' => 'different',
        ]);
    }

    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'overflow@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(429);

    expect(User::count())->toBe(0);
});

it('rate limits password reset link requests per ip', function () {
    Notification::fake();

    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        $this->post(route('password.email'), ['email' => $user->email]);
    }

    $this->post(route('password.email'), ['email' => $user->email])
        ->assertStatus(429);
});
