# Saved Conversion Notes Feature - Implementation Plan

## Overview

Add the ability for users (both guest and authenticated) to create and manage notes for saved conversion dates. This feature allows users to annotate their saved conversions with contextual information, making them more useful for reference purposes.

---

## PHASE 1: Database Schema Update (Authenticated Users)

### GOAL
Add persistent storage for notes in the MySQL database for authenticated users.

### CONTEXT
The `saved_conversions` table currently stores conversion data but lacks a field for user-generated notes. We need to add a nullable text column to store optional notes.

### TASK
Create a new migration to add a `note` column to the `saved_conversions` table.

### REQUIREMENTS
1. Column must be nullable (notes are optional)
2. Use `TEXT` datatype to support longer notes (up to 65,535 characters)
3. Add the column after `conversion_data` for logical field ordering
4. Include rollback functionality in the `down()` method

### GUIDELINES

**File: `database/migrations/YYYY_MM_DD_HHMMSS_add_note_column_to_saved_conversions_table.php`**

Create a new migration file with the following structure:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saved_conversions', function (Blueprint $table) {
            $table->text('note')->nullable()->after('conversion_data');
        });
    }

    public function down(): void
    {
        Schema::table('saved_conversions', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
```

**Execution:**
```bash
php artisan make:migration add_note_column_to_saved_conversions_table
# Edit the generated file to match the structure above
php artisan migrate
php artisan migrate --env=testing
```

---

## PHASE 2: Model Update

### GOAL
Enable mass assignment and retrieval of notes through the SavedConversion Eloquent model.

### CONTEXT
The SavedConversion model uses the `$fillable` array to control mass assignment. The `note` field must be added to this array to allow notes to be saved via `create()` and `update()` methods.

### TASK
Update the SavedConversion model to include the `note` field in the `$fillable` array.

### REQUIREMENTS
1. Add `'note'` to the `$fillable` array
2. Maintain alphabetical or logical ordering of fields
3. No casting required (string is the default type)

### GUIDELINES

**File: `app/Models/SavedConversion.php`**

Locate the `$fillable` array (currently at line 19-27) and add `'note'`:

```php
protected $fillable = [
    'user_id',
    'original_input',
    'conversion_data',
    'note',  // Add this line
    'gregorian_date',
    'jalali_date',
    'unix_timestamp',
    'iso8601_string',
];
```

**Alternative placement (for logical grouping):**

```php
protected $fillable = [
    'user_id',
    'original_input',
    'note',  // Place after original_input for logical grouping
    'conversion_data',
    'gregorian_date',
    'jalali_date',
    'unix_timestamp',
    'iso8601_string',
];
```

---

## PHASE 3: Guest User Storage (localStorage) Structure Update

### GOAL
Extend the localStorage data structure to support notes for guest users.

### CONTEXT
Guest users store saved conversions in browser localStorage using the key `savedConversions`. The structure must be updated to include a `note` field that mirrors the authenticated user schema.

### TASK
Update the Alpine.js functions in the landing page component to handle note persistence in localStorage.

### REQUIREMENTS
1. Add `note` field to the guest conversion object structure
2. Update the `handleGuestSave()` function to accept and store notes
3. Update the migration function `migrateGuestData()` to preserve existing data
4. Ensure backwards compatibility with existing localStorage data

### GUIDELINES

**File: `resources/views/livewire/landing.blade.php`**

**Location: Alpine `x-data` initialization (around line 147-304)**

#### 3.1: Update the `handleGuestSave()` function

Modify the `handleGuestSave()` function to include a `note` parameter and store it:

```javascript
handleGuestSave(note = '') {
    try {
        const savedConversions = JSON.parse(localStorage.getItem('savedConversions') || '[]');
        
        // Check if already saved
        if (this.isConversionSaved()) {
            this.$dispatch('notify', {
                message: 'This conversion is already saved!',
                type: 'info'
            });
            return;
        }
        
        // Check save limit
        if (savedConversions.length >= 3) {
            this.$dispatch('notify', {
                message: 'Guest limit reached (3 max). Register for unlimited saves!',
                type: 'info'
            });
            this.showSaveLimitNotification = true;
            return;
        }
        
        const allResults = this.getAllConversionResults();
        
        const newConversion = {
            id: Date.now(),
            original_input: String(this.dateInput),
            conversion_data: allResults,
            note: String(note || ''),  // Add note field
            created_at: new Date().toISOString()
        };
        
        savedConversions.unshift(newConversion);
        localStorage.setItem('savedConversions', JSON.stringify(savedConversions.slice(0, 3)));
        
        window.dispatchEvent(new CustomEvent('new-guest-conversion', { 
            detail: { conversion: newConversion }
        }));
        
        this.$dispatch('notify', {
            message: 'Conversion saved! (Guest mode: 3 max)',
            type: 'success'
        });
        
        this.$nextTick(() => {
            // Force reactivity update
        });
    } catch (error) {
        console.error('Failed to save guest conversion:', error);
        
        this.$dispatch('notify', {
            message: 'Failed to save conversion. Please try again.',
            type: 'error'
        });
        
        if (error.message && (error.message.includes('toJSON') || error.message.includes('circular'))) {
            console.warn('Clearing potentially corrupted localStorage data');
            localStorage.removeItem('savedConversions');
        }
    }
}
```

#### 3.2: Update the `migrateGuestData()` function

Add note field initialization during data migration:

```javascript
migrateGuestData() {
    try {
        const existingData = localStorage.getItem('savedConversions');
        if (!existingData) return;
        
        const parsed = JSON.parse(existingData);
        
        // Check if migration is needed
        if (parsed.length > 0 && (!parsed[0].conversion_data || !parsed[0].hasOwnProperty('note'))) {
            const migratedData = parsed.map(item => {
                // Migrate conversion_data if needed
                let conversionData = item.conversion_data;
                
                if (!conversionData) {
                    conversionData = {
                        'gregorian': {
                            'full_date': item.gregorian?.full_date || item.gregorian_date,
                            'name': 'Gregorian',
                            'tags': ['modern', 'solar']
                        },
                        'jalali': {
                            'full_date': item.jalali?.full_date || item.jalali_date,
                            'name': 'Jalali',
                            'tags': ['modern', 'solar']
                        },
                        'unix': item.unix || item.unix_timestamp,
                        'iso8601': item.iso8601 || item.iso8601_string
                    };
                }
                
                return {
                    ...item,
                    conversion_data: conversionData,
                    note: item.note || '',  // Add default empty note if missing
                    original_input: item.original_input || this.dateInput,
                    created_at: item.created_at || new Date().toISOString()
                };
            });
            
            localStorage.setItem('savedConversions', JSON.stringify(migratedData));
        }
    } catch (e) {
        console.warn('Failed to migrate guest data:', e);
        if (e.message && (e.message.includes('JSON') || e.message.includes('toJSON'))) {
            localStorage.removeItem('savedConversions');
        }
    }
}
```

---

## PHASE 4: Save Note Functionality - UI Component

### GOAL
Create a user interface component that allows users to add notes when saving a conversion.

### CONTEXT
Currently, the save operation is triggered by clicking a "Save" button in the results grid component. We need to intercept this action and present a modal/dialog where users can optionally add a note before saving.

### TASK
Create a save modal component with a note input field that works for both guest and authenticated users.

### REQUIREMENTS
1. Modal should appear when user clicks "Save Conversion" button
2. Include a textarea for note input (max 500-1000 characters recommended)
3. Include "Save" and "Cancel" buttons
4. Handle both guest and authenticated save flows
5. Use Alpine.js for modal state management
6. Use Livewire for authenticated user save operation
7. Dispatch appropriate events based on user authentication status

### GUIDELINES

**File: `resources/views/livewire/components/save-conversion-modal.blade.php`**

Create a new Livewire Volt component:

```php
<?php

use Illuminate\Support\Facades\Auth;
use function Livewire\Volt\state;

state(['showModal' => false, 'note' => '']);

$openModal = function () {
    $this->showModal = true;
    $this->note = '';
};

$closeModal = function () {
    $this->showModal = false;
    $this->note = '';
};

$saveWithNote = function () {
    // Trim and validate note length
    $this->note = trim($this->note);
    
    if (strlen($this->note) > 1000) {
        $this->addError('note', 'Note must not exceed 1000 characters.');
        return;
    }
    
    if (Auth::check()) {
        // Dispatch to parent component for authenticated save
        $this->dispatch('save-conversion-with-note', note: $this->note);
    } else {
        // Dispatch to Alpine for guest save
        $this->js("
            window.dispatchEvent(new CustomEvent('save-guest-conversion-with-note', {
                detail: { note: " . json_encode($this->note) . " }
            }))
        ");
    }
    
    $this->closeModal();
};

?>

<div>
    <!-- Trigger Button (to be placed in results grid) -->
    <button
        wire:click="openModal"
        class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg shadow-md transition-colors"
    >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
        </svg>
        Save Conversion
    </button>

    <!-- Modal -->
    <div
        x-data="{ show: @entangle('showModal') }"
        x-show="show"
        x-cloak
        x-transition.opacity
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="show = false"
    >
        <!-- Backdrop -->
        <div
            class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"
            @click="$wire.closeModal()"
        ></div>

        <!-- Modal Content -->
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div
                x-show="show"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-theme-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full"
            >
                <!-- Modal Header -->
                <div class="bg-theme-100 dark:bg-theme-700 px-6 py-4 border-b border-theme-200 dark:border-theme-600">
                    <h3 class="text-lg font-semibold text-theme-900 dark:text-theme-50">
                        Save Conversion
                    </h3>
                </div>

                <!-- Modal Body -->
                <div class="bg-white dark:bg-theme-800 px-6 py-4">
                    <div class="mb-4">
                        <label for="conversion-note" class="block text-sm font-medium text-theme-700 dark:text-theme-300 mb-2">
                            Add a note (optional)
                        </label>
                        <textarea
                            id="conversion-note"
                            wire:model="note"
                            rows="4"
                            maxlength="1000"
                            placeholder="e.g., Birthday, Anniversary, Project Deadline..."
                            class="w-full px-3 py-2 border border-theme-300 dark:border-theme-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-theme-900 text-theme-900 dark:text-theme-50 placeholder-theme-400 dark:placeholder-theme-500"
                        ></textarea>
                        <p class="mt-1 text-xs text-theme-500 dark:text-theme-400">
                            <span x-text="$wire.note.length"></span>/1000 characters
                        </p>
                        @error('note')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-theme-50 dark:bg-theme-900 px-6 py-4 flex justify-end gap-3">
                    <button
                        wire:click="closeModal"
                        type="button"
                        class="px-4 py-2 border border-theme-300 dark:border-theme-600 rounded-lg text-theme-700 dark:text-theme-300 hover:bg-theme-100 dark:hover:bg-theme-800 transition-colors"
                    >
                        Cancel
                    </button>
                    <button
                        wire:click="saveWithNote"
                        type="button"
                        class="px-4 py-2 bg-theme-500 hover:bg-theme-600 text-white rounded-lg transition-colors"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
```

---

## PHASE 5: Update Landing Page - Authenticated User Save

### GOAL
Integrate note saving functionality for authenticated users in the main landing page component.

### CONTEXT
The landing page component currently handles the `save-conversion` event without notes. We need to update it to listen for `save-conversion-with-note` event and include the note in the database save operation.

### TASK
Modify the landing page Volt component to handle note parameter during authenticated save operations.

### REQUIREMENTS
1. Update the `save-conversion` event listener to accept a `note` parameter
2. Include the note when creating the SavedConversion record
3. Maintain backwards compatibility (default empty note if not provided)
4. Keep existing toast notification logic

### GUIDELINES

**File: `resources/views/livewire/landing.blade.php`**

**Location: Event listeners section (around lines 81-128)**

Replace the existing `'save-conversion'` event listener:

```php
on([
    'date-selected' => function (string $date, ?DateTimeConverterService $converter = null) use ($resolveConverter) {
        $this->dateInput = $date;
        $this->convertDate($resolveConverter($converter));
    },
    'input-updated' => function (string $dateInput, string $inputType) {
        $this->dateInput = $dateInput;
        $this->inputType = $inputType;
    },
    'filters-updated' => function (string $searchTerm, array $selectedTypes) {
        $normalizedTypes = array_values(array_unique(array_filter(array_map(
            static function ($type) {
                if (! is_string($type)) {
                    return null;
                }

                $normalized = strtolower(trim($type));

                return $normalized === '' ? null : $normalized;
            },
            $selectedTypes
        ))));

        if ($this->searchTerm === $searchTerm && $this->selectedTypes === $normalizedTypes) {
            return;
        }

        $this->searchTerm = $searchTerm;
        $this->selectedTypes = $normalizedTypes;
    },
    // Update this event listener to accept note parameter
    'save-conversion-with-note' => function (string $note = '') {
        if (! Auth::check() || empty($this->conversionResults)) {
            return;
        }

        try {
            $converter = app(DateTimeConverterService::class);
            $allResults = $converter->getFullConversionResults($this->dateInput, $this->inputType);

            Auth::user()->savedConversions()->create([
                'original_input' => $this->dateInput,
                'conversion_data' => $allResults,
                'note' => trim($note),  // Add note field
                'gregorian_date' => $this->conversionResults['gregorian']['full_date'],
                'jalali_date' => $this->conversionResults['jalali']['full_date'],
                'unix_timestamp' => $this->conversionResults['unix'],
                'iso8601_string' => $this->conversionResults['iso8601'],
            ]);

            $this->dispatch('conversion-saved');
            
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Conversion saved successfully!',
                        type: 'success'
                    }
                }))
            ");
        } catch (\Exception $e) {
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Failed to save conversion. Please try again.',
                        type: 'error'
                    }
                }))
            ");
            
            \Log::error('Failed to save conversion', [
                'user_id' => Auth::id(),
                'date_input' => $this->dateInput,
                'error' => $e->getMessage()
            ]);
        }
    },
]);
```

**Alternative: Keep both events for backwards compatibility**

```php
on([
    // ... other events ...
    
    // Legacy event (no note)
    'save-conversion' => function () {
        $this->saveConversion();
    },
    
    // New event (with note)
    'save-conversion-with-note' => function (string $note = '') {
        $this->saveConversion($note);
    },
]);

