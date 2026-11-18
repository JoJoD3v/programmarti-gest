<?php

namespace App\Ticketing\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * Business-day arithmetic (Mon-Fri minus the configured holidays).
 */
class WorkingDays
{
    public static function isWorkingDay(CarbonInterface $date): bool
    {
        return $date->isWeekday() && ! in_array($date->toDateString(), (array) config('ticketing.holidays'), true);
    }

    /**
     * The instant that is $days working days before $from (same time of day).
     */
    public static function subtract(CarbonInterface $from, int $days): CarbonImmutable
    {
        $date = CarbonImmutable::instance($from);

        while ($days > 0) {
            $date = $date->subDay();
            if (self::isWorkingDay($date)) {
                $days--;
            }
        }

        return $date;
    }

    /**
     * Working hours between two instants (24h per working day, a deliberately simple measure).
     */
    public static function hoursBetween(CarbonInterface $start, CarbonInterface $end): float
    {
        $hours = 0.0;
        $cursor = CarbonImmutable::instance($start);
        $end = CarbonImmutable::instance($end);

        while ($cursor->lt($end)) {
            $next = $cursor->addDay()->startOfDay();
            $slice = ($next->lt($end) ? $next : $end)->diffInSeconds($cursor, true) / 3600;

            if (self::isWorkingDay($cursor)) {
                $hours += $slice;
            }

            $cursor = $next;
        }

        return round($hours, 2);
    }
}
