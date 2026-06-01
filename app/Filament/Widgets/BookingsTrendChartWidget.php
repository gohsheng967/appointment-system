<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class BookingsTrendChartWidget extends ChartWidget
{
    protected ?string $heading = 'Bookings Last 7 Days';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        $cacheKey = sprintf(
            'dashboard:chart:trend:%s:%s',
            $user?->id ?? 'guest',
            $user?->role?->value ?? 'none',
        );

        /** @var array<string, int> $grouped */
        $grouped = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($user): array {
            $start = Carbon::today('UTC')->subDays(6);
            $end = Carbon::tomorrow('UTC');

            $query = Appointment::query()
                ->where('start_at', '>=', $start)
                ->where('start_at', '<', $end)
                ->selectRaw('DATE(start_at) as day, COUNT(*) as aggregate')
                ->groupBy('day')
                ->orderBy('day');

            if ($user?->role === UserRole::STAFF) {
                $query->where('staff_id', $user->id);
            }

            return $query
                ->pluck('aggregate', 'day')
                ->map(static fn ($value): int => (int) $value)
                ->all();
        });

        $labels = [];
        $data = [];
        foreach (range(6, 0) as $offset) {
            $day = Carbon::today('UTC')->subDays($offset);
            $key = $day->toDateString();
            $labels[] = $day->format('M d');
            $data[] = (int) ($grouped[$key] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Bookings',
                'data' => $data,
                'borderColor' => '#f59e0b',
                'backgroundColor' => 'rgba(245, 158, 11, 0.15)',
                'tension' => 0.35,
                'fill' => true,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

