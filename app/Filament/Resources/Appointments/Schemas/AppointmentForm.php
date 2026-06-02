<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Enums\UserRole;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        $branchCache = [];

        $resolveBranch = static function (Get $get) use (&$branchCache): ?Branch {
            $branchId = $get('branch_id');

            if (blank($branchId)) {
                return null;
            }

            $cacheKey = (string) $branchId;

            if (! array_key_exists($cacheKey, $branchCache)) {
                $branchCache[$cacheKey] = Branch::query()
                    ->select(['id', 'timezone'])
                    ->find($branchId);
            }

            return $branchCache[$cacheKey];
        };

        return $schema
            ->components([
                Section::make('Booking Details')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Select::make('branch_id')
                            ->relationship('branch', 'name')
                            ->getOptionLabelFromRecordUsing(static fn (Branch $record): string => sprintf(
                                '%s (%s)',
                                $record->name,
                                timezone_utc_offset_label($record->timezone),
                            ))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => $set('staff_id', null))
                            ->disabled(static fn (?Appointment $record): bool => ! self::canEditScheduling($record)),
                        Select::make('service_id')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(static fn (?Appointment $record): bool => ! self::canEditScheduling($record)),
                        Select::make('staff_id')
                            ->relationship(
                                name: 'staff',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query
                                    ->where('role', UserRole::STAFF->value)
                                    ->when(
                                        blank($get('branch_id')),
                                        fn (Builder $query): Builder => $query->whereRaw('1 = 0'),
                                        fn (Builder $query): Builder => $query->where('branch_id', $get('branch_id')),
                                    ),
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText(static function (?Appointment $record): ?string {
                                if (
                                    $record
                                    && filled($record->staff_id)
                                    && $record->status !== AppointmentStatus::CONFIRMED
                                ) {
                                    return 'Staff can only be reassigned while the appointment is confirmed.';
                                }

                                return null;
                            })
                            ->disabled(static function (Get $get, ?Appointment $record): bool {
                                if (! self::canEditScheduling($record) || blank($get('branch_id'))) {
                                    return true;
                                }

                                return (bool) (
                                    $record
                                    && filled($record->staff_id)
                                    && $record->status !== AppointmentStatus::CONFIRMED
                                );
                            }),
                        Select::make('customer_id')
                            ->relationship('customer', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->disabled(static fn (?Appointment $record): bool => ! self::canEditScheduling($record)),
                        TextInput::make('start_at_local')
                            ->label(static function (Get $get) use ($resolveBranch): string {
                                $branch = $resolveBranch($get);

                                if (! $branch) {
                                    return 'Start Datetime (Branch Local)';
                                }

                                return sprintf(
                                    'Start Datetime (%s)',
                                    timezone_utc_offset_label($branch->timezone),
                                );
                            })
                            ->type('datetime-local')
                            ->required()
                            ->helperText(static function (Get $get) use ($resolveBranch): string {
                                $branch = $resolveBranch($get);

                                if (! $branch) {
                                    return 'Enter the datetime in the selected branch timezone.';
                                }

                                return sprintf(
                                    'Enter datetime in branch timezone (%s, %s).',
                                    timezone_utc_offset_label($branch->timezone),
                                    $branch->timezone,
                                );
                            })
                            ->live()
                            ->disabled(static fn (?Appointment $record): bool => ! self::canEditScheduling($record))
                            ->columnSpanFull(),
                    ]),
                Section::make('Status Update')
                    ->columnSpanFull()
                    ->hiddenOn('create')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(static function (?Appointment $record): array {
                                if (! $record) {
                                    return [];
                                }

                                return $record->status->nextOptions();
                            })
                            ->default(static function (?Appointment $record): ?string {
                                if (! $record || $record->status !== AppointmentStatus::PENDING) {
                                    return null;
                                }

                                return AppointmentStatus::CONFIRMED->value;
                            }),
                        Textarea::make('remark')
                            ->label('Remark')
                            ->nullable()
                            ->rows(3)
                            ->helperText('Required when status is cancelled.')
                            ->requiredIf('status', AppointmentStatus::CANCELLED->value),
                    ]),
            ]);
    }

    private static function canEditScheduling(?Appointment $record): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        if (! $record) {
            return $user->can('create', Appointment::class);
        }

        return $user->can('editScheduling', $record);
    }
}
