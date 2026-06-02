<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Domain\Appointments\Data\AppointmentReassignmentAvailability;
use App\Domain\Appointments\Actions\UpdateAppointmentAction;
use App\Domain\Appointments\Actions\TransitionAppointmentStatusAction;
use App\Domain\Appointments\Services\AppointmentReassignmentAvailabilityService;
use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('booking_reference')
                    ->label('Reference')
                    ->toggleable(),
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('start_at')
                    ->formatStateUsing(static fn (mixed $state, Appointment $record): string => self::formatDateTimeForBranch(
                        $record->start_at,
                        $record->branch?->timezone,
                    ))
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_at')
                    ->formatStateUsing(static fn (mixed $state, Appointment $record): string => self::formatDateTimeForBranch(
                        $record->end_at,
                        $record->branch?->timezone,
                    ))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(static fn (AppointmentStatus|string|null $state): string => AppointmentStatus::labelFor($state))
                    ->color(static fn (AppointmentStatus|string|null $state): string => AppointmentStatus::colorFor($state))
                    ->sortable()
                    ->toggleable(),
            ])
            ->columnManagerColumns(2)
            ->persistColumnsInSession()
            ->filters([
                Filter::make('branch_local_date')
                    ->label('Branch Local Date')
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(fn (): array => Branch::query()->pluck('name', 'id')->all()),
                        Select::make('service.id')
                            ->label('Service')
                            ->options(fn (): array => Service::query()->pluck('name', 'id')->all()),
                        DatePicker::make('date')
                            ->native(false),
                    ])
                    ->query(function ($query, array $data) {
                        $branchId = $data['branch_id'] ?? null;
                        $date = $data['date'] ?? null;

                        if (! $branchId || ! $date) {
                            return $query;
                        }

                        $dateValue = $date instanceof CarbonInterface
                            ? $date->format('Y-m-d')
                            : (string) $date;

                        return $query->forBranchLocalDate((int) $branchId, $dateValue);
                    }),
            ])
            ->recordActions([
                self::reassignStaffAction(),
                self::updateStatusAction(),
                ViewAction::make()
                    ->color('success')
                    ->iconButton()
                    ->tooltip('View'),
                self::editRecordAction(),
            ])
            ->toolbarActions([]);
    }

    private static function reassignStaffAction(): Action
    {
        return Action::make('reassign_staff')
            ->label('Reassign')
            ->authorize(static fn (Appointment $record): bool => auth()->user()?->can('reassignStaff', $record) ?? false)
            ->icon('heroicon-o-user-plus')
            ->color(static fn (Appointment $record): string => self::getReassignmentAvailability($record)->isAvailable() ? 'info' : 'gray')
            ->iconButton()
            ->tooltip(static fn (Appointment $record): string => self::getReassignStaffTooltip($record))
            ->visible(static fn (Appointment $record): bool => auth()->user()?->can('reassignStaff', $record) ?? false)
            ->disabled(static fn (Appointment $record): bool => ! self::getReassignmentAvailability($record)->isAvailable())
            ->extraAttributes(static fn (Appointment $record): array => self::getReassignmentAvailability($record)->isAvailable()
                ? []
                : ['class' => 'cursor-not-allowed'])
            ->form([
                Placeholder::make('booking_info')
                    ->label('Booking Info')
                    ->content(static fn (Appointment $record): HtmlString => self::bookingInfoContent($record)),
                Select::make('staff_id')
                    ->label('New Staff')
                    ->required()
                    ->placeholder('Select an available staff member')
                    ->helperText('Only staff who are free for this time slot are listed.')
                    ->options(static fn (Appointment $record): array => self::getReassignmentAvailability($record)->staffOptions),
            ])
            ->action(static function (Appointment $record, array $data): void {
                app(UpdateAppointmentAction::class)($record, [
                    'staff_id' => (int) $data['staff_id'],
                ]);
            })
            ->successNotificationTitle('Staff reassigned successfully.');
    }

    private static function updateStatusAction(): Action
    {
        return Action::make('update_status')
            ->label('Update Status')
            ->authorize(static fn (Appointment $record): bool => auth()->user()?->can('transitionStatus', $record) ?? false)
            ->icon('heroicon-o-arrow-path')
            ->color(static fn (Appointment $record): string => ($record->status === AppointmentStatus::PENDING || $record->status->nextStatuses() === []) ? 'gray' : 'warning')
            ->iconButton()
            ->tooltip('Update Status')
            ->visible(static fn (Appointment $record): bool => auth()->user()?->can('transitionStatus', $record) ?? false)
            ->disabled(static fn (Appointment $record): bool => $record->status === AppointmentStatus::PENDING || $record->status->nextStatuses() === [])
            ->extraAttributes(static fn (Appointment $record): array => ($record->status === AppointmentStatus::PENDING || $record->status->nextStatuses() === [])
                ? ['class' => 'cursor-not-allowed']
                : [])
            ->form([
                Select::make('status')
                    ->label('New Status')
                    ->required()
                    ->options(static fn (Appointment $record): array => $record->status->nextOptions()),
                Textarea::make('remark')
                    ->label('Remark')
                    ->requiredIf('status', AppointmentStatus::CANCELLED->value)
                    ->rows(3),
            ])
            ->action(static function (Appointment $record, array $data): void {
                app(TransitionAppointmentStatusAction::class)(
                    $record,
                    AppointmentStatus::from((string) $data['status']),
                    $data['status'] === AppointmentStatus::CANCELLED->value
                        ? (string) ($data['remark'] ?? '')
                        : null,
                );
            })
            ->successNotificationTitle('Appointment status updated successfully.');
    }

    private static function editRecordAction(): Action
    {
        return Action::make('edit_record')
            ->label('Edit')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->iconButton()
            ->authorize(static fn (Appointment $record): bool => auth()->user()?->can('editScheduling', $record) ?? false)
            ->visible(static fn (Appointment $record): bool => auth()->user()?->can('editScheduling', $record) ?? false)
            ->url(static fn (Appointment $record): string => AppointmentResource::getUrl('edit', ['record' => $record]))
            ->tooltip('Edit');
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

    private static function bookingInfoContent(Appointment $record): HtmlString
    {
        $start = self::formatDateTimeForBranch($record->start_at, $record->branch?->timezone);
        $end = self::formatDateTimeForBranch($record->end_at, $record->branch?->timezone);

        $rows = [
            'Customer' => $record->customer?->name ?? '-',
            'Service' => $record->service?->name ?? '-',
            'Branch' => $record->branch?->name ?? '-',
            'Current Staff' => $record->staff?->name ?? '-',
            'Datetime' => "{$start} - {$end}",
            'Status' => AppointmentStatus::labelFor($record->status),
        ];

        $html = collect($rows)
            ->map(static fn (string $value, string $label): string => sprintf(
                '<div><span class="font-medium text-gray-700">%s:</span> <span class="text-gray-900">%s</span></div>',
                e($label),
                e($value),
            ))
            ->implode('');

        return new HtmlString('<div class="space-y-1">'.$html.'</div>');
    }

    private static function getReassignStaffTooltip(Appointment $record): string
    {
        $availability = self::getReassignmentAvailability($record);

        if ($availability->blockedByStatus) {
            return 'Only confirmed appointments can be reassigned';
        }

        if (! $availability->hasAvailableStaffOptions()) {
            return 'No other staff are available for this time range';
        }

        return 'Reassign Staff';
    }

    private static function getReassignmentAvailability(Appointment $record): AppointmentReassignmentAvailability
    {
        static $cache = [];

        $cacheKey = implode(':', [
            $record->getKey(),
            (string) $record->branch_id,
            (string) $record->staff_id,
            (string) $record->start_at?->getTimestamp(),
            (string) $record->end_at?->getTimestamp(),
            $record->status instanceof AppointmentStatus ? $record->status->value : (string) $record->status,
        ]);

        if (! array_key_exists($cacheKey, $cache)) {
            $cache[$cacheKey] = app(AppointmentReassignmentAvailabilityService::class)->evaluate($record);
        }

        return $cache[$cacheKey];
    }

}
