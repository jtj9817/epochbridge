<?php

namespace App\Services;

use App\Models\MayanCalendarData;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class MayanCalendarService extends CalendarConversionService
{
    // GMT Correlation Constant: Julian day for Mayan date 0.0.0.0.0
    private const GMT_CORRELATION = 584283;

    private static ?array $haabData = null;

    private static ?array $tzolkinData = null;

    public function __construct()
    {
        if (self::$haabData === null || self::$tzolkinData === null) {
            self::$haabData = Cache::rememberForever('mayan_haab_data', function () {
                return MayanCalendarData::where('calendar_type', 'haab')->orderBy('position')->pluck('name_maya')->all();
            });
            self::$tzolkinData = Cache::rememberForever('mayan_tzolkin_data', function () {
                return MayanCalendarData::where('calendar_type', 'tzolkin')->orderBy('position')->pluck('name_maya')->all();
            });
        }
    }

    public function fromGregorian(Carbon $date): array
    {
        $jd = gregoriantojd($date->month, $date->day, $date->year);
        $longCount = $this->calculateLongCount($jd);
        $haab = $this->calculateHaab($jd);
        $tzolkin = $this->calculateTzolkin($jd);

        return [
            'long_count' => "{$longCount['baktun']}.{$longCount['katun']}.{$longCount['tun']}.{$longCount['winal']}.{$longCount['kin']}",
            'haab' => "{$haab['day']} {$haab['month_name']}",
            'tzolkin' => "{$tzolkin['number']} {$tzolkin['day_name']}",
            'calendar_round' => "{$tzolkin['number']} {$tzolkin['day_name']} {$haab['day']} {$haab['month_name']}",
            'disclaimer' => 'Based on the GMT correlation constant (584283).',
        ];
    }

    public function toGregorian(array $dateData): Carbon
    {
        $this->handleCalculationError('Direct conversion from Mayan components is not supported via this form. Please use Gregorian input.');

        // @codeCoverageIgnoreStart
        return Carbon::now(); // Should not be reached
        // @codeCoverageIgnoreEnd
    }

    public function getKey(): string
    {
        return 'mayan';
    }

    public function getName(): string
    {
        return 'Mayan Calendar';
    }

    public function validateDate(int $year, int $month, int $day): bool
    {
        // This input is abstract for Mayan, so we just validate components
        return $year > 0 && checkdate($month, $day, $year);
    }

    public function getDaysInMonth(int $year, int $month): int
    {
        // Not directly applicable to Mayan cycles in this context
        return 31;
    }

    private function calculateLongCount(int $jd): array
    {
        $totalDays = $jd - self::GMT_CORRELATION;

        $baktun = floor($totalDays / 144000);
        $remainder = $totalDays % 144000;

        $katun = floor($remainder / 7200);
        $remainder %= 7200;

        $tun = floor($remainder / 360);
        $remainder %= 360;

        $winal = floor($remainder / 20);
        $kin = $remainder % 20;

        return compact('baktun', 'katun', 'tun', 'winal', 'kin');
    }

    private function calculateHaab(int $jd): array
    {
        $haabDays = ($jd - self::GMT_CORRELATION + 348) % 365;
        $monthIndex = floor($haabDays / 20);
        $day = ($haabDays % 20);

        return [
            'day' => $day,
            'month_name' => self::$haabData[$monthIndex] ?? 'Unknown',
        ];
    }

    private function calculateTzolkin(int $jd): array
    {
        $tzolkinDays = ($jd - self::GMT_CORRELATION + 4) % 260;
        $dayNumber = ($tzolkinDays % 13) + 1;
        $dayIndex = $tzolkinDays % 20;

        return [
            'number' => $dayNumber,
            'day_name' => self::$tzolkinData[$dayIndex] ?? 'Unknown',
        ];
    }

    public function getTags(): array
    {
        return ['ancient', 'system'];
    }
}
