<?php

namespace App\Filament\Widgets;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Models\Appointment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class AppointmentStatusChartWidget extends ChartWidget
{
    protected ?string $heading = 'Appointment Status';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected ?string $pollingInterval = null;

    protected function getData(): array
    {
        $user = auth()->user();
        $cacheKey = sprintf(
            'dashboard:chart:status:%s:%s',
            $user?->id ?? 'guest',
            $user?->role?->value ?? 'none',
        );

        /** @var array<string, int> $counts */
        $counts = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($user): array {
            $query = Appointment::query()
                ->selectRaw('status, COUNT(*) as aggregate')
                ->groupBy('status');

            if ($user?->role === UserRole::STAFF) {
                $query->where('staff_id', $user->id);
            }

            return $query
                ->pluck('aggregate', 'status')
                ->map(static fn ($value): int => (int) $value)
                ->all();
        });

        $labels = [];
        $data = [];
        foreach (AppointmentStatus::cases() as $status) {
            $labels[] = $status->label();
            $data[] = (int) ($counts[$status->value] ?? 0);
        }

        return [
            'datasets' => [[
                'label' => 'Appointments',
                'data' => $data,
                'backgroundColor' => [
                    '#9ca3af', // pending
                    '#3b82f6', // confirmed
                    '#f59e0b', // in progress
                    '#10b981', // completed
                    '#ef4444', // cancelled
                    '#6b7280', // no show
                ],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}

