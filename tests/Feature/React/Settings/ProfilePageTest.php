<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React profile settings page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/ProfilePage')
        ->has('user')
        ->has('status')
    );
});

it('passes user data to the profile page', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('user.name', 'Test User')
        ->where('user.email', 'test@example.com')
        ->where('user.has_verified_email', true)
    );
});

it('passes status flash to the profile page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)
        ->withSession(['status' => 'verification-link-sent'])
        ->get(route('profile.edit'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', 'verification-link-sent')
    );
});

it('passes null status when no status flash exists', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('profile.edit'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', null)
    );
});

it('requires authentication to access profile settings', function () {
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect(route('login'));
});
