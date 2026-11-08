<?php

namespace App\Services;

use App\Models\WritingActivity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class WritingOverview
{
    public function forManuscripts(Collection $manuscripts, int $days, string $timezone): array
    {
        $ids = $manuscripts->pluck('id');
        $today = CarbonImmutable::now($timezone)->startOfDay();
        $start = $today->subDays($days - 1);
        $week = $today->startOfWeek(CarbonImmutable::MONDAY);
        $month = $today->startOfMonth();
        $queryStart = min($start, $week, $month);
        $events = WritingActivity::whereIn('manuscript_id', $ids)
            ->where('recorded_at', '>=', $queryStart->utc())
            ->orderBy('recorded_at')
            ->orderBy('id')
            ->get();
        $totals = fn ($from) => $this->totals(
            $events->filter(fn ($event) => $event->kind === 'writing' && $event->recorded_at->gte($from->utc())),
            $timezone
        );
        $tracked = WritingActivity::whereIn('manuscript_id', $ids)->min('recorded_at');
        $latestBeforeStart = WritingActivity::selectRaw('MAX(id)')
            ->whereIn('manuscript_id', $ids)
            ->where('recorded_at', '<', $start->utc())
            ->groupBy('manuscript_id');
        $snapshots = WritingActivity::whereIn('id', $latestBeforeStart)
            ->get()
            ->pluck('manuscript_words', 'manuscript_id')
            ->all();
        $dailyEvents = $events->filter(fn ($event) => $event->recorded_at->gte($start->utc()))
            ->groupBy(fn ($event) => $event->recorded_at->setTimezone($timezone)->format('Y-m-d'));
        $series = [];
        for ($date = $start; $date->lte($today); $date = $date->addDay()) {
            $onDay = $dailyEvents->get($date->format('Y-m-d'), collect());
            foreach ($onDay as $event) {
                $snapshots[$event->manuscript_id] = $event->manuscript_words;
            }
            $writing = $onDay->where('kind', 'writing');
            $recorded = $tracked && $date->endOfDay()->gte(CarbonImmutable::parse($tracked, 'UTC'));
            $series[] = [
                'date' => $date->format('Y/m/d'),
                'added' => $recorded ? $writing->sum('words_added') : null,
                'removed' => $recorded ? $writing->sum('words_removed') : null,
                'total' => $snapshots ? array_sum($snapshots) : null,
            ];
        }

        return ['today' => $totals($today), 'week' => $totals($week), 'month' => $totals($month), 'period' => $totals($start), 'series' => $series,
            'tracking_since' => $tracked ? CarbonImmutable::parse($tracked, 'UTC')->setTimezone($timezone)->format('Y/m/d') : null,
            'timezone' => $timezone];
    }

    private function totals(Collection $events, string $timezone): array
    {
        return [
            'added' => $events->sum('words_added'),
            'removed' => $events->sum('words_removed'),
            'net' => $events->sum('words_added') - $events->sum('words_removed'),
            'active_days' => $events->map(fn ($event) => $event->recorded_at->setTimezone($timezone)->format('Y-m-d'))->unique()->count(),
        ];
    }
}
