<?php

namespace App\Services\Chinese;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * High-precision astronomical utilities for Chinese lunisolar calculations.
 * Implements Meeus algorithms for solar longitude, lunar conjunctions, and time conversions.
 */
class AstronomyCalculator
{
    private const JULIAN_DATE_UNIX_EPOCH = 2440587.5;
    public const SECONDS_PER_DAY = 86400;
    private const SUN_MEAN_LONGITUDE_COEFFS = [280.46646, 36000.76983, 0.0003032];
    private const SUN_MEAN_ANOMALY_COEFFS = [357.52911, 35999.05029, -0.0001537, 0.00000048];
    private const EARTH_ECCENTRICITY_COEFFS = [1.0, -0.002516, -0.0000074];

    /**
     * Convert a Carbon date/time to Julian Day (UTC-based).
     */
    public function julianDayFromCarbon(CarbonInterface $dateTime): float
    {
        $utc = CarbonImmutable::createFromInterface($dateTime)->setTimezone('UTC');
        $seconds = (float) $utc->format('U.u');

        return self::JULIAN_DATE_UNIX_EPOCH + ($seconds / self::SECONDS_PER_DAY);
    }

    /**
     * Convert a Julian Day (UTC) to a CarbonImmutable instance in requested timezone.
     */
    public function julianDayToCarbon(float $jd, string $timezone = 'UTC'): CarbonImmutable
    {
        $seconds = ($jd - self::JULIAN_DATE_UNIX_EPOCH) * self::SECONDS_PER_DAY;
        $wholeSeconds = (int) floor($seconds);
        $microseconds = (int) round(($seconds - $wholeSeconds) * 1_000_000);

        $date = CarbonImmutable::createFromTimestampUTC($wholeSeconds);

        if ($microseconds !== 0) {
            $date = $date->addMicroseconds($microseconds);
        }

        return $date->setTimezone($timezone);
    }

    /**
     * Approximate lunation number for a Julian Day.
     */
    public function approximateKFromJulianDay(float $jd): float
    {
        return ($jd - 2451550.09765) / 29.530588861;
    }

    /**
     * Calculate the Terrestrial Time Julian Ephemeris Day of the k-th new moon.
     *
     * @see Jean Meeus, Astronomical Algorithms (2nd ed.), Chapter 49.
     */
    public function newMoon(float $k): float
    {
        $T = $k / 1236.85;
        $T2 = $T * $T;
        $T3 = $T2 * $T;
        $T4 = $T3 * $T;

        $E = self::EARTH_ECCENTRICITY_COEFFS[0]
            + self::EARTH_ECCENTRICITY_COEFFS[1] * $T
            + self::EARTH_ECCENTRICITY_COEFFS[2] * $T2;

        $M = $this->degToRad($this->normalizeDegrees(
            2.5534 + 29.10535670 * $k - 0.0000014 * $T2 - 0.00000011 * $T3
        ));
        $MPrime = $this->degToRad($this->normalizeDegrees(
            201.5643 + 385.81693528 * $k + 0.0107582 * $T2 + 0.00001238 * $T3 - 0.000000058 * $T4
        ));
        $F = $this->degToRad($this->normalizeDegrees(
            160.7108 + 390.67050284 * $k - 0.0016118 * $T2 - 0.00000227 * $T3 + 0.000000011 * $T4
        ));
        $omega = $this->degToRad($this->normalizeDegrees(
            124.7746 - 1.56375588 * $k + 0.0020672 * $T2 + 0.00000215 * $T3
        ));

        $JDE = 2451550.09766
            + 29.530588861 * $k
            + 0.00015437 * $T2
            - 0.000000150 * $T3
            + 0.00000000073 * $T4;

        $JDE += -0.40720 * sin($MPrime)
            + 0.17241 * $E * sin($M)
            + 0.01608 * sin(2 * $MPrime)
            + 0.01039 * sin(2 * $F)
            + 0.00739 * $E * sin($MPrime - $M)
            - 0.00514 * $E * sin($MPrime + $M)
            + 0.00208 * $E * $E * sin(2 * $M)
            - 0.00111 * sin($MPrime - 2 * $F)
            - 0.00057 * sin($MPrime + 2 * $F)
            + 0.00056 * $E * sin(2 * $MPrime + $M)
            - 0.00042 * sin(3 * $MPrime)
            + 0.00042 * $E * sin($M + 2 * $F)
            + 0.00038 * $E * sin($M - 2 * $F)
            - 0.00024 * $E * sin(2 * $MPrime - $M)
            - 0.00017 * sin($omega)
            - 0.00007 * sin($MPrime + 2 * $M)
            + 0.00004 * sin(2 * $MPrime - 2 * $F)
            + 0.00004 * sin(3 * $M)
            + 0.00003 * sin($MPrime + $M - 2 * $F)
            + 0.00003 * sin(2 * $MPrime + 2 * $F)
            - 0.00003 * sin($MPrime + $M + 2 * $F)
            + 0.00003 * sin($MPrime - $M + 2 * $F)
            - 0.00002 * sin($MPrime - $M - 2 * $F)
            - 0.00002 * sin(3 * $MPrime + $M)
            + 0.00002 * sin(4 * $MPrime);

        $A1 = $this->degToRad($this->normalizeDegrees(299.77 + 0.107408 * $k - 0.009173 * $T2));
        $A2 = $this->degToRad($this->normalizeDegrees(251.88 + 0.016321 * $k));
        $A3 = $this->degToRad($this->normalizeDegrees(251.83 + 26.651886 * $k));
        $A4 = $this->degToRad($this->normalizeDegrees(349.42 + 36.412478 * $k));
        $A5 = $this->degToRad($this->normalizeDegrees(84.66 + 18.206239 * $k));
        $A6 = $this->degToRad($this->normalizeDegrees(141.74 + 53.303771 * $k));
        $A7 = $this->degToRad($this->normalizeDegrees(207.14 + 2.453732 * $k));
        $A8 = $this->degToRad($this->normalizeDegrees(154.84 + 7.306860 * $k));
        $A9 = $this->degToRad($this->normalizeDegrees(34.52 + 27.261239 * $k));
        $A10 = $this->degToRad($this->normalizeDegrees(207.19 + 0.121824 * $k));
        $A11 = $this->degToRad($this->normalizeDegrees(291.34 + 1.844379 * $k));
        $A12 = $this->degToRad($this->normalizeDegrees(161.72 + 24.198154 * $k));
        $A13 = $this->degToRad($this->normalizeDegrees(239.56 + 25.513099 * $k));
        $A14 = $this->degToRad($this->normalizeDegrees(331.55 + 3.592518 * $k));

        $JDE += 0.000325 * sin($A1)
            + 0.000165 * sin($A2)
            + 0.000164 * sin($A3)
            + 0.000126 * sin($A4)
            + 0.000110 * sin($A5)
            + 0.000062 * sin($A6)
            + 0.000060 * sin($A7)
            + 0.000056 * sin($A8)
            + 0.000047 * sin($A9)
            + 0.000042 * sin($A10)
            + 0.000040 * sin($A11)
            + 0.000037 * sin($A12)
            + 0.000035 * sin($A13)
            + 0.000023 * sin($A14);

        return $JDE;
    }

