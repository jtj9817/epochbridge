# Implementation Plan: Clipboard and Saved Conversions

This document outlines the plan to implement clipboard functionality and a feature to save date-time conversions for both guest and authenticated users. The implementation will be divided into three phases.

---

## Phase 1: Clipboard Functionality

**GOAL:**
Enable users to easily copy individual conversion results or all results at once to their clipboard.

**CONTEXT:**
The landing page displays multiple conversion formats (Gregorian, Jalali, Unix, ISO 8601). Users need a quick way to use this data. This functionality will be handled entirely on the client-side using Alpine.js to provide instant feedback without server requests.

**TASK:**
- Add a "Copy" icon button to each conversion result card.
- Add a "Copy All" button to the main results section.
- Implement clipboard logic using Alpine.js and the `navigator.clipboard` API.
- Provide visual feedback to the user upon a successful copy action (e.g., changing text to "Copied!").

**REQUIREMENTS:**
- A "Copy" button must be present on each of the four result cards.
- A "Copy All" button must be located near the "Conversion Results" heading.
- Clicking a "Copy" button should copy the relevant value to the clipboard.
- Clicking "Copy All" should copy a formatted string of all results to the clipboard.
- The UI must show feedback for 3 seconds after copying.

**GUIDELINES:**
- Use Alpine.js for all client-side state management and event handling.
- Use SVG icons for the copy buttons.
- The "Copy All" string should be formatted for readability, with each conversion on a new line.

### Step-by-Step Implementation

#### 1. Modify `resources/views/livewire/landing.blade.php`

A. **Update the main Alpine.js `x-data` component:**
   - In the top-level `div#landing-page-container`, add new properties to the `x-data` object for managing copy state.

   ```html
   <div id="landing-page-container" ...
        x-data="{
           showResults: @entangle('conversionResults').live,
           inputValue: @entangle('dateInput').live,
           copiedFeedback: null, // Can be 'gregorian', 'jalali', 'unix', 'iso8601', 'all'
           copyToClipboard(text, type) {
               navigator.clipboard.writeText(text).then(() => {
                   this.copiedFeedback = type;
                   setTimeout(() => { this.copiedFeedback = null }, 3000);
               });
           }
        }">
   ```

B. **Add a "Copy All" button:**
   - Below the `<h2 id="conversion-results-heading">`, add a button that will format and copy all results.

   ```html
   <div class="flex items-center justify-between mb-6">
       <h2 id="conversion-results-heading" class="text-2xl font-bold text-theme-900 dark:text-theme-50">
           Conversion Results
       </h2>
       <button
           @click="copyToClipboard(
               `Gregorian: {{ $conversionResults['gregorian']['full_date'] ?? '' }}\n` +
               `Jalali: {{ $conversionresults['jalali']['full_date'] ?? '' }}\n` +
               `Unix: {{ $conversionResults['unix'] ?? '' }}\n` +
               `ISO 8601: {{ $conversionResults['iso8601'] ?? '' }}`,
               'all'
           )"
           class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline"
           :class="{ 'text-green-500': copiedFeedback === 'all' }"
           x-text="copiedFeedback === 'all' ? 'Copied!' : 'Copy All'">
           Copy All
       </button>
   </div>
   ```

C. **Add individual "Copy" buttons to result cards:**
   - In each result card (e.g., `<div id="result-card-gregorian">`), add a copy button inside the header. This requires adding a small Alpine scope to each card for cleaner state management.

   **Example for Gregorian Card:**
   - Modify the header `div` to include the button.

   ```html
   <!-- In result-card-gregorian -->
   <div class="p-4 border-b border-theme-300/50 dark:border-theme-300/50 flex items-center justify-between">
       <h3 class="font-semibold text-theme-500 dark:text-theme-100 text-center flex-grow">Gregorian</h3>
       <button @click="copyToClipboard('{{ $conversionResults['gregorian']['full_date'] ?? '' }}', 'gregorian')" title="Copy">
           <span x-show="copiedFeedback !== 'gregorian'">
               <!-- SVG for copy icon -->
           </span>
           <span x-show="copiedFeedback === 'gregorian'" class="text-green-500 text-xs">Copied!</span>
       </button>
   </div>
   ```
   - Repeat this pattern for the **Jalali**, **Unix**, and **ISO 8601** cards, adjusting the value passed to `copyToClipboard()` accordingly. For Jalali, copy `full_date`. For Unix, copy `unix`. For ISO 8601, copy `iso8601`.

---

## Phase 2: Saved Conversions for Guests

**GOAL:**
Allow non-authenticated users to save a limited number of conversion results using browser `localStorage`.

**CONTEXT:**
This feature enhances user experience for guests by providing persistence for their conversions. It also serves as an incentive to register for an account to unlock unlimited saves. The logic will be entirely client-side, managed by Alpine.js.

