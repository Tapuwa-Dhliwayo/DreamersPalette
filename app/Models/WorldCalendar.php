<?php

namespace App\Models;

use App\Models\Concerns\ArchivesStudioRecords;
use App\Models\Concerns\RevisesWorldRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class WorldCalendar extends Model
{
    use ArchivesStudioRecords, RevisesWorldRecord;

    protected $table = 'studio_world_calendars';

    protected $fillable = ['name', 'months', 'weekdays', 'leap_every', 'leap_month', 'leap_days', 'notes_md'];

    protected function casts(): array
    {
        return ['months' => 'array', 'weekdays' => 'array', 'leap_every' => 'integer', 'leap_month' => 'integer', 'leap_days' => 'integer', 'revision' => 'integer'];
    }

    public function daysBeforeYear(int $year): int
    {
        $years = $year - 1;

        return array_sum(array_column($this->months, 'days')) * $years + ($this->leap_every ? intdiv($years, $this->leap_every) * $this->leap_days : 0);
    }

    public function monthDays(int $year, int $month): int
    {
        return (int) $this->months[$month - 1]['days'] + ($this->leap_every && $year % $this->leap_every === 0 && $month === $this->leap_month ? $this->leap_days : 0);
    }

    public function ordinal(int $year, int $month, int $day): int
    {
        if ($year < 1 || $year > 1000000 || $month < 1 || $month > count($this->months) || $day < 1 || $day > $this->monthDays($year, $month)) {
            throw ValidationException::withMessages(['dateForm.day' => 'That day does not exist in this calendar.']);
        }
        $ordinal = $this->daysBeforeYear($year) + $day - 1;
        for ($index = 1; $index < $month; $index++) {
            $ordinal += $this->monthDays($year, $index);
        }

        return $ordinal;
    }

    public function dateFor(int $ordinal): array
    {
        $low = 1;
        $high = 1000000;
        while ($low < $high) {
            $middle = intdiv($low + $high + 1, 2);
            if ($this->daysBeforeYear($middle) <= $ordinal) {
                $low = $middle;
            } else {
                $high = $middle - 1;
            }
        }
        $remainder = $ordinal - $this->daysBeforeYear($low);
        $month = 1;
        while ($month < count($this->months) && $remainder >= $this->monthDays($low, $month)) {
            $remainder -= $this->monthDays($low, $month);
            $month++;
        }

        return ['year' => $low, 'month' => $month, 'day' => $remainder + 1, 'weekday' => count($this->weekdays) ? $this->weekdays[$ordinal % count($this->weekdays)] : null];
    }

    public function label(int $ordinal): string
    {
        $date = $this->dateFor($ordinal);

        return sprintf('%04d/%02d/%02d', $date['year'], $date['month'], $date['day']).($date['weekday'] ? ' · '.$date['weekday'] : '');
    }
}