// Create a shared save method
$saveConversion = function (string $note = '') {
    if (! Auth::check() || empty($this->conversionResults)) {
        return;
    }

    try {
        $converter = app(DateTimeConverterService::class);
        $allResults = $converter->getFullConversionResults($this->dateInput, $this->inputType);

        Auth::user()->savedConversions()->create([
            'original_input' => $this->dateInput,
            'conversion_data' => $allResults,
            'note' => trim($note),
            'gregorian_date' => $this->conversionResults['gregorian']['full_date'],
            'jalali_date' => $this->conversionResults['jalali']['full_date'],
            'unix_timestamp' => $this->conversionResults['unix'],
            'iso8601_string' => $this->conversionResults['iso8601'],
        ]);

        $this->dispatch('conversion-saved');
        
        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'Conversion saved successfully!',
                    type: 'success'
                }
            }))
        ");
    } catch (\Exception $e) {
        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'Failed to save conversion. Please try again.',
                    type: 'error'
                }
            }))
        ");
        
        \Log::error('Failed to save conversion', [
            'user_id' => Auth::id(),
            'date_input' => $this->dateInput,
            'error' => $e->getMessage()
        ]);
    }
};
```

---

## PHASE 6: Update Landing Page - Guest User Save Event Handler

### GOAL
Handle the save-with-note event for guest users via Alpine.js in the landing page.

### CONTEXT
Guest users trigger saves through Alpine.js event handlers. We need to add a new event listener for `save-guest-conversion-with-note` that passes the note to the `handleGuestSave()` function.

### TASK
Add event listener for note-enabled guest saves in the landing page Alpine component.

### REQUIREMENTS
1. Listen for `save-guest-conversion-with-note` custom event
2. Extract note from event detail
3. Pass note to `handleGuestSave()` function
4. Maintain backwards compatibility with existing `save-guest-conversion` event

### GUIDELINES

**File: `resources/views/livewire/landing.blade.php`**

**Location: Main container div with Alpine x-data (around line 147, in the attributes section around line 304)**

Update the event listener:

```html
<div id="landing-page-container" class="min-h-screen bg-theme-50 dark:bg-theme-900"
     x-data="{
         showResults: @entangle('conversionResults'),
         showSaveLimitNotification: false,
         dateInput: @entangle('dateInput'),
         inputType: @entangle('inputType'),
         
         init() {
             this.migrateGuestData();
             
             this.$watch('dateInput', () => {
                 // dateInput changed, button will automatically re-evaluate isConversionSaved()
             });
         },
         
         // ... existing methods (migrateGuestData, getAllConversionResults, isConversionSaved) ...
         
         // Update handleGuestSave to accept note parameter
         handleGuestSave(note = '') {
             // ... (updated function from Phase 3) ...
         }
      }"
      @save-guest-conversion.window="handleGuestSave()"
      @save-guest-conversion-with-note.window="handleGuestSave($event.detail.note)">
