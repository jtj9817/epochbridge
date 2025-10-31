<?php

use function Livewire\Volt\state;

state(['showModal' => false, 'searchTerm' => '', 'selectedCategory' => 'all']);

$openModal = function () {
    $this->showModal = true;
    $this->searchTerm = '';
    $this->selectedCategory = 'all';
};

$closeModal = function () {
    $this->showModal = false;
};

$calendars = [
    // Modern Calendars
    [
        'id' => 'gregorian',
        'name' => 'Gregorian Calendar',
        'icon' => '📅',
        'category' => 'modern',
        'description' => 'The internationally accepted civil calendar system, introduced by Pope Gregory XIII in 1582.',
        'details' => 'A solar calendar with 365 days in common years and 366 days in leap years. It is the most widely used calendar system worldwide for civil purposes.',
        'use_cases' => 'International business, civil administration, global communication, scientific documentation.',
        'technical' => '12 months, leap year every 4 years (except centurial years not divisible by 400).',
    ],
    [
        'id' => 'jalali',
        'name' => 'Jalali Calendar (Persian)',
        'icon' => '🌙',
        'category' => 'modern',
        'description' => 'The official calendar of Iran and Afghanistan, also known as the Solar Hijri calendar.',
        'details' => 'One of the most astronomically accurate solar calendars, based on precise astronomical observations of the vernal equinox.',
        'use_cases' => 'Official use in Iran and Afghanistan, Persian cultural celebrations, Nowruz (Persian New Year).',
        'technical' => '12 months, first 6 months have 31 days, next 5 have 30 days, last month has 29 or 30 days in leap years.',
    ],
    [
        'id' => 'hebrew',
        'name' => 'Hebrew Calendar',
        'icon' => '✡️',
        'category' => 'modern',
        'description' => 'A lunisolar calendar used for Jewish religious observances and Israel\'s official calendar.',
        'details' => 'Combines lunar months with solar year adjustments through periodic leap months to keep holidays aligned with seasons.',
        'use_cases' => 'Jewish religious holidays, bar/bat mitzvahs, Hebrew date tracking, Israel civil calendar.',
        'technical' => '12 or 13 months (leap years), 353-355 or 383-385 days depending on year type.',
    ],
    [
        'id' => 'islamic',
        'name' => 'Islamic Calendar (Hijri)',
        'icon' => '☪️',
        'category' => 'modern',
        'description' => 'A purely lunar calendar used for Islamic religious purposes, starting from the Hijra (622 CE).',
        'details' => 'Based entirely on lunar cycles with no solar adjustment, causing Islamic months to shift through seasons over time.',
        'use_cases' => 'Islamic religious observances, Ramadan, Eid celebrations, Hajj pilgrimage timing.',
        'technical' => '12 lunar months totaling 354 or 355 days, approximately 11 days shorter than solar year.',
    ],

    // East Asian Calendars
    [
        'id' => 'chinese',
        'name' => 'Chinese Lunisolar Calendar',
        'icon' => '🐉',
        'category' => 'east-asian',
        'description' => 'Traditional calendar system using astronomical calculations for lunations and solar terms.',
        'details' => 'Features complex astronomical calculations including zodiac animals, heavenly stems, earthly branches, and 24 solar terms. Uses Beijing timezone (UTC+8) for all calculations.',
        'use_cases' => 'Chinese New Year, traditional festivals, zodiac year determination, agricultural planning, feng shui.',
        'technical' => 'Lunisolar with leap months, 12 or 13 months per year. Uses Meeus algorithms with ΔT corrections for accuracy.',
    ],
    [
        'id' => 'japanese',
        'name' => 'Japanese Calendar',
        'icon' => '🌸',
        'category' => 'east-asian',
        'description' => 'A Gregorian-based calendar system with traditional era names (nengō) based on Imperial reigns.',
        'details' => 'Combines the Gregorian calendar structure with Japanese era names like Reiwa, Heisei, Shōwa, marking different Imperial periods.',
        'use_cases' => 'Official Japanese government documents, traditional ceremonies, cultural events, birth certificates.',
        'technical' => 'Same structure as Gregorian but years counted from start of current era (e.g., Reiwa 7 instead of 2025).',
    ],

    // Historical Calendars
    [
        'id' => 'julian',
        'name' => 'Julian Calendar',
        'icon' => '🏛️',
        'category' => 'historical',
        'description' => 'Predecessor to the Gregorian calendar, introduced by Julius Caesar in 45 BCE.',
        'details' => 'A solar calendar with a simpler leap year rule than the Gregorian, causing gradual drift against astronomical seasons.',
        'use_cases' => 'Historical date research, Orthodox Christian Church dates, genealogical studies, classical scholarship.',
        'technical' => '365 days with leap year every 4 years without exception, causing 3-day drift every 400 years.',
    ],
    [
        'id' => 'french_republican',
        'name' => 'French Republican Calendar',
        'icon' => '🇫🇷',
        'category' => 'historical',
        'description' => 'Revolutionary calendar used in France from 1793 to 1805, based on decimal system and reason.',
        'details' => 'Featured 12 months of 30 days plus 5-6 complementary days. Implementation includes Paris mean solar offset (UTC+00:09:21) and supports proleptic extension beyond historical 1792-1805 range.',
        'use_cases' => 'French Revolution historical research, understanding revolutionary documents, historical education.',
        'technical' => '12 months of 30 days + 5 or 6 complementary days (sansculottides), 10-day weeks (décades).',
    ],
    [
        'id' => 'coptic',
        'name' => 'Coptic Calendar',
        'icon' => '☦️',
        'category' => 'historical',
        'description' => 'The liturgical calendar of the Coptic Orthodox Church, derived from ancient Egyptian calendar.',
        'details' => 'Closely related to the Ethiopian calendar, with same structure but different epoch (Era of Martyrs starting 284 CE).',
        'use_cases' => 'Coptic Orthodox Church liturgy, Egyptian Christian festivals, historical Egyptian date conversion.',
        'technical' => '13 months: 12 months of 30 days each + 1 short month of 5 or 6 days.',
    ],
    [
        'id' => 'ethiopian',
        'name' => 'Ethiopian Calendar',
        'icon' => '🇪🇹',
        'category' => 'historical',
        'description' => 'The principal calendar in Ethiopia, approximately 7-8 years behind the Gregorian calendar.',
        'details' => 'Still actively used as the official calendar of Ethiopia, with 13 months including a unique New Year celebration in September.',
        'use_cases' => 'Ethiopian civil and religious life, Orthodox Tewahedo Church, Ethiopian New Year (Enkutatash).',
        'technical' => '13 months: 12 months of 30 days + Pagumē (5 or 6 days). Year starts on Gregorian August 29 or 30.',
    ],
    [
        'id' => 'egyptian',
        'name' => 'Ancient Egyptian Calendar',
        'icon' => '🏺',
        'category' => 'ancient',
        'description' => 'The civil calendar of ancient Egypt, one of the earliest solar calendars in human history.',
        'details' => 'A purely solar calendar with 365 days per year, no leap year correction, causing gradual drift through seasons.',
        'use_cases' => 'Egyptology research, understanding ancient Egyptian texts, archaeological dating, historical studies.',
        'technical' => '12 months of 30 days + 5 epagomenal days (total 365 days), no leap years.',
    ],

    // Ancient Calendars
    [
        'id' => 'babylonian',
        'name' => 'Babylonian Calendar',
        'icon' => '🏺',
        'category' => 'ancient',
        'description' => 'Ancient Mesopotamian lunisolar calendar used in Babylon and throughout the ancient Near East.',
        'details' => 'An influential calendar system that contributed to the development of the Hebrew and other Near Eastern calendars.',
        'use_cases' => 'Ancient Mesopotamian history, biblical chronology studies, archaeological research, cuneiform text interpretation.',
        'technical' => 'Lunisolar with intercalary months added periodically to align lunar and solar cycles.',
    ],
    [
        'id' => 'assyrian',
        'name' => 'Assyrian Calendar',
        'icon' => '📜',
        'category' => 'ancient',
        'description' => 'Calendar system used by the ancient Assyrian Empire in northern Mesopotamia.',
        'details' => 'Related to the Babylonian calendar but with distinct month names and cultural traditions from Assyrian civilization.',
        'use_cases' => 'Assyrian historical research, ancient Near Eastern studies, Assyrian cultural heritage preservation.',
        'technical' => 'Lunisolar calendar with Assyrian-specific month names and intercalation practices.',
    ],
    [
        'id' => 'macedonian',
        'name' => 'Ancient Macedonian Calendar',
        'icon' => '🏛️',
        'category' => 'ancient',
        'description' => 'Calendar system used in ancient Macedon and spread throughout the Hellenistic world by Alexander the Great.',
        'details' => 'A lunisolar calendar with distinct Macedonian month names, widely adopted in territories conquered by Alexander.',
        'use_cases' => 'Hellenistic period research, ancient Greek history, Alexander the Great\'s campaigns, classical studies.',
        'technical' => 'Lunisolar with intercalary months, Macedonian month names distinct from Athenian calendar.',
    ],
    [
        'id' => 'mayan',
        'name' => 'Mayan Calendar',
        'icon' => '🗿',
        'category' => 'ancient',
        'description' => 'Complex Mesoamerican calendar system featuring multiple interlocking cycles including Long Count, Tzolk\'in, and Haab\'.',
        'details' => 'One of the most sophisticated pre-Columbian calendar systems, featuring astronomical precision and multiple simultaneous cycles for different purposes.',
        'use_cases' => 'Mesoamerican archaeology, Maya cultural studies, understanding ancient Maya inscriptions, historical chronology.',
        'technical' => 'Long Count (base-20 positional notation), Tzolk\'in (260-day ritual calendar), Haab\' (365-day civil calendar).',
    ],

    // Technical Formats
    [
        'id' => 'iso8601',
        'name' => 'ISO 8601',
        'icon' => '🔢',
        'category' => 'technical',
        'description' => 'International standard for date and time representation, widely used in computing and data exchange.',
        'details' => 'Provides unambiguous date-time notation (YYYY-MM-DDTHH:MM:SS) for international communication and data storage.',
        'use_cases' => 'APIs and web services, database storage, international data exchange, software development, log files.',
        'technical' => 'Format: YYYY-MM-DDTHH:MM:SS±TZ. Supports time zones, durations, and intervals.',
    ],
    [
        'id' => 'unix',
        'name' => 'Unix Timestamp',
        'icon' => '⏰',
        'category' => 'technical',
        'description' => 'Number of seconds elapsed since January 1, 1970 00:00:00 UTC (Unix epoch).',
        'details' => 'The fundamental time representation in Unix-like operating systems and many programming languages.',
        'use_cases' => 'System programming, database timestamps, file systems, cron jobs, server logs, API responses.',
        'technical' => 'Signed integer (32-bit or 64-bit) counting seconds since 1970-01-01 00:00:00 UTC.',
    ],
    [
        'id' => 'julian_day',
        'name' => 'Julian Day Number',
        'icon' => '🔭',
        'category' => 'technical',
        'description' => 'Continuous count of days since January 1, 4713 BCE (proleptic Julian calendar), used in astronomy.',
        'details' => 'The universal backbone for all calendar conversions in EpochBridge, providing astronomical accuracy. Note: Different from the Julian calendar (historical calendar system).',
        'use_cases' => 'Astronomical calculations, calendar conversions, satellite orbit computations, scientific research.',
        'technical' => 'Integer day count starting from JD 0 = January 1, 4713 BCE at 12:00 UTC.',
    ],
    [
        'id' => 'modified_julian_day',
        'name' => 'Modified Julian Day',
        'icon' => '🛰️',
        'category' => 'technical',
        'description' => 'Simplified Julian Day Number starting from midnight on November 17, 1858.',
        'details' => 'Introduced to reduce the number of digits needed for modern dates while maintaining Julian Day compatibility.',
        'use_cases' => 'Satellite tracking, space missions, astronomical observations, radio astronomy, navigation systems.',
        'technical' => 'MJD = JD - 2400000.5. Starts at midnight instead of noon, uses smaller numbers.',
    ],
];

