<?php

namespace App\Filament\Resources\Appointments\Tables;

use App\Enums\AppointmentStatus;
use App\Models\Branch;
use Carbon\CarbonInterface;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AppointmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('branch.name')
                    ->label('Branch')
                    ->searchable(),
                TextColumn::make('service.name')
                    ->label('Service')
                    ->searchable(),
                TextColumn::make('staff.name')
                    ->label('Staff')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->searchable(),
                TextColumn::make('start_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(AppointmentStatus::options()),
                Filter::make('branch_local_date')
                    ->label('Branch Local Date')
                    ->schema([
                        Select::make('branch_id')
                            ->label('Branch')
                            ->options(fn (): array => Branch::query()->pluck('name', 'id')->all()),
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

                        $branch = Branch::query()->find($branchId);

                        if (! $branch) {
                            return $query;
                        }

                        $startUtc = branch_local_to_utc($dateValue.' 00:00:00', $branch->timezone);
                        $endUtc = $startUtc->addDay();

                        return $query
                            ->where('branch_id', $branch->id)
                            ->where('start_at', '>=', $startUtc->toDateTimeString())
                            ->where('start_at', '<', $endUtc->toDateTimeString());
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
