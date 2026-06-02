<?php

namespace Tests\Unit;

use App\Domain\Customers\Services\CustomerFormDataService;
use App\Domain\Customers\Services\CustomerIdentityUniquenessValidator;
use App\Support\PhoneNumberFormDataService;
use Tests\TestCase;

class CustomerFormDataServiceTest extends TestCase
{
    public function test_prepare_for_fill_splits_stored_phone_number_for_form_state(): void
    {
        $service = new CustomerFormDataService(
            new PhoneNumberFormDataService(),
            $this->createStub(CustomerIdentityUniquenessValidator::class),
        );

        $data = $service->prepareForFill([
            'name' => 'Sample Customer',
            'phone_number' => '+60112223333',
        ]);

        $this->assertSame('Sample Customer', $data['name']);
        $this->assertSame('+60', $data['phone_country_code']);
        $this->assertSame('112223333', $data['phone_number']);
    }

    public function test_prepare_for_save_normalizes_customer_contact_details_and_removes_form_only_fields(): void
    {
        $validator = $this->createMock(CustomerIdentityUniquenessValidator::class);
        $service = new CustomerFormDataService(new PhoneNumberFormDataService(), $validator);

        $validator->expects($this->once())
            ->method('normalizeEmail')
            ->with(' Customer@Example.com ')
            ->willReturn('customer@example.com');

        $validator->expects($this->once())
            ->method('assertAtLeastOneContact')
            ->with('customer@example.com', '+60112223333');

        $validator->expects($this->once())
            ->method('assertUniqueIdentity')
            ->with('customer@example.com', '+60112223333', 42);

        $data = $service->prepareForSave([
            'name' => 'Sample Customer',
            'email' => ' Customer@Example.com ',
            'phone_country_code' => '+60',
            'phone_number' => '011-222 3333',
        ], 42);

        $this->assertSame('Sample Customer', $data['name']);
        $this->assertSame('customer@example.com', $data['email']);
        $this->assertSame('+60112223333', $data['phone_number']);
        $this->assertArrayNotHasKey('phone_country_code', $data);
    }
}