    /**
     * Compute Sun's apparent ecliptic longitude (degrees) at a given Julian Ephemeris Day.
     */
    public function sunApparentLongitude(float $jde): float
    {
        $T = ($jde - 2451545.0) / 36525.0;
        $T2 = $T * $T;
        $T3 = $T2 * $T;

        $L0 = self::SUN_MEAN_LONGITUDE_COEFFS[0]
            + self::SUN_MEAN_LONGITUDE_COEFFS[1] * $T
            + self::SUN_MEAN_LONGITUDE_COEFFS[2] * $T2;

        $M = self::SUN_MEAN_ANOMALY_COEFFS[0]
            + self::SUN_MEAN_ANOMALY_COEFFS[1] * $T
            + self::SUN_MEAN_ANOMALY_COEFFS[2] * $T2
            + self::SUN_MEAN_ANOMALY_COEFFS[3] * $T3;

        $M_rad = $this->degToRad($this->normalizeDegrees($M));

        $C = (1.914602 - 0.004817 * $T - 0.000014 * $T2) * sin($M_rad)
            + (0.019993 - 0.000101 * $T) * sin(2 * $M_rad)
            + 0.000289 * sin(3 * $M_rad);

        $trueLongitude = $L0 + $C;
        $omega = 125.04 - 1934.136 * $T;

        $apparentLongitude = $trueLongitude - 0.00569 - 0.00478 * sin($this->degToRad($omega));

        return $this->normalizeDegrees($apparentLongitude);
    }

    /**
     * Find the Julian Ephemeris Day where the sun's longitude reaches a target value.
     */
    public function findSolarLongitudeCrossing(float $initialJde, float $targetLongitude): float
    {
        $target = $this->normalizeDegrees($targetLongitude);
        $jde = $initialJde;

        for ($i = 0; $i < 10; $i++) {
            $lambda = $this->sunApparentLongitude($jde);
            $delta = $this->normalizeDegrees($lambda - $target);
            if ($delta > 180) {
                $delta -= 360;
            }

            if (abs($delta) < 1e-7) {
                break;
            }

            $jde -= $delta / 0.98564736; // Average solar motion (deg/day)
        }

        return $jde;
    }

