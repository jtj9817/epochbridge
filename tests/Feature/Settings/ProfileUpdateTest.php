<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('displays the profile page', function () {
    $this->actingAs($user = User::factory()->create());

    $this->get(route('profile.edit'))->assertOk();
});

it('updates profile information via the Fortify endpoint', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->put(route('user-profile-information.update'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);

    $response->assertSessionHasNoErrors();

    $user->refresh();

    expect($user->name)->toEqual('Test User');
    expect($user->email)->toEqual('test@example.com');
    expect($user->email_verified_at)->toBeNull();
})->skip(
    fn () => ! (Features::enabled(Features::updateProfileInformation()) && Route::has('user-profile-information.update')),
    'Profile information updating is not enabled.'
);

it('keeps the email verification status unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->put(route('user-profile-information.update'), [
        'name' => 'Test User',
        'email' => $user->email,
    ]);

    $response->assertSessionHasNoErrors();

    expect($user->refresh()->email_verified_at)->not->toBeNull();
})->skip(
    fn () => ! (Features::enabled(Features::updateProfileInformation()) && Route::has('user-profile-information.update')),
    'Profile information updating is not enabled.'
);

it('keeps the profile update endpoint unavailable while the feature is disabled', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->put('/user/profile-information', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    expect($response->status())->toBeIn([404, 405]);

    $user->refresh();

    expect($user->name)->not->toEqual('Test User');
})->skip(
    fn () => Features::enabled(Features::updateProfileInformation()) && Route::has('user-profile-information.update'),
    'Profile information updating is enabled.'
);

it('deletes the account with a valid password', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->delete(route('current-user.destroy'), [
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    expect($user->fresh())->toBeNull();
    expect(auth()->check())->toBeFalse();
})->skip(fn () => ! Route::has('current-user.destroy'), 'Account deletion is not registered.');

it('requires the correct password to delete the account', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->delete(route('current-user.destroy'), [
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors(['password']);

    expect($user->fresh())->not->toBeNull();
})->skip(fn () => ! Route::has('current-user.destroy'), 'Account deletion is not registered.');
