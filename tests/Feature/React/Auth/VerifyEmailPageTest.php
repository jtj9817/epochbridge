<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React verify email page for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/VerifyEmailPage')
        ->has('status')
    );
});

it('redirects verified user to dashboard from verify email page', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('verification.notice'));

    $response->assertRedirect(route('dashboard', absolute: false));
});

it('requires authentication to access verify email page', function () {
    $response = $this->get(route('verification.notice'));

    $response->assertRedirect(route('login'));
});

it('passes status flash to verify email page', function () {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)
        ->withSession(['status' => 'verification-link-sent'])
        ->get(route('verification.notice'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', 'verification-link-sent')
    );
});
