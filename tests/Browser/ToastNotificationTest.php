<?php

use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Dusk reuses a single browser across the tests in this class, so guest tests
 * must clear the session cookie and guest localStorage left by earlier tests.
 * Cookies are deleted on the already-quiet page: reloading first would re-fire
 * authenticated XHRs whose Set-Cookie responses would restore the session.
 */
function resetGuestState(Browser $browser): void
{
    $browser->driver->manage()->deleteAllCookies();
    $browser->script('localStorage.clear();');
}

test('shows toast notification when authenticated user saves conversion', function () {
    $this->browse(function (Browser $browser) {
        $user = User::factory()->create();

        $browser->loginAs($user)
            ->visit('/')
            ->waitFor('#date-input')
            ->type('#date-input', '01012024')
            ->click('#btn-convert-date')
            ->waitFor('#conversion-results-section')
            ->click('[data-test="save-current-button"]')
            ->waitFor('div[role="dialog"]')
            ->click('[data-test="modal-save-button"]')
            ->waitForText('Conversion saved successfully!', 5)
            ->assertVisible('.fixed.bottom-4.right-4')
            ->assertSee('Conversion saved successfully!')
            ->pause(4500) // Wait for toast to auto-dismiss
            ->assertDontSee('Conversion saved successfully!');
    });
});

test('shows success toast when guest user saves conversion', function () {
    $this->browse(function (Browser $browser) {
        resetGuestState($browser);

        $browser->visit('/')
            ->waitFor('#date-input')
            ->type('#date-input', '01012024')
            ->click('#btn-convert-date')
            ->waitFor('#conversion-results-section')
            ->click('[data-test="save-current-button"]')
            ->waitFor('div[role="dialog"]')
            ->click('[data-test="modal-save-button"]')
            ->waitForText('Conversion saved! (Guest mode: 3 max)', 5)
            ->assertVisible('.fixed.bottom-4.right-4')
            ->assertSee('Conversion saved! (Guest mode: 3 max)')
            ->pause(4500)
            ->assertDontSee('Conversion saved! (Guest mode: 3 max)');
    });
});

test('shows info toast when guest user saves already saved conversion', function () {
    $this->browse(function (Browser $browser) {
        resetGuestState($browser);

        $browser->visit('/')
            ->waitFor('#date-input')
            ->type('#date-input', '01012024')
            ->click('#btn-convert-date')
            ->waitFor('#conversion-results-section')
            ->click('[data-test="save-current-button"]')
            ->waitFor('div[role="dialog"]')
            ->click('[data-test="modal-save-button"]')
            ->waitForText('Conversion saved! (Guest mode: 3 max)', 5)
            ->pause(4500) // Wait for first toast to dismiss
            ->click('[data-test="save-current-button"]')
            ->waitFor('div[role="dialog"]')
            ->click('[data-test="modal-save-button"]')
            ->waitForText('Conversion already saved or limit reached.', 5)
            ->assertVisible('.fixed.bottom-4.right-4')
            ->assertSee('Conversion already saved or limit reached.');
    });
});

test('shows guest limit dialog when guest user reaches save limit', function () {
    $this->browse(function (Browser $browser) {
        resetGuestState($browser);

        $browser->visit('/')
            ->waitFor('#date-input');

        // Save 3 conversions (the limit)
        for ($i = 1; $i <= 3; $i++) {
            $browser->type('#date-input', "010{$i}2024")
                ->click('#btn-convert-date')
                ->waitFor('#conversion-results-section')
                ->click('[data-test="save-current-button"]')
                ->waitFor('div[role="dialog"]')
                ->click('[data-test="modal-save-button"]')
                ->pause(4500); // Wait for toast to dismiss
        }

        // Try to save a 4th conversion: the save is refused with an info toast...
        $browser->type('#date-input', '01042024')
            ->click('#btn-convert-date')
            ->waitFor('#conversion-results-section')
            ->click('[data-test="save-current-button"]')
            ->waitFor('div[role="dialog"]')
            ->click('[data-test="modal-save-button"]')
            ->waitForText('Conversion already saved or limit reached.', 5);

        // ...and the next save attempt opens the guest limit dialog instead.
        $browser->click('[data-test="save-current-button"]')
            ->waitForText('GUEST LIMIT REACHED', 5)
            ->assertSee('You have reached the guest limit of 3 saved conversions.');
    });
});
