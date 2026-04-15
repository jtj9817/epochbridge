<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React password settings page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('password.edit'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/PasswordPage')
    );
});

it('requires authentication to access password settings', function () {
    $response = $this->get(route('password.edit'));

    $response->assertRedirect(route('login'));
});