**TASK:**
- Add a "Save" button to the conversion results section.
- Use Alpine.js to manage a list of saved conversions in `localStorage`.
- Display the list of saved conversions on the landing page.
- Limit guests to a maximum of three saved conversions.
- Implement a dismissible notification that appears when the limit is reached, prompting the user to log in or register.

**REQUIREMENTS:**
- A "Save" button should be clearly visible when conversion results are displayed.
- Saved conversions must persist across page reloads for guests.
- The UI must display all saved conversions.
- Guests cannot save more than three items.
- A notification must appear when a guest tries to save a fourth item.

**GUIDELINES:**
- Store the saved conversions as a JSON string in `localStorage`.
- Initialize the Alpine state from `localStorage` on page load.
- Ensure the UI for saved items is clear and includes all relevant data points.

### Step-by-Step Implementation

#### 1. Modify `resources/views/livewire/landing.blade.php`

A. **Expand the main Alpine.js `x-data` object:**
   - Add state for saved conversions, limit notifications, and the associated logic.

   ```html
   <div id="landing-page-container" ...
        x-data="{
           // ... existing properties
           savedConversions: JSON.parse(localStorage.getItem('savedConversions')) || [],
           showSaveLimitNotification: false,
           saveConversion() {
               if (this.savedConversions.length >= 3) {
                   this.showSaveLimitNotification = true;
                   return;
               }
               // Use a unique ID based on timestamp to avoid duplicates in this session
               const newConversion = { ...this.showResults, id: Date.now() };
               this.savedConversions.unshift(newConversion);
               localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
           },
           deleteSaved(id) {
               this.savedConversions = this.savedConversions.filter(item => item.id !== id);
               localStorage.setItem('savedConversions', JSON.stringify(this.savedConversions));
           }
        }">
   ```

B. **Add a "Save Conversion" button:**
   - Place this button next to the "Copy All" button. It should only be shown for guests (`@guest`) and when there are results.

   ```html
   <!-- Inside the flex container with the h2 and "Copy All" button -->
   @guest
   <button
       x-show="Object.keys(showResults).length > 0"
       @click="saveConversion()"
       class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
       Save
   </button>
   @endguest
   ```

C. **Create the UI for the Save Limit Notification:**
   - Place this below the results section. It will be controlled by `x-show`.

   ```html
   <!-- Below conversion-results-section div -->
   <div x-show="showSaveLimitNotification" x-transition class="mt-4 max-w-4xl mx-auto p-4 bg-yellow-100 dark:bg-yellow-900/30 border-l-4 border-yellow-500 text-yellow-800 dark:text-yellow-200 rounded-r-lg">
       <div class="flex">
           <div class="py-1"><!-- SVG icon for info --></div>
           <div class="ml-3">
               <p class="text-sm">You've reached the guest limit of 3 saved conversions. <a href="{{ route('register') }}" class="font-bold underline">Create an account</a> for unlimited saves.</p>
           </div>
           <button @click="showSaveLimitNotification = false" class="ml-auto -mx-1.5 -my-1.5 p-1.5 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
               <!-- SVG icon for close (X) -->
           </button>
       </div>
   </div>
   ```

D. **Create the UI to display saved conversions for guests:**
   - Add a new section below the notification for displaying items from the `savedConversions` array.

   ```html
   @guest
   <div id="saved-conversions-guest" class="mt-16 max-w-4xl mx-auto" x-show="savedConversions.length > 0">
       <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6">
           Your Saved Conversions
       </h2>
       <div class="space-y-4">
           <template x-for="item in savedConversions" :key="item.id">
               <div class="p-4 bg-theme-100 dark:bg-theme-500 rounded-lg shadow-md flex items-center justify-between">
                   <div class="text-sm text-theme-900 dark:text-theme-50">
                       <!-- Display saved data, e.g., item.gregorian.full_date -->
                   </div>
                   <button @click="deleteSaved(item.id)" class="text-red-500 hover:text-red-700">
                       <!-- SVG for trash can icon -->
                   </button>
               </div>
           </template>
       </div>
   </div>
   @endguest
   ```

---

## Phase 3: Saved Conversions for Authenticated Users

**GOAL:**
Enable authenticated users to save an unlimited number of conversions to their database account.

**CONTEXT:**
This feature provides a core benefit for registered users. It requires backend infrastructure (model, migration, controller logic) and frontend integration to call Livewire actions.

**TASK:**
- Create a `SavedConversion` model and a corresponding migration.
- Establish a `hasMany` relationship from the `User` model to `SavedConversion`.
- Add server-side logic to the landing page's Volt component to handle saving and deleting conversions for the authenticated user.
- Load and display saved conversions for authenticated users on the landing page.
- Modify the "Save" button to trigger a Livewire action instead of an Alpine.js function when a user is authenticated.

