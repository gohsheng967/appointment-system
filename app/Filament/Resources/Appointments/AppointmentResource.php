<?php

namespace App\Filament\Resources\Appointments;

use App\Domain\Appointments\Services\AppointmentAuthorizationService;
use App\Filament\Resources\Appointments\Pages\CreateAppointment;
use App\Filament\Resources\Appointments\Pages\EditAppointment;
use App\Filament\Resources\Appointments\Pages\ListAppointments;
use App\Filament\Resources\Appointments\Pages\ViewAppointment;
use App\Filament\Resources\Appointments\Schemas\AppointmentForm;
use App\Filament\Resources\Appointments\Schemas\AppointmentInfolist;
use App\Filament\Resources\Appointments\Tables\AppointmentsTable;
use App\Models\Appointment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    public static function form(Schema $schema): Schema
    {
        return AppointmentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return AppointmentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AppointmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getEloquentQuery(): Builder
    {
        return app(AppointmentAuthorizationService::class)
            ->scopeVisibleTo(
                parent::getEloquentQuery()->with(['branch', 'service', 'staff', 'customer']),
                Auth::user(),
            );
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();

        return $user?->can('viewAny', Appointment::class) ?? false;
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();

        return $user?->can('create', Appointment::class) ?? false;
    }

    public static function canEdit(Model $record): bool
    {
        $user = Auth::user();

        return $record instanceof Appointment
            && ($user?->can('editScheduling', $record) ?? false);
    }

    public static function canDelete(Model $record): bool
    {
        $user = Auth::user();

        return $record instanceof Appointment
            && ($user?->can('delete', $record) ?? false);
    }

    public static function canDeleteAny(): bool
    {
        $user = Auth::user();

        return $user?->can('deleteAny', Appointment::class) ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAppointments::route('/'),
            'create' => CreateAppointment::route('/create'),
            'view' => ViewAppointment::route('/{record}'),
            'edit' => EditAppointment::route('/{record}/edit'),
        ];
    }
}
