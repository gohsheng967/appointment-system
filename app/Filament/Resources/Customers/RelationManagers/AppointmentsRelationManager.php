<?php

namespace App\Filament\Resources\Customers\RelationManagers;

use App\Enums\AppointmentStatus;
use App\Filament\Support\AppointmentStatusTabs;
use App\Models\Appointment;
use Carbon\CarbonInterface;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Booking History';

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        $statusCounts = $this->getOwnerRecord()
            ->appointments()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        return AppointmentStatusTabs::fromCounts(
            $statusCounts,
            auth()->user()?->isStaff() ?? false,
        );
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(static fn ($query) => $query->with(['branch', 'service', 'staff']))
            ->defaultSort('start_at', 'desc')
            ->columns([
                TextColumn::make('start_at')
                    ->label('Start')
                    ->formatStateUsing(static fn (mixed $state, Appointment $record): string => self::formatDateTimeForBranch(
                        $record->start_at,
                        $record->branch?->timezone,
                    ))
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('End')
                    ->formatStateUsing(static fn (mixed $state, Appointment $record): string => self::formatDateTimeForBranch(
                        $record->end_at,
                        $record->branch?->timezone,
                    ))
                    ->sortable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable(),
                TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (AppointmentStatus|string|null $state): string => AppointmentStatus::labelFor($state))
                    ->color(static fn (AppointmentStatus|string|null $state): string => AppointmentStatus::colorFor($state)),
                TextColumn::make('cancellation_reason')
                    ->label('Remark')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('-'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->color('success')
                    ->iconButton()
                    ->tooltip('View'),
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit'),
            ])
            ->headerActions([])
            ->toolbarActions([]);
    }

    private static function formatDateTimeForBranch(?CarbonInterface $dateTime, ?string $branchTimezone): string
    {
        if (! $dateTime) {
            return '-';
        }

        $timezone = filled($branchTimezone) ? $branchTimezone : (string) config('app.timezone', 'UTC');
        $local = utc_to_branch_local($dateTime, $timezone);

        return sprintf(
            '%s (%s)',
            $local->format('Y-m-d H:i:s'),
            timezone_utc_offset_label($timezone, $local),
        );
    }
}
