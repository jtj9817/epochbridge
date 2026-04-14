<?php

declare(strict_types=1);

namespace App\Http\Controllers\FrontendApi;

use App\Http\Controllers\Controller;
use App\Services\AncientMacedonianService;
use App\Services\AssyrianCalendarService;
use App\Services\BabylonianCalendarService;
use App\Services\ChineseCalendarService;
use App\Services\CopticCalendarService;
use App\Services\EgyptianCalendarService;
use App\Services\EthiopianCalendarService;
use App\Services\FrenchRepublicanService;
use App\Services\HebrewCalendarService;
use App\Services\IslamicCalendarService;
use App\Services\JapaneseCalendarService;
use App\Services\JulianCalendarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Morilog\Jalali\Jalalian;

class QuickActionController extends Controller
{
    /**
     * Get formatted date for a quick action (today, yesterday, tomorrow).
     */
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:today,yesterday,tomorrow',
            'input_type' => 'required|string|in:gregorian,jalali,unix,iso8601,jd,mjd,hebrew,islamic,french_republican,chinese,japanese,coptic,egyptian,ethiopian,babylonian,assyrian,macedonian,julian,mayan',
        ]);

        $action = $validated['action'];
        $inputType = $validated['input_type'];

        $date = match ($action) {
            'today' => Carbon::now(),
            'yesterday' => Carbon::now()->subDay(),
            'tomorrow' => Carbon::now()->addDay(),
        };

        $formattedDate = $this->formatDateForInputType($date, $inputType);

        return response()->json([
            'date' => $formattedDate,
            'input_type' => $inputType,
        ]);
    }

    /**
     * Convert a Carbon date to the appropriate format for the given input type.
     */
    private function formatDateForInputType(Carbon $date, string $inputType): string
    {
        return match ($inputType) {
            'gregorian' => $date->format('Y-m-d'),
            'jalali' => Jalalian::fromCarbon($date)->format('Y/m/d'),
            'unix' => (string) $date->startOfDay()->timestamp,
            'iso8601' => $date->startOfDay()->toIso8601String(),
            'jd' => (string) gregoriantojd($date->month, $date->day, $date->year),
            'mjd' => (string) (gregoriantojd($date->month, $date->day, $date->year) - 2400000.5),
            'hebrew' => app(HebrewCalendarService::class)->fromGregorian($date)['full_date'],
            'islamic' => app(IslamicCalendarService::class)->fromGregorian($date)['full_date'],
            'french_republican' => app(FrenchRepublicanService::class)->fromGregorian($date)['full_date'],
            'chinese' => app(ChineseCalendarService::class)->fromGregorian($date)['full_date'],
            'japanese' => app(JapaneseCalendarService::class)->fromGregorian($date)['full_date'],
            'coptic' => app(CopticCalendarService::class)->fromGregorian($date)['full_date'],
            'egyptian' => app(EgyptianCalendarService::class)->fromGregorian($date)['full_date'],
            'ethiopian' => app(EthiopianCalendarService::class)->fromGregorian($date)['full_date'],
            'babylonian' => app(BabylonianCalendarService::class)->fromGregorian($date)['full_date'],
            'assyrian' => app(AssyrianCalendarService::class)->fromGregorian($date)['full_date'],
            'macedonian' => app(AncientMacedonianService::class)->fromGregorian($date)['full_date'],
            'julian' => app(JulianCalendarService::class)->fromGregorian($date)['full_date'],
            // Mayan uses Gregorian input format (as stated in the input type label)
            'mayan' => $date->format('Y/m/d'),
            default => $date->format('Y-m-d'),
        };
    }
}
