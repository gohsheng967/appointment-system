<?php

namespace App\Filament\Resources\Customers\Pages;

use App\Domain\Customers\Services\CustomerFormDataService;
use App\Filament\Resources\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Customer created successfully.';
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return app(CustomerFormDataService::class)->prepareForSave($data);
    }
}
