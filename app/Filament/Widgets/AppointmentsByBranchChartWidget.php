<?php

namespace App\Filament\Widgets;

use App\Filament\Support\AppointmentDashboardScope;
use Illuminate\Support\Facades\Cache;

class AppointmentsByBranchChartWidget extends DashboardChartWidget
{
    protected ?string $heading = 'Appointments By Branch';

    protected int|string|array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];

    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return app(AppointmentDashboardScope::class)->canViewBranchBreakdown(auth()->user());
    }

    protected function getData(): array
    {
        $user = auth()->user();
        $cacheKey = sprintf(
            'dashboard:chart:branch:%s:%s',
            $user?->id ?? 'guest',
            $user?->role?->value ?? 'none',
        );

        $grouped = Cache::remember($cacheKey, now()->addSeconds(30), function () use ($user): array {
            $query = app(AppointmentDashboardScope::class)
                ->appointmentsQuery($user)
                ->selectRaw('branches.name as branch_name, COUNT(appointments.id) as aggregate')
                ->join('branches', 'branches.id', '=', 'appointments.branch_id')
                ->groupBy('branches.name')
                ->orderByDesc('aggregate');

            return $query
                ->pluck('aggregate', 'branch_name')
                ->map(static fn ($value): int => (int) $value)
                ->all();
        });

        return [
            'datasets' => [[
                'label' => 'Appointments',
                'data' => array_values($grouped),
                'backgroundColor' => [
                    '#f59e0b',
                    '#3b82f6',
                    '#10b981',
                    '#a855f7',
                    '#ef4444',
                    '#6b7280',
                ],
            ]],
            'labels' => array_keys($grouped),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
