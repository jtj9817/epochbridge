<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React appearance settings page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('appearance.edit'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/AppearancePage')
    );
});

it('requires authentication to access appearance settings', function () {
    $response = $this->get(route('appearance.edit'));

    $response->assertRedirect(route('login'));
});
