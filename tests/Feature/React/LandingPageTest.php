<?php

declare(strict_types=1);

use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia;

it('renders the react landing page', function () {
    $response = $this->get('/react');

    $response->assertSuccessful();
});

it('passes today date to the landing page', function () {
    Carbon::setTestNow(Carbon::create(2024, 7, 4, 10, 0, 0));

    $response = $this->get('/react');

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('LandingPage')
        ->has('todayDate')
        ->where('todayDate', '2024-07-04')
    );

    Carbon::setTestNow();
});
