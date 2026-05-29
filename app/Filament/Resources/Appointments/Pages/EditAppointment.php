<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Domain\Appointments\Actions\UpdateAppointmentAction;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $branch = $this->record->branch;

        $data['start_at_local'] = utc_to_branch_local(
            $this->record->start_at,
            $branch->timezone,
        )->format('Y-m-d\TH:i');
        $data['status'] = $this->record->status->value;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = auth()->user();

        $payload = $user?->isStaff() ? [
            'status' => $data['status'] ?? $record->status->value,
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
        ] : [
            'branch_id' => (int) $data['branch_id'],
            'service_id' => (int) $data['service_id'],
            'customer_id' => (int) $data['customer_id'],
            'staff_id' => (int) $data['staff_id'],
            'start_at' => (string) $data['start_at_local'],
            'status' => $data['status'] ?? $record->status->value,
            'cancellation_reason' => $data['cancellation_reason'] ?? null,
        ];

        return app(UpdateAppointmentAction::class)($record, $payload);
    }
}
