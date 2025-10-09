<?php

namespace Tests\Browser;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LandingPageTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_landing_page_conversion_flow(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->assertSee('EpochBridge')
                    ->type('date-input-gregorian', '2024-10-27')
                    ->press('Convert Date')
                    ->waitForText('Conversion Results')
                    ->assertSee('Gregorian')
                    ->assertSee('2024-10-27')
                    ->assertSee('Jalali')
                    ->assertSee('1403/08/06');
        });
    }

    public function test_quick_actions_work(): void
    {
        $this->browse(function (Browser $browser) {
            $today = now()->format('Y-m-d');
            $browser->visit('/')
                    ->press('TODAY')
                    ->press('Convert Date')
                    ->waitForText('Conversion Results')
                    ->assertSee($today);
        });
    }

    public function test_search_and_filter_works(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                    ->type('date-input-gregorian', '2024-01-01')
                    ->press('Convert Date')
                    ->waitForText('Conversion Results')
                    ->type('input[placeholder="Search calendars..."]', 'Jalali')
                    ->assertSee('Jalali')
                    ->assertDontSee('Gregorian')
                    ->clear('input[placeholder="Search calendars..."]')
                    ->press('Show Filters')
                    ->check('selectedTypes[]', 'lunar')
                    ->assertSee('Islamic')
                    ->assertDontSee('Gregorian');
        });
    }
    
    public function test_guest_can_save_and_delete_conversions(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->type('date-input-gregorian', '2024-01-01')
                ->press('Convert Date')
                ->waitForText('Conversion Results')
                ->press('Save')
                ->waitForText('Your Saved Conversions')
                ->assertSeeIn('#saved-conversions-guest', '2024-01-01')
                ->with('#saved-conversions-guest', function ($guestConversions) {
                    $guestConversions->press('Delete');
                })
                ->waitUntilMissingText('2024-01-01');
        });
    }
}
