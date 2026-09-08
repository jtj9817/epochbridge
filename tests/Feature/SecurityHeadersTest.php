<?php

it('sets security headers on web responses', function () {
    $response = $this->get('/');

    $response->assertOk();

    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        ->and($response->headers->get('X-Frame-Options'))->toBe('DENY')
        ->and($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'")
        ->and($response->headers->get('Content-Security-Policy'))->toContain("object-src 'none'")
        ->and($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'none'");
});

it('does not send hsts over plain http', function () {
    $response = $this->get('/');

    $response->assertOk()
        ->assertHeaderMissing('Strict-Transport-Security');
});