```

---

## PHASE 7: Display Notes in Saved Conversions

### GOAL
Display and allow editing of notes for each saved conversion item.

### CONTEXT
The `saved-conversion-item.blade.php` component displays saved conversion data in a collapsible card. We need to add a section to display the note (if present) and provide inline editing capabilities.

### TASK
Extend the saved conversion item component to display and edit notes.

### REQUIREMENTS
1. Display note in a dedicated section within the collapsed content
2. Show "No note" placeholder if note is empty
3. Provide inline editing capability
4. Save note changes for both guest and authenticated users
5. Use Alpine.js for UI state management
6. Use Livewire methods for authenticated user updates
7. Use localStorage updates for guest users

### GUIDELINES

**File: `resources/views/components/saved-conversion-item.blade.php`**

#### 7.1: Extract note data in the PHP section

**Location: Top of file, in the `@php` block (around line 10-44)**

```php
@php
    $conversionData = $isGuest
        ? $item['conversion_data'] ?? []
        : ($item->normalized_conversion_data ?? $item->conversion_data ?? []);

    $originalInput = $isGuest ? ($item['original_input'] ?? '') : ($item->original_input ?? '');
    $createdAt = $isGuest ? ($item['created_at'] ?? '') : ($item->created_at ?? null);
    
    // Add note extraction
    $note = $isGuest ? ($item['note'] ?? '') : ($item->note ?? '');

    // ... rest of existing code ...
