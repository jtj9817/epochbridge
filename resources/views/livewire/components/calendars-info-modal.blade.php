<?php

use function Livewire\Volt\state;

state(['showModal' => false]);

$openModal = function () {
    $this->showModal = true;
};

$closeModal = function () {
    $this->showModal = false;
};

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
        x-data="{ show: @entangle('showModal') }"
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
                class="bg-white dark:bg-zinc-900 rounded-lg shadow-xl w-full max-w-5xl max-h-[90vh] pointer-events-auto overflow-hidden flex flex-col"
            >
                <!-- Modal Header -->
                <div class="bg-theme-100 dark:bg-zinc-800 px-6 py-4 border-b border-theme-300 dark:border-zinc-600">
                    <div class="flex items-center justify-between">
                        <h3 class="text-2xl font-bold text-theme-900 dark:text-zinc-50">
                            Supported Calendar Systems
                        </h3>
                        <button
                            @click="$wire.closeModal()"
                            class="text-theme-900 hover:text-theme-500 dark:text-zinc-300 dark:hover:text-zinc-100 transition-colors"
                            aria-label="Close modal"
                        >
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-2 text-sm text-theme-500 dark:text-zinc-300">
                        EpochBridge supports 19 calendar systems spanning modern, historical, ancient, and technical formats.
                    </p>
                </div>

                <!-- Modal Body with Calendar Grid -->
                <div class="bg-theme-100 dark:bg-zinc-900 px-6 py-6 overflow-y-auto flex-1">
                    <div class="grid gap-6 md:grid-cols-2">
                        <!-- Gregorian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">📅</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Gregorian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Modern Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">The internationally accepted civil calendar system, introduced by Pope Gregory XIII in 1582.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> A solar calendar with 365 days in common years and 366 days in leap years. It is the most widely used calendar system worldwide for civil purposes.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> International business, civil administration, global communication, scientific documentation.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 12 months, leap year every 4 years (except centurial years not divisible by 400).</div>
                            </div>
                        </div>

                        <!-- Jalali Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🌙</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Jalali Calendar (Persian)</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Modern Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">The official calendar of Iran and Afghanistan, also known as the Solar Hijri calendar.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> One of the most astronomically accurate solar calendars, based on precise astronomical observations of the vernal equinox.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Official use in Iran and Afghanistan, Persian cultural celebrations, Nowruz (Persian New Year).</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 12 months, first 6 months have 31 days, next 5 have 30 days, last month has 29 or 30 days in leap years.</div>
                            </div>
                        </div>

                        <!-- Hebrew Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">✡️</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Hebrew Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Modern Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">A lunisolar calendar used for Jewish religious observances and Israel's official calendar.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Combines lunar months with solar year adjustments through periodic leap months to keep holidays aligned with seasons.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Jewish religious holidays, bar/bat mitzvahs, Hebrew date tracking, Israel civil calendar.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 12 or 13 months (leap years), 353-355 or 383-385 days depending on year type.</div>
                            </div>
                        </div>

                        <!-- Islamic Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">☪️</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Islamic Calendar (Hijri)</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Modern Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">A purely lunar calendar used for Islamic religious purposes, starting from the Hijra (622 CE).</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Based entirely on lunar cycles with no solar adjustment, causing Islamic months to shift through seasons over time.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Islamic religious observances, Ramadan, Eid celebrations, Hajj pilgrimage timing.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 12 lunar months totaling 354 or 355 days, approximately 11 days shorter than solar year.</div>
                            </div>
                        </div>

                        <!-- Chinese Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🐉</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Chinese Lunisolar Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">East Asian Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Traditional calendar system using astronomical calculations for lunations and solar terms.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Features complex astronomical calculations including zodiac animals, heavenly stems, earthly branches, and 24 solar terms. Uses Beijing timezone (UTC+8) for all calculations.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Chinese New Year, traditional festivals, zodiac year determination, agricultural planning, feng shui.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Lunisolar with leap months, 12 or 13 months per year. Uses Meeus algorithms with ΔT corrections for accuracy.</div>
                            </div>
                        </div>

                        <!-- Japanese Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🌸</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Japanese Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">East Asian Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">A Gregorian-based calendar system with traditional era names (nengō) based on Imperial reigns.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Combines the Gregorian calendar structure with Japanese era names like Reiwa, Heisei, Shōwa, marking different Imperial periods.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Official Japanese government documents, traditional ceremonies, cultural events, birth certificates.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Same structure as Gregorian but years counted from start of current era (e.g., Reiwa 7 instead of 2025).</div>
                            </div>
                        </div>

                        <!-- Julian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🏛️</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Julian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Historical Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Predecessor to the Gregorian calendar, introduced by Julius Caesar in 45 BCE.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> A solar calendar with a simpler leap year rule than the Gregorian, causing gradual drift against astronomical seasons.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Historical date research, Orthodox Christian Church dates, genealogical studies, classical scholarship.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 365 days with leap year every 4 years without exception, causing 3-day drift every 400 years.</div>
                            </div>
                        </div>

                        <!-- French Republican Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🇫🇷</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">French Republican Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Historical Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Revolutionary calendar used in France from 1793 to 1805, based on decimal system and reason.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Featured 12 months of 30 days plus 5-6 complementary days. Implementation includes Paris mean solar offset (UTC+00:09:21) and supports proleptic extension beyond historical 1792-1805 range.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> French Revolution historical research, understanding revolutionary documents, historical education.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 12 months of 30 days + 5 or 6 complementary days (sansculottides), 10-day weeks (décades).</div>
                            </div>
                        </div>

                        <!-- Coptic Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">☦️</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Coptic Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Historical Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">The liturgical calendar of the Coptic Orthodox Church, derived from ancient Egyptian calendar.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Closely related to the Ethiopian calendar, with same structure but different epoch (Era of Martyrs starting 284 CE).</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Coptic Orthodox Church liturgy, Egyptian Christian festivals, historical Egyptian date conversion.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 13 months: 12 months of 30 days each + 1 short month of 5 or 6 days.</div>
                            </div>
                        </div>

                        <!-- Ethiopian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🇪🇹</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Ethiopian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Historical Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">The principal calendar in Ethiopia, approximately 7-8 years behind the Gregorian calendar.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Still actively used as the official calendar of Ethiopia, with 13 months including a unique New Year celebration in September.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Ethiopian civil and religious life, Orthodox Tewahedo Church, Ethiopian New Year (Enkutatash).</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 13 months: 12 months of 30 days + Pagumē (5 or 6 days). Year starts on Gregorian August 29 or 30.</div>
                            </div>
                        </div>

                        <!-- Ancient Egyptian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🏺</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Ancient Egyptian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Ancient Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">The civil calendar of ancient Egypt, one of the earliest solar calendars in human history.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> A purely solar calendar with 365 days per year, no leap year correction, causing gradual drift through seasons.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Egyptology research, understanding ancient Egyptian texts, archaeological dating, historical studies.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> 12 months of 30 days + 5 epagomenal days (total 365 days), no leap years.</div>
                            </div>
                        </div>

                        <!-- Babylonian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🏺</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Babylonian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Ancient Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Ancient Mesopotamian lunisolar calendar used in Babylon and throughout the ancient Near East.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> An influential calendar system that contributed to the development of the Hebrew and other Near Eastern calendars.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Ancient Mesopotamian history, biblical chronology studies, archaeological research, cuneiform text interpretation.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Lunisolar with intercalary months added periodically to align lunar and solar cycles.</div>
                            </div>
                        </div>

                        <!-- Assyrian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">📜</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Assyrian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Ancient Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Calendar system used by the ancient Assyrian Empire in northern Mesopotamia.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Related to the Babylonian calendar but with distinct month names and cultural traditions from Assyrian civilization.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Assyrian historical research, ancient Near Eastern studies, Assyrian cultural heritage preservation.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Lunisolar calendar with Assyrian-specific month names and intercalation practices.</div>
                            </div>
                        </div>

                        <!-- Ancient Macedonian Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🏛️</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Ancient Macedonian Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Ancient Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Calendar system used in ancient Macedon and spread throughout the Hellenistic world by Alexander the Great.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> A lunisolar calendar with distinct Macedonian month names, widely adopted in territories conquered by Alexander.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Hellenistic period research, ancient Greek history, Alexander the Great's campaigns, classical studies.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Lunisolar with intercalary months, Macedonian month names distinct from Athenian calendar.</div>
                            </div>
                        </div>

                        <!-- Mayan Calendar -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🗿</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Mayan Calendar</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Ancient Calendars</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Complex Mesoamerican calendar system featuring multiple interlocking cycles including Long Count, Tzolk'in, and Haab'.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> One of the most sophisticated pre-Columbian calendar systems, featuring astronomical precision and multiple simultaneous cycles for different purposes.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Mesoamerican archaeology, Maya cultural studies, understanding ancient Maya inscriptions, historical chronology.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Long Count (base-20 positional notation), Tzolk'in (260-day ritual calendar), Haab' (365-day civil calendar).</div>
                            </div>
                        </div>

                        <!-- ISO 8601 -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🔢</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">ISO 8601</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Technical Formats</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">International standard for date and time representation, widely used in computing and data exchange.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Provides unambiguous date-time notation (YYYY-MM-DDTHH:MM:SS) for international communication and data storage.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> APIs and web services, database storage, international data exchange, software development, log files.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Format: YYYY-MM-DDTHH:MM:SS±TZ. Supports time zones, durations, and intervals.</div>
                            </div>
                        </div>

                        <!-- Unix Timestamp -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">⏰</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Unix Timestamp</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Technical Formats</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Number of seconds elapsed since January 1, 1970 00:00:00 UTC (Unix epoch).</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> The fundamental time representation in Unix-like operating systems and many programming languages.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> System programming, database timestamps, file systems, cron jobs, server logs, API responses.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Signed integer (32-bit or 64-bit) counting seconds since 1970-01-01 00:00:00 UTC.</div>
                            </div>
                        </div>

                        <!-- Julian Day Number -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🔭</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Julian Day Number</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Technical Formats</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Continuous count of days since January 1, 4713 BCE (proleptic Julian calendar), used in astronomy.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> The universal backbone for all calendar conversions in EpochBridge, providing astronomical accuracy. Note: Different from the Julian calendar (historical calendar system).</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Astronomical calculations, calendar conversions, satellite orbit computations, scientific research.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> Integer day count starting from JD 0 = January 1, 4713 BCE at 12:00 UTC.</div>
                            </div>
                        </div>

                        <!-- Modified Julian Day -->
                        <div class="bg-white dark:bg-zinc-800 rounded-lg p-5 border border-theme-300 dark:border-zinc-600 shadow-sm">
                            <div class="flex items-start gap-3 mb-3">
                                <div class="text-4xl">🛰️</div>
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-zinc-900 dark:text-zinc-50">Modified Julian Day</h4>
                                    <span class="inline-block mt-1 px-2 py-0.5 text-xs font-medium rounded-full bg-theme-100 dark:bg-zinc-700 text-theme-900 dark:text-zinc-200">Technical Formats</span>
                                </div>
                            </div>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300 mb-2">Simplified Julian Day Number starting from midnight on November 17, 1858.</p>
                            <div class="space-y-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Details:</strong> Introduced to reduce the number of digits needed for modern dates while maintaining Julian Day compatibility.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Use Cases:</strong> Satellite tracking, space missions, astronomical observations, radio astronomy, navigation systems.</div>
                                <div><strong class="text-zinc-800 dark:text-zinc-200">Technical:</strong> MJD = JD - 2400000.5. Starts at midnight instead of noon, uses smaller numbers.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-white dark:bg-zinc-900 px-6 py-4 flex justify-between items-center border-t border-theme-300 dark:border-zinc-600">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        19 Supported Calendar Systems
                    </p>
                    <button
                        @click="$wire.closeModal()"
                        type="button"
                        class="px-6 py-2 bg-theme-500 hover:bg-theme-300 text-white rounded-lg transition-colors font-medium"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
