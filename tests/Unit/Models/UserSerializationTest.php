<?php

use App\Models\User;

it('hides sensitive attributes from user serialization', function () {
    $user = (new User)->forceFill([
        'name' => 'Test User',
        'email' => 'test@example.com',
        'two_factor_secret' => 'totp-secret',
        'two_factor_recovery_codes' => '["code-one", "code-two"]',
        'two_factor_confirmed_at' => now(),
    ]);

    $serialized = $user->toArray();

    expect($serialized)
        ->not->toHaveKey('password')
        ->not->toHaveKey('remember_token')
        ->not->toHaveKey('two_factor_secret')
        ->not->toHaveKey('two_factor_recovery_codes')
        ->not->toHaveKey('two_factor_confirmed_at')
        ->toHaveKey('name')
        ->toHaveKey('email');
});
