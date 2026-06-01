<?php

namespace App\Filament\Support;

use App\Enums\AppointmentStatus;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class AppointmentStatusTabs
{
    /**
     * @param  array<string, int>  $statusCounts
     * @return array<string, Tab>
     */
    public static function fromCounts(array $statusCounts, bool $hidePendingForStaff = false): array
    {
        $tabs = [
            'all' => Tab::make('All')->badge(array_sum($statusCounts)),
        ];

        foreach (AppointmentStatus::cases() as $status) {
            if ($hidePendingForStaff && $status === AppointmentStatus::PENDING) {
                continue;
            }

            $tabs[$status->value] = Tab::make($status->label())
                ->badge((int) ($statusCounts[$status->value] ?? 0))
                ->modifyQueryUsing(static fn (Builder $query): Builder => $query->where('status', $status->value));
        }

        return $tabs;
    }
}
