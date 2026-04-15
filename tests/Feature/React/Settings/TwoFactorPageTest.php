<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React two-factor settings page', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Settings/TwoFactorPage')
        ->has('twoFactorEnabled')
        ->has('requiresConfirmation')
    );
});

it('passes two factor enabled state as false by default', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('twoFactorEnabled', false)
        ->where('requiresConfirmation', true)
    );
});

it('passes two factor enabled state as true when enabled', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'two_factor_secret' => encrypt('secret'),
        'two_factor_confirmed_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('two-factor.show'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('twoFactorEnabled', true)
    );
});

it('requires authentication to access two factor settings', function () {
    $response = $this->get(route('two-factor.show'));

    $response->assertRedirect(route('login'));
});

it('requires password confirmation to access two factor settings', function () {
    $user = User::factory()->create(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get(route('two-factor.show'));

    $response->assertRedirect(route('password.confirm'));
});