@endphp
```

#### 7.2: Add note display and editing to Alpine x-data

**Location: Main component div, x-data initialization (around line 51-196)**

Add note-related state and methods:

```javascript
x-data="{
    collapsed: true,
    editingNote: false,
    noteText: '{{ addslashes($note) }}',  // Initialize from server
    @if($isGuest)
    itemData: {},
    @endif

    init() {
        const attr = this.$el.getAttribute('data-collapsed');
        const datasetValue = this.$el.dataset.initialCollapsed;

        if (attr !== null) {
            this.collapsed = attr !== 'false';
        } else if (datasetValue !== undefined) {
            this.collapsed = datasetValue !== 'false';
        }

        this.syncCollapsedAttr();
    },

    toggleCollapsed() {
        // ... existing code ...
    },

    syncCollapsedAttr() {
        // ... existing code ...
    },
    
    // New note-related methods
    startEditingNote() {
        this.editingNote = true;
        this.$nextTick(() => {
            this.$refs.noteInput && this.$refs.noteInput.focus();
        });
    },
    
    cancelEditingNote() {
        this.noteText = '{{ addslashes($note) }}';  // Reset to original
        this.editingNote = false;
    },
    
    saveNote() {
        const trimmedNote = this.noteText.trim();
        
        @if($isGuest)
        // Guest user: update localStorage
        try {
            const savedConversions = JSON.parse(localStorage.getItem('savedConversions') || '[]');
            const index = savedConversions.findIndex(item => item.id === this.itemData.id);
            
            if (index !== -1) {
                savedConversions[index].note = trimmedNote;
                localStorage.setItem('savedConversions', JSON.stringify(savedConversions));
                
                this.$dispatch('notify', {
                    message: 'Note updated successfully!',
                    type: 'success'
                });
            }
        } catch (error) {
            console.error('Failed to update note:', error);
            this.$dispatch('notify', {
                message: 'Failed to update note. Please try again.',
                type: 'error'
            });
        }
        @else
        // Authenticated user: call Livewire method
        $wire.updateNote({{ $item->id }}, trimmedNote);
        @endif
        
        this.editingNote = false;
    },

    // ... existing methods (copyValue, copyAll, etc.) ...
}"
```

#### 7.3: Add note display section to the template

**Location: Inside the collapsible content div, after the conversion table (around line 534)**

```blade
<div
    id="{{ $contentId }}"
    x-show="!collapsed"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0 -translate-y-2"
    x-transition:enter-end="opacity-100 translate-y-0"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100 translate-y-0"
    x-transition:leave-end="opacity-0 -translate-y-2"
    x-cloak
    class="border-t border-theme-200 dark:border-theme-700"