?>

<div>
    <!-- Trigger Button -->
    <button
        wire:click="openModal"
        class="inline-flex items-center gap-2 px-5 py-2 bg-theme-100 dark:bg-theme-700 border border-theme-300 dark:border-theme-600 rounded-lg text-sm font-medium text-theme-700 dark:text-theme-200 hover:bg-theme-200 dark:hover:bg-theme-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
        <span>Calendars</span>
    </button>
    
    <!-- Modal -->
    <div
        x-data="{ 
            show: @entangle('showModal'),
            searchTerm: @entangle('searchTerm'),
            selectedCategory: @entangle('selectedCategory'),
            calendars: <?php echo json_encode($calendars); ?>,
            
            get filteredCalendars() {
                return this.calendars.filter(calendar => {
                    const matchesSearch = this.searchTerm === '' || 
                        calendar.name.toLowerCase().includes(this.searchTerm.toLowerCase()) ||
                        calendar.description.toLowerCase().includes(this.searchTerm.toLowerCase());
                    
                    const matchesCategory = this.selectedCategory === 'all' || 
                        calendar.category === this.selectedCategory;
                    
                    return matchesSearch && matchesCategory;
                });
            },
            
            getCategoryName(category) {
                const names = {
                    'modern': 'Modern Calendars',
                    'east-asian': 'East Asian Calendars',
                    'historical': 'Historical Calendars',
                    'ancient': 'Ancient Calendars',
                    'technical': 'Technical Formats'
                };
                return names[category] || category;
            }
        }"
        x-cloak
        @keydown.escape="$wire.closeModal()"
    >
        <!-- Backdrop -->
        <div
            x-show="show"
            x-transition.opacity
            class="fixed inset-0 bg-black bg-opacity-50 z-50"
            @click="$wire.closeModal()"
        ></div>

        <!-- Modal Content Container -->
        <div 
            x-show="show"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none"
        >
            <div
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                @click.stop
                class="bg-white dark:bg-theme-800 rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] pointer-events-auto overflow-hidden flex flex-col"
            >
                <!-- Modal Header -->
                <div class="bg-theme-100 dark:bg-theme-700 px-6 py-4 border-b border-theme-200 dark:border-theme-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-theme-900 dark:text-theme-50">
                            Supported Calendar Systems
                        </h3>
                        <button
                            @click="$wire.closeModal()"
                            class="text-theme-500 hover:text-theme-700 dark:text-theme-300 dark:hover:text-theme-100 transition-colors"
                            aria-label="Close modal"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-theme-600 dark:text-theme-300">
                        EpochBridge supports 19 calendar systems spanning modern, historical, ancient, and technical formats.
                    </p>
                </div>

                <!-- Search and Filter Controls -->
                <div class="bg-theme-50 dark:bg-theme-900 px-6 py-4 border-b border-theme-200 dark:border-theme-600">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <!-- Search Input -->
                        <div class="flex-1">
                            <label for="calendar-search" class="sr-only">Search calendars</label>
                            <div class="relative">
                                <input
                                    id="calendar-search"
                                    type="text"
                                    x-model="searchTerm"
                                    placeholder="Search calendars..."
                                    class="w-full px-4 py-2 pl-10 border border-theme-300 dark:border-theme-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-theme-800 text-black dark:text-white placeholder-theme-400"
                                />
                                <svg class="absolute left-3 top-2.5 w-5 h-5 text-theme-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                        </div>

                        <!-- Category Filter -->
                        <div class="sm:w-64">
                            <label for="category-filter" class="sr-only">Filter by category</label>
                            <select
                                id="category-filter"
                                x-model="selectedCategory"
                                class="w-full px-4 py-2 border border-theme-300 dark:border-theme-600 rounded-lg focus:ring-2 focus:ring-theme-500 focus:border-transparent bg-white dark:bg-theme-800 text-theme-900 dark:text-theme-50"
                            >
                                <option value="all">All Categories</option>
                                <option value="modern">Modern Calendars</option>
                                <option value="east-asian">East Asian Calendars</option>
                                <option value="historical">Historical Calendars</option>
                                <option value="ancient">Ancient Calendars</option>
                                <option value="technical">Technical Formats</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Modal Body with Calendar Grid -->
                <div class="bg-white dark:bg-theme-800 px-6 py-6 overflow-y-auto flex-1">
                    <div class="grid gap-6 md:grid-cols-2">
                        <template x-for="calendar in filteredCalendars" :key="calendar.id">
                            <div class="bg-theme-50 dark:bg-theme-700 rounded-lg p-5 border border-theme-200 dark:border-theme-600 hover:shadow-lg transition-shadow">
                                <!-- Calendar Header -->
                                <div class="flex items-start gap-3 mb-3">
                                    <div class="text-4xl" x-text="calendar.icon"></div>
                                    <div class="flex-1">
                                        <h4 class="text-lg font-semibold text-theme-900 dark:text-theme-50" x-text="calendar.name"></h4>
                                        <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-200 dark:bg-theme-600 text-theme-700 dark:text-theme-200" x-text="getCategoryName(calendar.category)"></span>
                                    </div>
                                </div>

                                <!-- Calendar Description -->
                                <p class="text-sm text-theme-700 dark:text-theme-300 mb-2" x-text="calendar.description"></p>
                                
                                <!-- Calendar Details -->
                                <div class="space-y-2 text-xs text-theme-600 dark:text-theme-400">
                                    <div>
                                        <strong class="text-theme-800 dark:text-theme-200">Details:</strong>
                                        <span x-text="calendar.details"></span>
                                    </div>
                                    <div>
                                        <strong class="text-theme-800 dark:text-theme-200">Use Cases:</strong>
                                        <span x-text="calendar.use_cases"></span>
                                    </div>
                                    <div>
                                        <strong class="text-theme-800 dark:text-theme-200">Technical:</strong>
                                        <span x-text="calendar.technical"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- No Results Message -->
                        <div x-show="filteredCalendars.length === 0" class="col-span-2 text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-theme-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <p class="mt-2 text-sm text-theme-500 dark:text-theme-400">No calendars found matching your search.</p>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-theme-50 dark:bg-theme-900 px-6 py-4 flex justify-between items-center border-t border-theme-200 dark:border-theme-600">
                    <p class="text-sm text-theme-600 dark:text-theme-400">
                        <span x-text="filteredCalendars.length"></span> of <span x-text="calendars.length"></span> calendars
                    </p>
                    <button
                        @click="$wire.closeModal()"
                        type="button"
                        class="px-6 py-2 bg-theme-500 hover:bg-theme-600 text-white rounded-lg transition-colors font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