**REQUIREMENTS:**
- A `saved_conversions` table must be created with appropriate columns and a foreign key to `users`.
- Authenticated users must be able to save and delete their own conversions.
- Users must not be able to see or delete conversions belonging to other users.
- Saved conversions for authenticated users must be loaded from the database.

**GUIDELINES:**
- Follow Laravel best practices for database schema and Eloquent models.
- Use Livewire actions for all database interactions.
- Ensure proper authorization is implicitly handled by scoping queries to the authenticated user.

### Step-by-Step Implementation

#### 1. Create Model and Migration

- Generate a model and migration: `php artisan make:model SavedConversion -m`
- **Modify the migration file:** `database/migrations/YYYY_MM_DD_HHMMSS_create_saved_conversions_table.php`

  ```php
  Schema::create('saved_conversions', function (Blueprint $table) {
      $table->id();
      $table->foreignId('user_id')->constrained()->onDelete('cascade');
      $table->string('original_input');
      $table->string('gregorian_date');
      $table->string('jalali_date');
      $table->bigInteger('unix_timestamp');
      $table->string('iso8601_string');
      $table->timestamps();
  });
  ```
- Run the migration: `php artisan migrate`

#### 2. Update User Model

- **File:** `app/Models/User.php`
- Add the relationship to the `SavedConversion` model.

  ```php
  use App\Models\SavedConversion;
  use Illuminate\Database\Eloquent\Relations\HasMany;

  // ... inside User class
  public function savedConversions(): HasMany
  {
      return $this->hasMany(SavedConversion::class)->orderBy('created_at', 'desc');
  }
  ```

#### 3. Update Landing Page Livewire (Volt) Component

- **File:** `resources/views/livewire/landing.blade.php`
- Add new state properties and actions to the PHP block at the top of the file.

  ```php
  <?php

  use App\Models\SavedConversion;
  use Illuminate\Support\Collection;
  use function Livewire\Volt\{state, mount, layout, rules, computed};

  // ... existing state and rules

  state(['savedConversions' => fn () => auth()->check() ? auth()->user()->savedConversions : collect()]);

  $saveConversion = function () {
      if (!auth()->check() || empty($this->conversionResults)) {
          return;
      }

      auth()->user()->savedConversions()->create([
          'original_input' => $this->dateInput,
          'gregorian_date' => $this->conversionResults['gregorian']['full_date'],
          'jalali_date' => $this->conversionResults['jalali']['full_date'],
          'unix_timestamp' => $this->conversionResults['unix'],
          'iso8601_string' => $this->conversionResults['iso8601'],
      ]);

      $this->savedConversions = auth()->user()->savedConversions()->get();
  };

  $deleteConversion = function (int $id) {
      $conversion = auth()->user()->savedConversions()->find($id);
      if ($conversion) {
          $conversion->delete();
          $this->savedConversions = $this->savedConversions->except($id);
      }
  };
  ```

#### 4. Update Landing Page UI for Authenticated Users

- **File:** `resources/views/livewire/landing.blade.php`

A. **Modify the "Save" button for authenticated users:**
   - Wrap the existing guest button and add a new one for authenticated users.

   ```html
    <!-- Inside the flex container with the h2 and "Copy All" button -->
    @auth
        <button
            x-show="Object.keys(showResults).length > 0"
            wire:click="saveConversion"
            class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
            Save
        </button>
    @else
        <button
            x-show="Object.keys(showResults).length > 0"
            @click="saveConversion()"
            class="text-sm font-medium text-theme-500 dark:text-theme-100 hover:underline">
            Save
        </button>
    @endauth
   ```

B. **Create the UI to display saved conversions for authenticated users:**
   - Add a new section for displaying items from the `$savedConversions` Livewire collection.

   ```html
   @auth
   <div id="saved-conversions-auth" class="mt-16 max-w-4xl mx-auto">
       @if ($savedConversions->isNotEmpty())
           <h2 class="text-2xl font-bold text-theme-900 dark:text-theme-50 mb-6">
               Your Saved Conversions
           </h2>
           <div class="space-y-4">
               @foreach($savedConversions as $item)
                   <div wire:key="{{ $item->id }}" class="p-4 bg-theme-100 dark:bg-theme-500 rounded-lg shadow-md flex items-center justify-between">
                       <div class="text-sm text-theme-900 dark:text-theme-50">
                           <!-- Display saved data, e.g., $item->gregorian_date -->
                       </div>
                       <button wire:click="deleteConversion({{ $item->id }})" wire:confirm="Are you sure you want to delete this saved conversion?" class="text-red-500 hover:text-red-700">
                           <!-- SVG for trash can icon -->
                       </button>
                   </div>
               @endforeach
           </div>
       @endif
   </div>
   @endauth
````
