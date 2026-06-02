<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Domain\Customers\Services\CustomerFormDataService;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Customer updated successfully.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return app(CustomerFormDataService::class)->prepareForFill($data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $exceptCustomerId = $this->record ? (int) $this->record->getKey() : null;

        return app(CustomerFormDataService::class)->prepareForSave($data, $exceptCustomerId);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()->color('success'),
        ];
    }
}
