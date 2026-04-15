<?php

use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React login page', function () {
    $response = $this->get(route('login'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/LoginPage')
        ->has('canResetPassword')
        ->has('canRegister')
        ->has('status')
        ->has('oauthError')
    );
});

it('passes canResetPassword and canRegister as true', function () {
    $response = $this->get(route('login'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('canResetPassword', true)
        ->where('canRegister', true)
    );
});

it('passes oauth error from session errors to login page', function () {
    $response = $this->withSession(['errors' => (new \Illuminate\Support\ViewErrorBag)->put('default', new \Illuminate\Support\MessageBag([
        'oauth' => 'Authentication failed. Please try again.',
    ]))])->get(route('login'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('oauthError', 'Authentication failed. Please try again.')
    );
});

it('passes null oauthError when no oauth errors exist', function () {
    $response = $this->get(route('login'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('oauthError', null)
    );
});

it('passes status flash to login page', function () {
    $response = $this->withSession(['status' => 'Your password has been reset.'])->get(route('login'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', 'Your password has been reset.')
    );
});

it('passes null status when no status flash exists', function () {
    $response = $this->get(route('login'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', null)
    );
});
