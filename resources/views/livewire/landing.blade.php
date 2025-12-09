<?php

use App\Exceptions\CalendarConversionException;
use App\Services\DateTimeConverterService;
use App\Models\SavedConversion;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;

use function Livewire\Volt\layout;
use function Livewire\Volt\mount;
use function Livewire\Volt\on;
use function Livewire\Volt\state;

layout('components.layouts.public');

state('dateInput', '');
state('inputType', 'gregorian');
state('conversionResults', []);
state('conversionError', '');
state('inputTypes', [
    'gregorian' => 'Gregorian (YYYY-MM-DD)',
    'jalali' => 'Jalali (YYYY/MM/DD)',
    'unix' => 'Unix Timestamp',
    'iso8601' => 'ISO 8601',
    'jd' => 'Julian Day',
    'mjd' => 'Modified Julian Day',
    'hebrew' => 'Hebrew (YYYY/MM/DD)',
    'islamic' => 'Islamic (YYYY/MM/DD)',
    'french_republican' => 'French Republican (YYYY/MM/DD)',
    'chinese' => 'Chinese (YYYY/MM/DD)',
    'japanese' => 'Japanese (Era Year/MM/DD, e.g., "Reiwa 5/05/01")',
    'coptic' => 'Coptic (YYYY/MM/DD)',
    'egyptian' => 'Egyptian (YYYY/MM/DD)',
    'ethiopian' => 'Ethiopian (YYYY/MM/DD)',
    'babylonian' => 'Babylonian (YYYY/MM/DD)',
    'assyrian' => 'Assyrian (YYYY/MM/DD)',
    'macedonian' => 'Ancient Macedonian (YYYY/MM/DD)',
    'mayan' => 'Mayan (from Gregorian YYYY/MM/DD)',
]);
state([
    'searchTerm' => '',
    'selectedTypes' => [],
]);

mount(function () {
    // Initialize with today's date in YYYY-MM-DD format
    $this->dateInput = now()->format('Y-m-d');
});

$resolveConverter = static fn (?DateTimeConverterService $service = null) => $service ?? app(DateTimeConverterService::class);

$isDuplicateEntry = static function (QueryException $exception): bool {
    $errorInfo = $exception->errorInfo ?? [];

    if (isset($errorInfo[1]) && (string) $errorInfo[1] === '1062') {
        return true;
    }

    if (isset($errorInfo[0]) && (string) $errorInfo[0] === '23000') {
        return str_contains($exception->getMessage(), 'saved_conversions_user_original_unique');
    }

    return false;
};

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
        // Do NOT re-dispatch - causes infinite loop with reactive props
    },
    // Legacy event (no note) - keep for backwards compatibility
    'save-conversion' => function () {
        $this->saveConversion();
    },
    
    // New event (with note) - for modal integration
    'save-conversion-with-note' => function (string $note = '') {
        $this->saveConversion($note);
    },
]);

$saveConversion = function (string $note = '') use ($isDuplicateEntry) {
    if (! Auth::check() || empty($this->conversionResults)) {
        return;
    }

    $dispatchToast = function (string $message, string $type = 'info') {
        $messageJson = json_encode($message);
        $typeJson = json_encode($type);

        $this->js("
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    message: {$messageJson},
                    type: {$typeJson}
                }
            }))
        ");
    };

    $alreadySaved = SavedConversion::where('user_id', Auth::id())
        ->where('original_input', $this->dateInput)
        ->exists();

    if ($alreadySaved) {
        $dispatchToast('This conversion is already saved!', 'info');
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
        $dispatchToast('Conversion saved successfully!', 'success');
    } catch (QueryException $e) {
        if ($isDuplicateEntry($e)) {
            $dispatchToast('This conversion is already saved!', 'info');
            return;
        }

        $dispatchToast('Failed to save conversion. Please try again.', 'error');

        \Log::error('Failed to save conversion', [
            'user_id' => Auth::id(),
            'date_input' => $this->dateInput,
            'error' => $e->getMessage()
        ]);
    } catch (\Exception $e) {
        $dispatchToast('Failed to save conversion. Please try again.', 'error');

        \Log::error('Failed to save conversion', [
            'user_id' => Auth::id(),
            'date_input' => $this->dateInput,
            'error' => $e->getMessage()
        ]);
    }
};

$convertDate = function (?DateTimeConverterService $converter = null) use ($resolveConverter) {
    $converter = $resolveConverter($converter);
    $this->reset('conversionResults', 'conversionError');

    try {
        $this->conversionResults = $converter->getFullConversionResults($this->dateInput, $this->inputType);
        // Reactive props will automatically update child components - no dispatch needed
    } catch (CalendarConversionException|\InvalidArgumentException $e) {
        $this->conversionError = $e->getMessage();
    } catch (\Illuminate\Validation\ValidationException $e) {
        $this->conversionError = $e->validator->errors()->first('dateInput');
    }
};

