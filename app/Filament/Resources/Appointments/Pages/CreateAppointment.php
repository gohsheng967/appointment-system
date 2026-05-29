<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Domain\Appointments\Actions\CreateAppointmentAction;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(CreateAppointmentAction::class)([
            'branch_id' => (int) $data['branch_id'],
            'service_id' => (int) $data['service_id'],
            'customer_id' => (int) $data['customer_id'],
            'staff_id' => isset($data['staff_id']) ? (int) $data['staff_id'] : null,
            'start_at' => (string) $data['start_at_local'],
        ]);
    }
}