>
    <!-- Existing conversion table code -->
    @if($isGuest)
        <!-- ... existing guest table code ... -->
    @else
        <!-- ... existing authenticated table code ... -->
    @endif
    
    <!-- Add Note Section -->
    <div class="bg-theme-50 dark:bg-theme-900 border-t border-theme-200 dark:border-theme-700 px-4 py-3">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-theme-500 dark:text-theme-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                    <span class="text-sm font-medium text-theme-700 dark:text-theme-300">Note</span>
                </div>
                
                <!-- Display Mode -->
                <div x-show="!editingNote">
                    <p class="text-sm text-theme-600 dark:text-theme-400 whitespace-pre-wrap" x-text="noteText || 'No note added'"></p>
                </div>
                
                <!-- Edit Mode -->
                <div x-show="editingNote" x-cloak>
                    <textarea
                        x-ref="noteInput"
                        x-model="noteText"
                        rows="3"
                        maxlength="1000"
                        class="w-full px-3 py-2 text-sm border border-theme-300 dark:border-theme-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-theme-800 text-theme-900 dark:text-theme-50"
                        @keydown.escape="cancelEditingNote()"
                        @keydown.meta.enter="saveNote()"
                        @keydown.ctrl.enter="saveNote()"
                    ></textarea>
                    <p class="mt-1 text-xs text-theme-500 dark:text-theme-400">
                        <span x-text="noteText.length"></span>/1000 characters
                    </p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center gap-2">
                <!-- Edit/Save/Cancel buttons -->
                <template x-if="!editingNote">
                    <button
                        @click="startEditingNote()"
                        class="text-theme-600 hover:text-theme-800 dark:text-theme-400 dark:hover:text-theme-200 p-1.5 rounded hover:bg-theme-100 dark:hover:bg-theme-800 transition-colors"
                        title="Edit note"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </button>
                </template>
                
                <template x-if="editingNote">
                    <div class="flex gap-1">
                        <button
                            @click="saveNote()"
                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 p-1.5 rounded hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors"
                            title="Save note"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <button
                            @click="cancelEditingNote()"
                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200 p-1.5 rounded hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors"
                            title="Cancel editing"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