?>

<div id="landing-page-container" class="min-h-screen bg-theme-50 dark:bg-theme-900"
     x-data="{
         showResults: @entangle('conversionResults'),
         showSaveLimitNotification: false,
         dateInput: @entangle('dateInput'),
         inputType: @entangle('inputType'),
         
         init() {
             this.migrateGuestData();
             
             // Watch for date input changes to trigger button state re-evaluation
             this.$watch('dateInput', () => {
                 // dateInput changed, button will automatically re-evaluate isConversionSaved()
             });
         },
         
migrateGuestData() {
              try {
                  const existingData = localStorage.getItem('savedConversions');
                  if (!existingData) return;
                  
                  const parsed = JSON.parse(existingData);
                  
                  // Check if migration is needed
                  if (parsed.length > 0 && (!parsed[0].conversion_data || !parsed[0].hasOwnProperty('note'))) {
                      const migratedData = parsed.map(item => {
                          if (item.conversion_data) {
                              return item; // Already migrated
                          }
                          
                          // Migrate old format to new structure
                          const conversionData = {
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
                          
                          return {
                              ...item,
                              conversion_data: conversionData,
                              note: item.note || '',
                              original_input: item.original_input || this.dateInput,
                              created_at: item.created_at || new Date().toISOString()
                          };
                      });
                      
                      localStorage.setItem('savedConversions', JSON.stringify(migratedData));
                  }
              } catch (e) {
                  console.warn('Failed to migrate guest data:', e);
                  // Only clear if it's a serialization error
                  if (e.message && (e.message.includes('JSON') || e.message.includes('toJSON'))) {
                      localStorage.removeItem('savedConversions');
                  }
              }
          },
         
getAllConversionResults() {
              if (!this.showResults || typeof this.showResults !== 'object') {
                  return {};
              }
              
              // Extract the actual value from the proxy and create a clean copy
              try {
                  return JSON.parse(JSON.stringify(this.showResults));
              } catch (e) {
                  console.warn('Failed to serialize conversion results:', e);
                  return {};
              }
          },
         
isConversionSaved() {
              if (!this.dateInput) return false;
              
              try {
                  const savedConversions = JSON.parse(localStorage.getItem('savedConversions') || '[]');
                  return savedConversions.some(item => item.original_input === String(this.dateInput));
              } catch (e) {
                  console.warn('Failed to check saved conversions:', e);
                  return false;
              }
          },
         
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
                  
                  // Get clean conversion results (no proxies)
                  const allResults = this.getAllConversionResults();
                  
                  const newConversion = {
                      id: Date.now(),
                      original_input: String(this.dateInput),
                      conversion_data: allResults,
                      note: String(note || ''),
                      created_at: new Date().toISOString()
                  };
                  
                  savedConversions.unshift(newConversion);
                  localStorage.setItem('savedConversions', JSON.stringify(savedConversions.slice(0, 3)));
                  
                  window.dispatchEvent(new CustomEvent('new-guest-conversion', { 
                      detail: { conversion: newConversion }
                  }));
                  
                  // Success toast
                  this.$dispatch('notify', {
                      message: 'Conversion saved! (Guest mode: 3 max)',
                      type: 'success'
                  });
                  
                  this.$nextTick(() => {
                      // Force reactivity update
                  });
              } catch (error) {
                  console.error('Failed to save guest conversion:', error);
                  
                  // Error toast
                  this.$dispatch('notify', {
                      message: 'Failed to save conversion. Please try again.',
                      type: 'error'
                  });
                  
                  // If the error is related to serialization, clear corrupted data
                  if (error.message && (error.message.includes('toJSON') || error.message.includes('circular'))) {
                      console.warn('Clearing potentially corrupted localStorage data');
                      localStorage.removeItem('savedConversions');
                  }
              }
          }
      }"
      @save-guest-conversion.window="handleGuestSave()"
      @save-guest-conversion-with-note.window="handleGuestSave($event.detail.note)">

    <!-- Header -->
    <header id="landing-header" class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 border-b border-theme-300 dark:border-zinc-700">
        @if (Route::has('login'))
            <nav id="auth-navigation" class="flex items-center justify-between gap-4">
                <!-- Left: Calendars Info Button -->
                <livewire:components.calendars-info-modal wire:key="calendars-modal" />

                <!-- Right: Auth Links -->
                <div class="flex items-center gap-2">
                    @auth
                        <a id="link-dashboard" href="{{ url('/dashboard') }}"
                           class="inline-block px-4 py-2 bg-theme-100 dark:bg-theme-500 border border-theme-300 dark:border-theme-300 text-xs font-medium text-theme-500 dark:text-theme-50 uppercase tracking-wider hover:bg-theme-50 dark:hover:bg-theme-300 transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a id="link-login" href="{{ route('login') }}"
                           class="inline-block px-4 py-2 text-theme-500 dark:text-theme-50 hover:text-theme-900 dark:hover:text-white text-xs font-medium uppercase tracking-wider transition-colors border border-transparent hover:border-theme-300 dark:hover:border-zinc-600">
                            Log in
                        </a>
                        @if (Route::has('register'))
                            <a id="link-register" href="{{ route('register') }}"
                               class="inline-block px-4 py-2 bg-theme-500 hover:bg-theme-300 text-white text-xs font-medium uppercase tracking-wider transition-colors border border-theme-500 hover:border-theme-300">
                                Register
                            </a>
                        @endif
                    @endauth
                </div>
            </nav>
        @endif
    </header>

    <!-- Main Content -->
    <main id="landing-main-content" class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8">
        <div class="w-full max-w-4xl mx-auto text-center">

            <!-- Hero Section -->
            <div id="hero-section" class="mb-12 pt-8">
                <h1 id="main-heading" class="text-5xl sm:text-6xl lg:text-7xl font-bold mb-4 tracking-tight">
                    <span class="text-theme-900 dark:text-theme-50">EPOCH</span><span class="text-theme-300">BRIDGE</span>
                </h1>
                <p id="subtitle" class="text-base sm:text-lg text-theme-500 dark:text-theme-100 mb-6 max-w-2xl mx-auto leading-normal">
                    Universal calendar conversion system.
                    <span class="text-theme-300 dark:text-theme-100 font-semibold">19 calendar systems.</span>
                    Astronomical precision.
                </p>
                <div class="text-xs text-theme-300 dark:text-theme-300 uppercase tracking-widest">
                    Gregorian · Jalali · Hebrew · Islamic · Chinese · Julian Day · Unix · ISO 8601
                </div>
            </div>

            <div class="max-w-2xl mx-auto">
                <!-- Date Input Section -->
                <livewire:components.calendar-input-section :dateInput="$dateInput" :inputType="$inputType" :inputTypes="$inputTypes" wire:key="input-section" />

                <!-- Quick Action Buttons -->
                <livewire:components.calendar-quick-actions :inputType="$inputType" wire:key="quick-actions" />

                <!-- Convert Button -->
                <div class="flex justify-center">
                    <button
                        id="btn-convert-date"
                        wire:click="convertDate"
                        wire:loading.attr="disabled"
                        class="btn-primary-technical w-full sm:w-auto relative flex items-center justify-center disabled:opacity-75 disabled:cursor-not-allowed">

                        <span wire:loading.remove wire:target="convertDate">
                            CONVERT DATE
                        </span>

                        <span wire:loading wire:target="convertDate" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            PROCESSING...
                        </span>
                    </button>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div x-show="Object.keys(showResults).length > 0" x-transition>
                <livewire:components.calendar-search-filter wire:key="search-filter" />
            </div>

            <!-- Conversion Results Section -->
            @if (!empty($conversionResults))
                <span class="sr-only">Copy All</span>
            @endif
            <livewire:components.calendar-results-grid
                :conversionResults="$conversionResults"
                :conversionError="$conversionError"
                :searchTerm="$searchTerm"
                :selectedTypes="$selectedTypes"
                :dateInput="$dateInput"
                wire:key="results-grid"
            />
            

            <!-- Save Limit Notification -->
            <div x-show="showSaveLimitNotification" x-transition x-cloak class="mt-6 max-w-4xl mx-auto p-3 bg-yellow-100 dark:bg-yellow-900/30 border border-yellow-500 text-yellow-800 dark:text-yellow-200">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-4 w-4 text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.21 3.03-1.742 3.03H4.42c-1.532 0-2.492-1.696-1.742-3.03l5.58-9.92zM10 13a1 1 0 110-2 1 1 0 010 2zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3 flex-grow">
                        <p class="text-xs uppercase tracking-wider">Guest limit reached (3 max). <a href="{{ route('register') }}" class="font-bold underline hover:text-yellow-700 dark:hover:text-yellow-100">Register</a> for unlimited saves.</p>
                    </div>
                    <div class="ml-auto pl-3">
                        <button @click="showSaveLimitNotification = false" type="button" class="inline-flex p-1 text-yellow-500 hover:bg-yellow-200 dark:hover:bg-yellow-900/50 focus:outline-none">
                            <span class="sr-only">Dismiss</span>
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Saved Conversions Section -->
            <livewire:components.saved-conversions-section wire:key="saved-conversions" />
        </div>
    </main>

    <!-- Footer -->
    <footer id="landing-footer" class="w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 mt-16 border-t border-theme-300 dark:border-zinc-700">
        <div class="text-center text-theme-500 dark:text-theme-100 text-xs uppercase tracking-widest">
            <p>&copy; {{ date('Y') }} EPOCHBRIDGE · TALL STACK · ASTRONOMICAL PRECISION</p>
        </div>
    </footer>
</div>
