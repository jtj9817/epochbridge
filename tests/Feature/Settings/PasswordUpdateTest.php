<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('updates the password via the Fortify endpoint', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = $this->put(route('user-password.update'), [
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
})->skip(
    fn () => ! (Features::enabled(Features::updatePasswords()) && Route::has('user-password.update')),
    'Password updating is not enabled.'
);

it('requires the correct current password to update the password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($user);

    $response = $this->put(route('user-password.update'), [
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ]);

    $response->assertSessionHasErrors(['current_password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
})->skip(
    fn () => ! (Features::enabled(Features::updatePasswords()) && Route::has('user-password.update')),
    'Password updating is not enabled.'
);

it('keeps the password update endpoint unavailable while the feature is disabled', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $response = $this->actingAs($user)
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    expect($response->status())->toBeIn([404, 405]);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
})->skip(
    fn () => Features::enabled(Features::updatePasswords()) && Route::has('user-password.update'),
    'Password updating is enabled.'
);