```

---

## PHASE 8: Livewire Method for Updating Notes (Authenticated Users)

### GOAL
Create a Livewire method in the saved-conversions-section component to handle note updates for authenticated users.

### CONTEXT
When authenticated users edit notes, we need a server-side method to validate and persist the changes to the database.

### TASK
Add an `updateNote()` method to the saved-conversions-section Volt component.

### REQUIREMENTS
1. Accept conversion ID and note text as parameters
2. Validate that the conversion belongs to the authenticated user
3. Trim and validate note length (max 1000 characters)
4. Update the database record
5. Refresh the computed property to reflect changes
6. Show appropriate toast notifications

### GUIDELINES

**File: `resources/views/livewire/components/saved-conversions-section.blade.php`**

**Location: Add after the `deleteConversion` function (around line 243)**

```php
$updateNote = function (int $id, string $note) {
    if (!Auth::check()) {
        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'You must be logged in to update notes.',
                    type: 'error'
                }
            }))
        ");
        return;
    }
    
    try {
        $note = trim($note);
        
        // Validate note length
        if (strlen($note) > 1000) {
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Note must not exceed 1000 characters.',
                        type: 'error'
                    }
                }))
            ");
            return;
        }
        
        $conversion = SavedConversion::where('user_id', Auth::id())->find($id);
        
        if ($conversion) {
            $conversion->update(['note' => $note]);
            
            // Refresh computed property
            unset($this->savedConversions);
            
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Note updated successfully!',
                        type: 'success'
                    }
                }))
            ");
        } else {
            $this->js("
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: {
                        message: 'Conversion not found.',
                        type: 'error'
                    }
                }))
            ");
        }
    } catch (\Exception $e) {
        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: 'Failed to update note. Please try again.',
                    type: 'error'
                }
            }))
        ");
        
        \Log::error('Failed to update conversion note', [
            'user_id' => Auth::id(),
            'conversion_id' => $id,
            'error' => $e->getMessage()
        ]);
    }
};
```

---

## PHASE 9: Integrate Save Modal into Results Grid

### GOAL
Integrate the save modal component into the calendar results grid to enable note entry during save operations.

### CONTEXT
The calendar results grid has two save buttons (lines 179-193 in `calendar-results-grid.blade.php`):
- **Authenticated users**: `wire:click="$dispatch('save-conversion')"` (line 180)
- **Guest users**: `@click="$dispatch('save-guest-conversion')"` (line 186)

We need to replace these buttons to open the save modal instead of directly dispatching save events.

### TASK
Update the save button section in calendar-results-grid to trigger the modal opening.

### REQUIREMENTS
1. Replace direct save dispatches with modal opening triggers
2. Maintain existing "Copy All" button functionality
3. Keep the "Saved ✓" state indicator for guest users
4. Ensure proper modal integration for both authenticated and guest users

### GUIDELINES

**File: `resources/views/livewire/components/calendar-results-grid.blade.php`**

**Location: Lines 170-194 (action buttons section)**

Replace the save button code:

```blade
<div class="flex items-center gap-4">
    <button
        @click="copyToClipboard({{ Js::from($copyAllPayload) }}, 'all')"
        class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline"
        :class="{ 'text-green-500': copiedFeedback === 'all' }"
        x-text="copiedFeedback === 'all' ? 'Copied!' : 'Copy All'">
        Copy All
    </button>
    
    <!-- Integrate Save Modal Component -->
    <div class="inline-block">
        <livewire:components.save-conversion-modal wire:key="save-modal-results-grid" />
    </div>
</div>
```

**Note**: The modal component from Phase 4 will handle the authentication check internally and dispatch the appropriate events (`save-conversion-with-note` for authenticated or `save-guest-conversion-with-note` for guests).

---

## PHASE 10: Factory and Seeder Updates

### GOAL
Update the SavedConversion factory to generate realistic test data with notes.

### CONTEXT
The factory is used for testing and seeding. Adding note generation ensures comprehensive test coverage.

### TASK
Update the SavedConversionFactory to include note generation.

### REQUIREMENTS
1. Generate random notes for 70% of conversions (notes are optional)
2. Use realistic note examples
3. Vary note length
4. Keep notes concise and relevant

### GUIDELINES

**File: `database/factories/SavedConversionFactory.php`**

```php
<?php

namespace Database\Factories;

