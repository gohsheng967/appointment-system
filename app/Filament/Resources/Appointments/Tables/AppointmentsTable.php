<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Domain\Appointments\Actions\UpdateAppointmentAction;
use App\Domain\Appointments\Actions\TransitionAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use App\Models\User;
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
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('end_at')
                    ->dateTime('Y-m-d H:i')
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
                Action::make('reassign_staff')
                    ->label('Reassign')
                    ->authorize(static fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->icon('heroicon-o-user-plus')
                    ->color(static fn (Appointment $record): string => in_array($record->status, [
                        AppointmentStatus::PENDING,
                        AppointmentStatus::COMPLETED,
                        AppointmentStatus::CANCELLED,
                        AppointmentStatus::NO_SHOW,
                    ], true) ? 'gray' : 'info')
                    ->iconButton()
                    ->tooltip('Reassign Staff')
                    ->visible(static fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->disabled(static fn (Appointment $record): bool => in_array($record->status, [
                        AppointmentStatus::PENDING,
                        AppointmentStatus::COMPLETED,
                        AppointmentStatus::CANCELLED,
                        AppointmentStatus::NO_SHOW,
                    ], true))
                    ->extraAttributes(static fn (Appointment $record): array => in_array($record->status, [
                        AppointmentStatus::PENDING,
                        AppointmentStatus::COMPLETED,
                        AppointmentStatus::CANCELLED,
                        AppointmentStatus::NO_SHOW,
                    ], true) ? ['class' => 'cursor-not-allowed'] : [])
                    ->form([
                        Placeholder::make('booking_info')
                            ->label('Booking Info')
                            ->content(static function (Appointment $record): HtmlString {
                                $start = $record->start_at?->format('Y-m-d H:i') ?? '-';
                                $end = $record->end_at?->format('Y-m-d H:i') ?? '-';

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
                            }),
                        Select::make('staff_id')
                            ->label('New Staff')
                            ->required()
                            ->options(static function (Appointment $record): array {
                                return User::query()
                                    ->where('role', UserRole::STAFF->value)
                                    ->where('branch_id', $record->branch_id)
                                    ->when(
                                        filled($record->staff_id),
                                        fn ($query) => $query->whereKeyNot($record->staff_id),
                                    )
                                    ->orderBy('name')
                                    ->pluck('name', 'id')
                                    ->all();
                            }),
                    ])
                    ->action(static function (Appointment $record, array $data): void {
                        app(UpdateAppointmentAction::class)($record, [
                            'staff_id' => (int) $data['staff_id'],
                        ]);
                    })
                    ->successNotificationTitle('Staff reassigned'),
                Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color(static fn (Appointment $record): string => ($record->status === AppointmentStatus::PENDING || $record->status->nextStatuses() === []) ? 'gray' : 'warning')
                    ->iconButton()
                    ->tooltip('Update Status')
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
                    ->successNotificationTitle('Appointment status updated'),
                ViewAction::make()
                    ->color('success')
                    ->iconButton()
                    ->tooltip('View'),
                Action::make('edit_record')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->color(static fn (Appointment $record): string => $record->status->isTerminal() ? 'gray' : 'warning')
                    ->iconButton()
                    ->visible(static fn (): bool => auth()->user()?->isAdmin() ?? false)
                    ->disabled(static fn (Appointment $record): bool => $record->status->isTerminal())
                    ->extraAttributes(static fn (Appointment $record): array => $record->status->isTerminal()
                        ? ['class' => 'cursor-not-allowed']
                        : [])
                    ->url(static fn (Appointment $record): string => AppointmentResource::getUrl('edit', ['record' => $record]))
                    ->tooltip('Edit'),
            ])
            ->toolbarActions([]);
    }

}
