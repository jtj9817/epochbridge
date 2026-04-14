<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React confirm password page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('password.confirm'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/ConfirmPasswordPage')
    );
});

it('requires authentication to access confirm password page', function () {
    $response = $this->get(route('password.confirm'));

    $response->assertRedirect(route('login'));
});