use App\Models\SavedConversion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SavedConversionFactory extends Factory
{
    protected $model = SavedConversion::class;

    public function definition(): array
    {
        $noteExamples = [
            'Birthday',
            'Anniversary',
            'Project deadline',
            'Important meeting',
            'Launch date',
            'Vacation start',
            'Contract expiration',
            'License renewal',
            'Historical event',
            'Release date',
            '',  // Some conversions won't have notes
        ];
        
        // 70% chance of having a note
        $note = $this->faker->boolean(70) 
            ? $this->faker->randomElement($noteExamples)
            : '';

        return [
            'user_id' => User::factory(),
            'original_input' => $this->faker->date(),
            'conversion_data' => [
                'gregorian' => [
                    'full_date' => $this->faker->date(),
                    'name' => 'Gregorian',
                    'tags' => ['modern', 'solar'],
                ],
                'jalali' => [
                    'full_date' => $this->faker->numerify('####/##/##'),
                    'name' => 'Jalali',
                    'tags' => ['modern', 'solar'],
                ],
                'unix' => $this->faker->unixTime(),
                'iso8601' => $this->faker->iso8601(),
            ],
            'note' => $note,
            'gregorian_date' => $this->faker->date(),
            'jalali_date' => $this->faker->numerify('####/##/##'),
            'unix_timestamp' => $this->faker->unixTime(),
            'iso8601_string' => $this->faker->iso8601(),
        ];
    }
}
```

---

## PHASE 11: Test Updates

### GOAL
Update existing tests and create new tests for note functionality.

### CONTEXT
Tests ensure that note functionality works correctly for both guest and authenticated users across create, read, and update operations.

### TASK
Update and create comprehensive tests for note functionality.

### REQUIREMENTS
1. Test note creation for authenticated users
2. Test note creation for guest users (via component tests)
3. Test note updates
4. Test note validation (max length)
5. Test empty/null notes (optional field)
6. Test note display in saved conversions

### GUIDELINES

#### 11.1: Update Saved Conversions Feature Test

**File: `tests/Feature/Livewire/SavedConversionsTest.php`**

Add test methods:

```php
test('authenticated user can save conversion with note', function () {
    $user = User::factory()->create();
    
    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\Landing::class);
    
    $component->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate');
    
    $component->dispatch('save-conversion-with-note', note: 'Test birthday');
    
    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'note' => 'Test birthday',
    ]);
});

test('authenticated user can save conversion without note', function () {
    $user = User::factory()->create();
    
    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\Landing::class);
    
    $component->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate');
    
    $component->dispatch('save-conversion-with-note', note: '');
    
    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'original_input' => '2024-01-15',
        'note' => '',
    ]);
});

test('note is trimmed before saving', function () {
    $user = User::factory()->create();
    
    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\Landing::class);
    
    $component->set('dateInput', '2024-01-15')
        ->set('inputType', 'gregorian')
        ->call('convertDate');
    
    $component->dispatch('save-conversion-with-note', note: '  Birthday  ');
    
    $this->assertDatabaseHas('saved_conversions', [
        'user_id' => $user->id,
        'note' => 'Birthday',
    ]);
});

test('authenticated user can update note', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Old note',
    ]);
    
    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\Components\SavedConversionsSection::class);
    
    $component->call('updateNote', $conversion->id, 'Updated note');
    
    $this->assertDatabaseHas('saved_conversions', [
        'id' => $conversion->id,
        'note' => 'Updated note',
    ]);
});

test('note cannot exceed 1000 characters', function () {
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
    ]);
    
    $longNote = str_repeat('a', 1001);
    
    $component = Livewire::actingAs($user)
        ->test(\App\Livewire\Components\SavedConversionsSection::class);
    
    $component->call('updateNote', $conversion->id, $longNote);
    
    // Note should not be updated
    $this->assertDatabaseMissing('saved_conversions', [
        'id' => $conversion->id,
        'note' => $longNote,
    ]);
});

