<?php

use App\Models\User;
use Laravel\Dusk\Browser;

test('shows toast notification when authenticated user saves conversion', function () {
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

test('shows success toast when guest user saves conversion', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->waitForLivewireToLoad()
            ->type('input[name="dateInput"]', '2024-01-01')
            ->click('button:contains("Convert Date")')
            ->waitForText('Conversion Results', 10)
            ->click('button:contains("Save")')
            ->waitForText('Conversion saved! (Guest mode: 3 max)', 5)
            ->assertVisible('.fixed.top-4.right-4')
            ->assertSee('Conversion saved! (Guest mode: 3 max)')
            ->pause(4000)
            ->assertDontSee('Conversion saved! (Guest mode: 3 max)');
    });
});

test('shows info toast when guest user saves already saved conversion', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->waitForLivewireToLoad()
            ->type('input[name="dateInput"]', '2024-01-01')
            ->click('button:contains("Convert Date")')
            ->waitForText('Conversion Results', 10)
            ->click('button:contains("Save")')
            ->waitForText('Conversion saved! (Guest mode: 3 max)', 5)
            ->pause(4000) // Wait for first toast to dismiss
            ->click('button:contains("Save")')
            ->waitForText('This conversion is already saved!', 5)
            ->assertVisible('.fixed.top-4.right-4')
            ->assertSee('This conversion is already saved!');
    });
});

test('shows info toast when guest user reaches save limit', function () {
    $this->browse(function (Browser $browser) {
        $browser->visit('/')
            ->waitForLivewireToLoad();

        // Save 3 conversions (the limit)
        for ($i = 1; $i <= 3; $i++) {
            $browser->type('input[name="dateInput"]', "2024-01-0{$i}")
                ->click('button:contains("Convert Date")')
                ->waitForText('Conversion Results', 10)
                ->click('button:contains("Save")')
                ->pause(4000); // Wait for toast to dismiss
        }

        // Try to save a 4th conversion
        $browser->type('input[name="dateInput"]', '2024-01-04')
            ->click('button:contains("Convert Date")')
            ->waitForText('Conversion Results', 10)
            ->click('button:contains("Save")')
            ->waitForText('Guest limit reached (3 max). Register for unlimited saves!', 5)
            ->assertVisible('.fixed.top-4.right-4')
            ->assertSee('Guest limit reached (3 max). Register for unlimited saves!');
    });
});
