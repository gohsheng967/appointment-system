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

    protected string $savedNotificationTitle = 'Appointment updated successfully.';

    protected function getSavedNotificationTitle(): ?string
    {
        return $this->savedNotificationTitle;
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->color('success'),
            Action::make('cancel_appointment')
                ->label('Cancel')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => (auth()->user()?->can('transitionStatus', $this->record) ?? false)
                    && $this->record->status->canTransitionTo(AppointmentStatus::CANCELLED))
                ->form([
                    Textarea::make('remark')
                        ->label('Remark')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data): void {
                    app(TransitionAppointmentStatusAction::class)(
                        $this->record,
                        AppointmentStatus::CANCELLED,
                        (string) $data['remark'],
                    );

                    $this->record->refresh();
                })
                ->successNotificationTitle('Appointment cancelled successfully.')
                ->successRedirectUrl(static fn (): string => AppointmentResource::getUrl()),
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
        $data['remark'] = $data['cancellation_reason'] ?? null;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $this->savedNotificationTitle = 'Appointment updated successfully.';

        $canEditScheduling = auth()->user()?->can('editScheduling', $this->record) ?? false;

        $payload = $canEditScheduling ? [
            'branch_id' => (int) $data['branch_id'],
            'service_id' => (int) $data['service_id'],
            'customer_id' => (int) $data['customer_id'],
            'staff_id' => (int) $data['staff_id'],
            'start_at' => (string) $data['start_at_local'],
        ] : [];

        if (filled($data['status'] ?? null)) {
            $payload['status'] = (string) $data['status'];
            $payload['remark'] = $data['remark'] ?? null;
        }

        if (($payload['status'] ?? null) === AppointmentStatus::CANCELLED->value) {
            $this->savedNotificationTitle = 'Appointment cancelled successfully.';
        }

        $payload['auto_confirm_pending'] = true;

        return app(UpdateAppointmentAction::class)($record, $payload);
    }
}
