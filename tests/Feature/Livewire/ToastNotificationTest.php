<?php

namespace Tests\Feature\Livewire;

use App\Models\User;
use Laravel\Dusk\Browser;

it('shows toast notification when authenticated user saves conversion', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create();
        
        $browser->loginAs($user)
            ->visit('/')
            ->waitForLivewireToLoad()
            ->type('input[name="dateInput"]', '2024-01-01')
            ->click('button:contains("Convert Date")')
            ->waitForText('Conversion Results', 10)
            ->click('button:contains("Save")')
            ->waitForText('Conversion saved successfully!', 5)
            ->assertVisible('.fixed.top-4.right-4')
            ->assertSee('Conversion saved successfully!')
            ->pause(4000) // Wait for toast to auto-dismiss
            ->assertDontSee('Conversion saved successfully!');
    });
});