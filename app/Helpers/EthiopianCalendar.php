<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Ethiopian (Ethiopic) ↔ Gregorian calendar converter.
 *
 * Ethiopian epoch: Julian Day Number 1724221
 * (= Meskerem 1, Year 1 E.C. = August 29, 8 CE in the Julian proleptic calendar)
 *
 * Verified reference: Sep 11, 2019 G.C. = Meskerem 1, 2012 E.C.
 */
class EthiopianCalendar
{
    const EPOCH_JDN = 1724221;

    const MONTHS_EN = [
        1  => 'Meskerem', 2  => 'Tikimt',  3  => 'Hidar',
        4  => 'Tahsas',   5  => 'Tir',     6  => 'Yakatit',
        7  => 'Megabit',  8  => 'Miyazia', 9  => 'Ginbot',
        10 => 'Sene',     11 => 'Hamle',   12 => 'Nehase',
        13 => 'Pagumē',
    ];

    const MONTHS_AM = [
        1  => 'መስከረም', 2  => 'ጥቅምት',  3  => 'ህዳር',
        4  => 'ታህሳስ',  5  => 'ጥር',    6  => 'የካቲት',
        7  => 'መጋቢት',  8  => 'ሚያዚያ', 9  => 'ግንቦት',
        10 => 'ሰኔ',    11 => 'ሐምሌ',   12 => 'ነሐሴ',
        13 => 'ጳጉሜ',
    ];

    // ── Pure-PHP Julian Day Number helpers ──────────────────────────────────

    private static function gregorianToJDN(int $year, int $month, int $day): int
    {
        $a = intdiv(14 - $month, 12);
        $y = $year + 4800 - $a;
        $m = $month + 12 * $a - 3;
        return $day
            + intdiv(153 * $m + 2, 5)
            + 365 * $y
            + intdiv($y, 4)
            - intdiv($y, 100)
            + intdiv($y, 400)
            - 32045;
    }

    private static function jdnToGregorian(int $jdn): array
    {
        $l = $jdn + 68569;
        $n = intdiv(4 * $l, 146097);
        $l = $l - intdiv(146097 * $n + 3, 4);
        $i = intdiv(4000 * ($l + 1), 1461001);
        $l = $l - intdiv(1461 * $i, 4) + 31;
        $j = intdiv(80 * $l, 2447);
        $d = $l - intdiv(2447 * $j, 80);
        $l = intdiv($j, 11);
        $m = $j + 2 - 12 * $l;
        $y = 100 * ($n - 49) + $i + $l;
        return ['year' => $y, 'month' => $m, 'day' => $d];
    }

    // ── Public API ───────────────────────────────────────────────────────────

    /**
     * Convert a Gregorian Carbon date to an Ethiopian [year, month, day] array.
     */
    public static function toEthiopian(Carbon $date): array
    {
        $jdn       = self::gregorianToJDN($date->year, $date->month, $date->day);
        $ethOffset = $jdn - self::EPOCH_JDN;

        $year4       = intdiv($ethOffset, 1461);
        $remaining   = $ethOffset % 1461;

        if ($remaining < 365) {
            $yearInCycle = 0; $day = $remaining;
        } elseif ($remaining < 730) {
            $yearInCycle = 1; $day = $remaining - 365;
        } elseif ($remaining < 1095) {
            $yearInCycle = 2; $day = $remaining - 730;
        } else {
            $yearInCycle = 3; $day = $remaining - 1095;
        }

        return [
            'year'  => $year4 * 4 + $yearInCycle + 1,
            'month' => intdiv($day, 30) + 1,
            'day'   => ($day % 30) + 1,
        ];
    }

    /**
     * Convert Ethiopian (year, month, day) to a Gregorian Carbon date.
     */
    public static function toGregorian(int $ethYear, int $ethMonth, int $ethDay): Carbon
    {
        $year4       = intdiv($ethYear - 1, 4);
        $yearInCycle = ($ethYear - 1) % 4;

        $jdn = self::EPOCH_JDN + $year4 * 1461;
        if ($yearInCycle >= 1) $jdn += 365;
        if ($yearInCycle >= 2) $jdn += 365;
        if ($yearInCycle >= 3) $jdn += 365;
        $jdn += ($ethMonth - 1) * 30 + ($ethDay - 1);

        $greg = self::jdnToGregorian($jdn);
        return Carbon::create($greg['year'], $greg['month'], $greg['day']);
    }

    /**
     * Format a Carbon date as an Ethiopian date string.
     *
     * @param  Carbon      $date
     * @param  bool        $amharic  true → Amharic month names
     * @param  bool        $suffix   true → append " E.C."
     */
    public static function format(?Carbon $date, bool $amharic = false, bool $suffix = true): string
    {
        if (!$date) return '—';
        $eth    = self::toEthiopian($date);
        $months = $amharic ? self::MONTHS_AM : self::MONTHS_EN;
        $name   = $months[$eth['month']] ?? '?';
        return $eth['day'] . ' ' . $name . ' ' . $eth['year'] . ($suffix ? ' E.C.' : '');
    }

    /**
     * Check whether an Ethiopian year is a leap year (year % 4 === 0).
     */
    public static function isLeapYear(int $ethYear): bool
    {
        return $ethYear % 4 === 0;
    }

    /**
     * Days in a given Ethiopian month (1-12 → 30, 13 → 5 or 6 on leap year).
     */
    public static function daysInMonth(int $ethYear, int $ethMonth): int
    {
        if ($ethMonth < 13) return 30;
        return self::isLeapYear($ethYear) ? 6 : 5;
    }
}