    /**
     * Compute the JDE instants of all new moons intersecting a range.
     *
     * @return float[]
     */
    public function newMoonsInRange(float $startJd, float $endJd): array
    {
        $results = [];
        $kStart = (int) floor($this->approximateKFromJulianDay($startJd)) - 2;
        $kEnd = (int) ceil($this->approximateKFromJulianDay($endJd)) + 2;

        for ($k = $kStart; $k <= $kEnd; $k++) {
            $jde = $this->newMoon($k);
            if ($jde >= $startJd - 1 && $jde <= $endJd + 1) {
                $results[] = $jde;
            }
        }

        sort($results);

        return $results;
    }

    /**
     * Compute major solar terms (zhongqi) between start and end JDE.
     *
     * @return float[] Sorted list of JDE instants.
     */
    public function majorSolarTermsBetween(float $startJd, float $endJd): array
    {
        $terms = [];
        $target = 270.0;
        $jde = $this->findSolarLongitudeCrossing($startJd, $target);

        if ($jde < $startJd) {
            $jde += 29.0; // Ensure we move into range
            $target = $this->normalizeDegrees($target + 30);
            $jde = $this->findSolarLongitudeCrossing($jde, $target);
        }

        while ($jde < $endJd) {
            $terms[] = $jde;
            $target = $this->normalizeDegrees($target + 30);
            $initialGuess = $jde + 25.0;
            $jde = $this->findSolarLongitudeCrossing($initialGuess, $target);
        }

        return $terms;
    }

    /**
     * Calculate winter solstice (sun longitude 270°) for a Gregorian year.
     */
    public function winterSolstice(int $gregorianYear): float
    {
        $approx = $this->julianDayFromCalendarDate($gregorianYear, 12, 21);

        return $this->findSolarLongitudeCrossing($approx, 270.0);
    }

    /**
     * Convert JDE (TT) to UTC-based Julian Day.
     */
    public function jdeToUtJulianDay(float $jde): float
    {
        $year = 2000.0 + ($jde - 2451545.0) / 365.2425;
        $deltaTSeconds = $this->deltaTSeconds($year);

        return $jde - ($deltaTSeconds / self::SECONDS_PER_DAY);
    }

    /**
     * Convert JDE to a CarbonImmutable in the specified timezone.
     */
    public function jdeToCarbon(float $jde, string $timezone = 'UTC'): CarbonImmutable
    {
        $jdUtc = $this->jdeToUtJulianDay($jde);

        return $this->julianDayToCarbon($jdUtc, $timezone);
    }

    /**
     * ΔT approximation (seconds) for a decimal year within 1900-2100.
     *
     * Uses the polynomial recommendations from NASA (Espenak & Meeus).
     */
    public function deltaTSeconds(float $year): float
    {
        if ($year < 1900 || $year > 2100) {
            // Fallback polynomial for out-of-range years
            $u = ($year - 1820) / 100.0;

            return -20.0 + 32.0 * $u * $u;
        }

        if ($year < 1986.0) {
            $t = ($year - 2000) / 100.0;

            return 10583.6
                - 1014.41 * $t
                + 33.78311 * $t * $t
                - 5.952053 * $t * $t * $t
                - 0.1798452 * $t ** 4
                + 0.022174192 * $t ** 5
                + 0.0090316521 * $t ** 6;
        }

        if ($year < 2005.0) {
            $t = $year - 2000.0;

            return 63.86
                + 0.3345 * $t
                - 0.060374 * $t * $t
                + 0.0017275 * $t * $t * $t
                + 0.000651814 * $t ** 4
                + 0.00002373599 * $t ** 5;
        }

        if ($year < 2050.0) {
            $t = $year - 2000.0;

            return 62.92
                + 0.32217 * $t
                + 0.005589 * $t * $t;
        }

        $u = ($year - 1820) / 100.0;

        return -20.0 + 32.0 * $u * $u - 0.5628 * (2150.0 - $year);
    }

    /**
     * Convert a Gregorian calendar date (UTC midnight) to Julian Day.
     */
    public function julianDayFromCalendarDate(int $year, int $month, int $day): float
    {
        return $this->julianDayFromCarbon(
            CarbonImmutable::createFromFormat('Y-m-d H:i:s', sprintf('%04d-%02d-%02d 00:00:00', $year, $month, $day), 'UTC')
        );
    }

    private function degToRad(float $degrees): float
    {
        return $degrees * M_PI / 180.0;
    }

    private function normalizeDegrees(float $degrees): float
    {
        $result = fmod($degrees, 360.0);

        return $result < 0 ? $result + 360.0 : $result;
    }
}
