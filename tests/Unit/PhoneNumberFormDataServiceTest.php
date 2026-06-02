<?php

namespace Tests\Unit;

use App\Support\PhoneNumberFormDataService;
use Tests\TestCase;

class PhoneNumberFormDataServiceTest extends TestCase
{
    public function test_prepare_for_fill_splits_stored_phone_number_for_form_state(): void
    {
        $service = new PhoneNumberFormDataService();

        $data = $service->prepareForFill([
            'name' => 'Sample Branch',
            'phone_number' => '+60112223333',
        ]);

        $this->assertSame('Sample Branch', $data['name']);
        $this->assertSame('+60', $data['phone_country_code']);
        $this->assertSame('112223333', $data['phone_number']);
    }

    public function test_prepare_for_save_normalizes_phone_number_and_removes_form_only_fields(): void
    {
        $service = new PhoneNumberFormDataService();

        $data = $service->prepareForSave([
            'name' => 'Sample Branch',
            'phone_country_code' => '+60',
            'phone_number' => '011-222 3333',
        ]);

        $this->assertSame('Sample Branch', $data['name']);
        $this->assertSame('+60112223333', $data['phone_number']);
        $this->assertArrayNotHasKey('phone_country_code', $data);
    }
}
