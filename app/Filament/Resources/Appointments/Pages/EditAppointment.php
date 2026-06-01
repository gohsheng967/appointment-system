<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Domain\Appointments\Actions\UpdateAppointmentAction;
use App\Domain\Appointments\Actions\TransitionAppointmentStatusAction;
use App\Enums\AppointmentStatus;
use App\Filament\Resources\Appointments\AppointmentResource;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

class EditAppointment extends EditRecord
{
    protected static string $resource = AppointmentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->color('success'),
            Action::make('cancel_appointment')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => $this->record->status->canTransitionTo(AppointmentStatus::CANCELLED))
                ->form([
                    Textarea::make('cancellation_reason')
                        ->label('Cancellation Reason')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(TransitionAppointmentStatusAction::class)(
                        $this->record,
                        AppointmentStatus::CANCELLED,
                        (string) $data['cancellation_reason'],
                    );

                    $this->record->refresh();
                })
                ->successNotificationTitle('Appointment cancelled'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $branch = $this->record->branch;

        $data['start_at_local'] = utc_to_branch_local(
            $this->record->start_at,
            $branch->timezone,
        )->format('Y-m-d\TH:i');
        $data['status'] = $this->record->status === AppointmentStatus::PENDING
            ? AppointmentStatus::CONFIRMED->value
            : null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $user = auth()->user();

        $payload = $user?->isStaff() ? [] : [
            'branch_id' => (int) $data['branch_id'],
            'service_id' => (int) $data['service_id'],
            'customer_id' => (int) $data['customer_id'],
            'staff_id' => (int) $data['staff_id'],
            'start_at' => (string) $data['start_at_local'],
        ];

        if (filled($data['status'] ?? null)) {
            $payload['status'] = (string) $data['status'];
            $payload['cancellation_reason'] = $data['cancellation_reason'] ?? null;
        }

        $payload['auto_confirm_pending'] = true;

        return app(UpdateAppointmentAction::class)($record, $payload);
    }
}
