<?php

namespace Tests\Browser;

use App\Models\SavedConversion;
use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class SavedConversionsTest extends DuskTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up localStorage before each test
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->script('localStorage.clear();');
        });
    }

    /** @test */
    public function it_displays_saved_conversions_for_authenticated_user()
    {
        $user = User::factory()->create();
        $conversionData = [
            'gregorian' => [
                'full_date' => '2024-01-15',
                'name' => 'Gregorian',
                'tags' => ['modern', 'solar'],
            ],
            'jalali' => [
                'full_date' => '1402/10/24',
                'name' => 'Jalali',
                'tags' => ['modern', 'solar'],
            ],
            'unix' => 1705276800,
            'iso8601' => '2024-01-15T00:00:00Z',
            'hebrew' => [
                'full_date' => '5784/11/04',
                'name' => 'Hebrew',
                'tags' => ['historical', 'lunar'],
            ],
            'islamic' => [
                'full_date' => '1445/07/04',
                'name' => 'Islamic',
                'tags' => ['historical', 'lunar'],
            ],
        ];

        SavedConversion::factory()->for($user)->create([
            'original_input' => '2024-01-15',
            'conversion_data' => $conversionData,
            'gregorian_date' => '2024-01-15',
            'jalali_date' => '1402/10/24',
            'unix_timestamp' => 1705276800,
            'iso8601_string' => '2024-01-15T00:00:00Z',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                ->assertSee('Gregorian')
                ->assertSee('Jalali')
                ->assertSee('Hebrew')
                ->assertSee('Islamic')
                ->assertSee('1402/10/24')
                ->assertSee('Input:')
                ->assertSee('Saved:');
        });
    }

    /** @test */
    public function it_can_save_and_display_conversion_for_authenticated_user()
    {
        $user = User::factory()->create();

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitForText('Convert Date')
                ->type('dateInput', '2024-01-15')
                ->click('#btn-convert-date')
                ->waitForText('Gregorian', 10)
                ->waitForText('Jalali', 10)
                ->click('button[wire\\:click*="saveConversion"]')
                ->waitForText('conversion-saved') // Event is dispatched
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                ->assertSee('Gregorian')
                ->assertSee('Jalali');
        });

        $this->assertDatabaseHas('saved_conversions', [
            'user_id' => $user->id,
            'original_input' => '2024-01-15',
        ]);
    }

    /** @test */
    public function it_can_delete_saved_conversion()
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->for($user)->create([
            'original_input' => '2024-01-15',
            'conversion_data' => [
                'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
                'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
                'unix' => 1705276800,
                'iso8601' => '2024-01-15T00:00:00Z',
            ],
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                ->click('button[wire\\:click*="deleteConversion"]')
                ->waitForDialog()
                ->acceptDialog()
                ->waitUntilMissingText('2024-01-15');
        });

        $this->assertDatabaseMissing('saved_conversions', [
            'id' => $conversion->id,
        ]);
    }

    /** @test */
    public function guest_user_can_save_conversion_to_localstorage()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForText('Convert Date')
                ->type('dateInput', '2024-01-15')
                ->click('#btn-convert-date')
                ->waitForText('Gregorian', 10)
                ->waitForText('Jalali', 10)
                ->click('button[onclick*="save-guest-conversion"]') // Guest save button
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                ->assertSee('Gregorian')
                ->assertSee('Jalali');
        });
    }

    /** @test */
    public function guest_user_can_delete_conversion_from_localstorage()
    {
        $this->browse(function (Browser $browser) {
            // First save a conversion
            $browser->visit('/')
                ->waitForText('Convert Date')
                ->type('dateInput', '2024-01-15')
                ->click('#btn-convert-date')
                ->waitForText('Gregorian', 10)
                ->click('button[onclick*="save-guest-conversion"]')
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                    // Then delete it
                ->click('button[onclick*="deleteSaved"]')
                ->waitUntilMissingText('2024-01-15');
        });
    }

    /** @test */
    public function guest_user_is_limited_to_three_conversions()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitForText('Convert Date');

            // Save 3 conversions
            for ($i = 1; $i <= 3; $i++) {
                $browser->type('dateInput', "2024-01-{$i}")
                    ->click('#btn-convert-date')
                    ->waitForText('Gregorian', 10)
                    ->click('button[onclick*="save-guest-conversion"]')
                    ->pause(500); // Wait for save to complete
            }

            // Try to save 4th conversion
            $browser->type('dateInput', '2024-01-04')
                ->click('#btn-convert-date')
                ->waitForText('Gregorian', 10)
                ->click('button[onclick*="save-guest-conversion"]')
                ->waitForText('You\'ve reached the guest limit of 3 saved conversions')
                ->assertSee('Create an account');
        });
    }

    /** @test */
    public function it_displays_all_calendar_systems_in_saved_conversion()
    {
        $user = User::factory()->create();
        $conversionData = [
            'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
            'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
            'unix' => 1705276800,
            'iso8601' => '2024-01-15T00:00:00Z',
            'hebrew' => ['full_date' => '5784/11/04', 'name' => 'Hebrew', 'tags' => ['historical']],
            'islamic' => ['full_date' => '1445/07/04', 'name' => 'Islamic', 'tags' => ['historical']],
            'french_republican' => ['full_date' => '25 Nivôse 232', 'name' => 'French Republican', 'tags' => ['historical']],
            'chinese' => ['full_date' => '2023/12/05', 'name' => 'Chinese', 'tags' => ['historical']],
            'japanese' => ['full_date' => 'Reiwa 6/01/15', 'name' => 'Japanese', 'tags' => ['modern']],
        ];

        SavedConversion::factory()->for($user)->create([
            'original_input' => '2024-01-15',
            'conversion_data' => $conversionData,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitForText('Your Saved Conversions')
                ->assertSee('Gregorian')
                ->assertSee('Jalali')
                ->assertSee('Hebrew')
                ->assertSee('Islamic')
                ->assertSee('French Republican')
                ->assertSee('Chinese')
                ->assertSee('Japanese');
        });
    }

    /** @test */
    public function it_handles_old_format_conversions_with_backward_compatibility()
    {
        $user = User::factory()->create();

        // Create old format conversion without conversion_data
        SavedConversion::factory()->for($user)->create([
            'original_input' => '2024-01-15',
            'gregorian_date' => '2024-01-15',
            'jalali_date' => '1402/10/24',
            'unix_timestamp' => 1705276800,
            'iso8601_string' => '2024-01-15T00:00:00Z',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                ->assertSee('1402/10/24')
                ->assertSee('Gregorian:')
                ->assertSee('Jalali:')
                ->assertSee('Unix:');
        });
    }

    /** @test */
    public function it_displays_conversions_responsively_on_mobile()
    {
        $user = User::factory()->create();
        $conversionData = [
            'gregorian' => ['full_date' => '2024-01-15', 'name' => 'Gregorian', 'tags' => ['modern']],
            'jalali' => ['full_date' => '1402/10/24', 'name' => 'Jalali', 'tags' => ['modern']],
            'unix' => 1705276800,
            'iso8601' => '2024-01-15T00:00:00Z',
        ];

        SavedConversion::factory()->for($user)->create([
            'original_input' => '2024-01-15',
            'conversion_data' => $conversionData,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->resize(375, 667) // Mobile viewport
                ->waitForText('Your Saved Conversions')
                ->assertSee('2024-01-15')
                ->assertSee('Gregorian')
                ->assertSee('Jalali');
        });
    }
}
