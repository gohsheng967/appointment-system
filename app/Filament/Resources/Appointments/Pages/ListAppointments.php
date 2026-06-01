<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Filament\Support\AppointmentStatusTabs;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListAppointments extends ListRecords
{
    protected static string $resource = AppointmentResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $statusCounts = AppointmentResource::getEloquentQuery()
            ->withoutEagerLoads()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return AppointmentStatusTabs::fromCounts(
            $statusCounts,
            auth()->user()?->isStaff() ?? false,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
        ];
    }
}
