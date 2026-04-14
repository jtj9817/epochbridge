<?php

use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React forgot password page', function () {
    $response = $this->get(route('password.request'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/ForgotPasswordPage')
        ->has('status')
    );
});

it('passes status flash to forgot password page', function () {
    $response = $this->withSession(['status' => 'A reset link will be sent if the account exists.'])->get(route('password.request'));

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->where('status', 'A reset link will be sent if the account exists.')
    );
});
