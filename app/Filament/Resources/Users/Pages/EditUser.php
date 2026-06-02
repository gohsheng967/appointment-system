<?php

namespace App\Filament\Resources\Users\Pages;

use App\Domain\Users\Actions\UpdateUserAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'User updated successfully.';
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (! $record instanceof User) {
            return $record;
        }

        return app(UpdateUserAction::class)($record, $data);
    }

    protected function getHeaderActions(): array
    {
        $hasActiveAppointments = $this->record->hasActiveAppointments();

        return [
            ViewAction::make()->color('success'),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false)
                ->disabled($hasActiveAppointments)
                ->successNotificationTitle('User deleted successfully.')
                ->tooltip($hasActiveAppointments
                    ? 'Cannot delete user with active appointments (Confirmed or In Progress).'
                    : null),
        ];
    }
}
