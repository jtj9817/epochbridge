<?php

use Inertia\Testing\AssertableInertia;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('renders the React register page', function () {
    $response = $this->get(route('register'));

    $response->assertSuccessful();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Auth/RegisterPage')
    );
});
