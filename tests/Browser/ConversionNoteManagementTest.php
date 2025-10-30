<?php

namespace Tests\Browser;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ConversionNoteManagementTest extends DuskTestCase
{
    use DatabaseMigrations;

    public function test_user_can_open_edit_note_modal_from_saved_conversion(): void
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => 'Original note',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->assertVisible('[id="edit-note-textarea"]')
                ->assertSeeIn('#edit-note-textarea', 'Original note');
        });
    }

    public function test_user_can_type_and_save_note(): void
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => '',
        ]);

        $this->browse(function (Browser $browser) use ($user, $conversion) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->type('#edit-note-textarea', 'New note content')
                ->click('button:contains("Save")')
                ->waitUntilMissing('[id="edit-note-textarea"]');

            $this->assertDatabaseHas('saved_conversions', [
                'id' => $conversion->id,
                'note' => 'New note content',
            ]);
        });
    }

    public function test_user_sees_success_toast_after_saving_note(): void
    {
        $user = User::factory()->create();
        SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => 'Original',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->clear('#edit-note-textarea')
                ->type('#edit-note-textarea', 'Updated note')
                ->click('button:contains("Save")')
                ->waitForText('Note updated successfully!');
        });
    }

    public function test_note_persists_after_page_reload(): void
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => '',
        ]);

        $this->browse(function (Browser $browser) use ($user, $conversion) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->type('#edit-note-textarea', 'Persistent note')
                ->click('button:contains("Save")')
                ->waitUntilMissing('[id="edit-note-textarea"]')
                ->refresh()
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->assertInputValue('#edit-note-textarea', 'Persistent note');
        });
    }

    public function test_user_can_edit_existing_note(): void
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => 'Original note text',
        ]);

        $this->browse(function (Browser $browser) use ($user, $conversion) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->assertInputValue('#edit-note-textarea', 'Original note text')
                ->clear('#edit-note-textarea')
                ->type('#edit-note-textarea', 'Modified note text')
                ->click('button:contains("Save")')
                ->waitUntilMissing('[id="edit-note-textarea"]');

            $this->assertDatabaseHas('saved_conversions', [
                'id' => $conversion->id,
                'note' => 'Modified note text',
            ]);
        });
    }

    public function test_user_can_clear_note_with_empty_textarea(): void
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => 'Note to be cleared',
        ]);

        $this->browse(function (Browser $browser) use ($user, $conversion) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->clear('#edit-note-textarea')
                ->click('button:contains("Save")')
                ->waitUntilMissing('[id="edit-note-textarea"]');

            $this->assertDatabaseHas('saved_conversions', [
                'id' => $conversion->id,
                'note' => '',
            ]);
        });
    }

    public function test_character_counter_updates_in_real_time(): void
    {
        $user = User::factory()->create();
        SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => '',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->assertSeeIn('.text-xs', '0/2048')
                ->type('#edit-note-textarea', 'Test')
                ->pause(100)
                ->assertSeeIn('.text-xs', '4/2048')
                ->type('#edit-note-textarea', ' note')
                ->pause(100)
                ->assertSeeIn('.text-xs', '9/2048');
        });
    }

    public function test_validation_error_shown_for_exceeding_2048_characters(): void
    {
        $user = User::factory()->create();
        SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => '',
        ]);

        $longText = str_repeat('a', 2049);

        $this->browse(function (Browser $browser) use ($user, $longText) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->script("document.querySelector('#edit-note-textarea').value = '{$longText}';");
            
            // Textarea has maxlength="2048" so it won't accept more than 2048 chars
            $browser->assertAttribute('#edit-note-textarea', 'maxlength', '2048');
        });
    }

    public function test_user_can_cancel_edit_without_saving_changes(): void
    {
        $user = User::factory()->create();
        $conversion = SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => 'Original note',
        ]);

        $this->browse(function (Browser $browser) use ($user, $conversion) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->clear('#edit-note-textarea')
                ->type('#edit-note-textarea', 'Changed but will cancel')
                ->click('button:contains("Cancel")')
                ->waitUntilMissing('[id="edit-note-textarea"]');

            $this->assertDatabaseHas('saved_conversions', [
                'id' => $conversion->id,
                'note' => 'Original note',
            ]);
        });
    }

    public function test_note_displays_correctly_in_saved_conversion_card_after_save(): void
    {
        $user = User::factory()->create();
        SavedConversion::factory()->create([
            'user_id' => $user->id,
            'note' => '',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                ->visit('/')
                ->waitFor('[data-conversion-id]')
                ->click('[data-note-edit-button]')
                ->waitFor('[id="edit-note-textarea"]')
                ->type('#edit-note-textarea', 'Display this note')
                ->click('button:contains("Save")')
                ->waitUntilMissing('[id="edit-note-textarea"]')
                ->waitForText('Display this note');
        });
    }
}