test('user cannot update note of another users conversion', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user1->id,
        'note' => 'Original note',
    ]);
    
    $component = Livewire::actingAs($user2)
        ->test(\App\Livewire\Components\SavedConversionsSection::class);
    
    $component->call('updateNote', $conversion->id, 'Hacked note');
    
    $this->assertDatabaseHas('saved_conversions', [
        'id' => $conversion->id,
        'note' => 'Original note', // Should remain unchanged
    ]);
});
```

#### 11.2: Update Model Test

**File: `tests/Unit/Models/SavedConversionTest.php`**

Add test:

```php
test('note is fillable', function () {
    $conversion = SavedConversion::create([
        'user_id' => User::factory()->create()->id,
        'original_input' => '2024-01-15',
        'conversion_data' => ['test' => 'data'],
        'note' => 'Test note',
        'gregorian_date' => '2024-01-15',
        'jalali_date' => '1402/10/25',
        'unix_timestamp' => 1705276800,
        'iso8601_string' => '2024-01-15T00:00:00+00:00',
    ]);
    
    expect($conversion->note)->toBe('Test note');
});
```

#### 11.3: Browser Test for Note Editing

**File: `tests/Browser/SavedConversionsTest.php`**

Add Dusk test:

```php
public function test_user_can_edit_note_for_saved_conversion()
{
    $user = User::factory()->create();
    $conversion = SavedConversion::factory()->create([
        'user_id' => $user->id,
        'note' => 'Original note',
    ]);
    
    $this->browse(function (Browser $browser) use ($user, $conversion) {
        $browser->loginAs($user)
            ->visit('/')
            ->waitFor('[data-conversion-id="' . $conversion->id . '"]')
            ->click('[data-conversion-id="' . $conversion->id . '"] button[title="Edit note"]')
            ->waitFor('textarea[x-ref="noteInput"]')
            ->type('textarea[x-ref="noteInput"]', 'Updated note')
            ->click('button[title="Save note"]')
            ->waitForText('Note updated successfully!');
        
        $this->assertDatabaseHas('saved_conversions', [
            'id' => $conversion->id,
            'note' => 'Updated note',
        ]);
    });
}
```

---

## PHASE 12: Documentation Updates

### GOAL
Update project documentation to reflect the new note functionality.

### CONTEXT
Documentation helps maintainers and future developers understand the note feature implementation.

### TASK
Update relevant documentation files.

### REQUIREMENTS
1. Update architecture overview
2. Update guest conversions guide
3. Add changelog entry

### GUIDELINES

#### 12.1: Update CHANGELOG.md

**File: `docs/CHANGELOG.md`**

Add entry:

```markdown
## [Unreleased]

### Added
- Note functionality for saved conversions (both guest and authenticated users)
  - Users can add optional notes when saving date conversions
  - Notes can be edited inline in saved conversion cards
  - Guest users store notes in localStorage (max 3 conversions)
  - Authenticated users store notes in MySQL database
  - Character limit of 1000 characters per note
  - Database migration adds `note` column to `saved_conversions` table
```

#### 12.2: Update Architecture Overview

**File: `docs/architecture-overview.md`**

Add section describing note storage:

```markdown
### Saved Conversion Notes

Saved conversions support optional user notes (max 1000 characters):

**Authenticated Users:**
- Notes stored in `saved_conversions.note` column (TEXT type)
- Updated via Livewire `updateNote()` method
- Persisted to MySQL database

**Guest Users:**
- Notes stored in localStorage under `savedConversions` key
- Each conversion object includes a `note` field
- Updated via Alpine.js reactive state

**UI Features:**
- Inline editing with save/cancel buttons
- Real-time character count display
- Toast notifications for save success/errors
- Keyboard shortcuts (Cmd/Ctrl+Enter to save, Escape to cancel)
```

#### 12.3: Update Guest Conversions Guide

**File: `docs/guest-conversions-guide.md`**

Update the localStorage structure documentation:

```markdown
### Guest Conversion Structure

```json
{
  "id": 1705276800000,
  "original_input": "2024-01-15",
  "conversion_data": {
    "gregorian": {
      "full_date": "2024-01-15",
      "name": "Gregorian",
      "tags": ["modern", "solar"]
    },
    "jalali": {
      "full_date": "1402/10/25",
      "name": "Jalali",
      "tags": ["modern", "solar"]
    },
    "unix": 1705276800,
    "iso8601": "2024-01-15T00:00:00+00:00"
  },
  "note": "Birthday reminder",
  "created_at": "2024-01-15T00:00:00.000Z"
}
```
```

---

## Summary

This implementation plan provides a complete, phase-by-phase approach to adding note functionality to saved conversions. Each phase is self-contained and can be implemented and tested independently, following TALL stack best practices:

1. **Database**: Migration adds `note` column
2. **Model**: Update `$fillable` array
3. **Guest Storage**: Update localStorage structure and Alpine.js handlers
4. **UI Modal**: Create save modal with note input
5. **Auth Save**: Update Livewire save handler for authenticated users
6. **Guest Save**: Update Alpine.js save handler for guest users
7. **Display**: Add note display and inline editing to saved conversion items
8. **Update Method**: Create Livewire method for note updates
9. **Results Grid**: Integrate modal into conversion results
10. **Factory**: Update test data generation
11. **Tests**: Comprehensive test coverage
12. **Documentation**: Update project documentation

The implementation leverages:
- **Livewire 3** for server-side state management (authenticated users)
- **Alpine.js** for client-side reactivity (guest users and UI interactions)
- **localStorage API** for guest data persistence
- **MySQL** for authenticated user data persistence
- **Toast notifications** for user feedback
- **Inline editing** for seamless UX
