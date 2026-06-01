<?php

namespace Tests\Unit;

use App\Support\CustomerPhoneNumberFormState;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerPhoneNumberFormStateTest extends TestCase
{
    public function test_compose_for_storage_with_local_number(): void
    {
        $normalized = CustomerPhoneNumberFormState::composeForStorage('+60', '011-222 3333');

        $this->assertSame('+60112223333', $normalized);
    }

    public function test_split_for_form_with_malaysia_number(): void
    {
        $parts = CustomerPhoneNumberFormState::splitForForm('+60112223333');

        $this->assertSame('+60', $parts['phone_country_code']);
        $this->assertSame('112223333', $parts['phone_number']);
    }

    public function test_compose_throws_on_invalid_number(): void
    {
        $this->expectException(ValidationException::class);

        CustomerPhoneNumberFormState::composeForStorage('+60', 'abc');
    }

    public function test_compose_strips_duplicate_country_code_and_leading_zeroes(): void
    {
        $normalized = CustomerPhoneNumberFormState::composeForStorage('+60', '+60 011-2223333');

        $this->assertSame('+60112223333', $normalized);
    }

    public function test_compose_defaults_to_configured_country_code_when_missing(): void
    {
        $normalized = CustomerPhoneNumberFormState::composeForStorage(null, '0112223333');

        $this->assertSame('+60112223333', $normalized);
    }

    public function test_split_for_form_with_blank_stored_phone_number(): void
    {
        $parts = CustomerPhoneNumberFormState::splitForForm(null);

        $this->assertSame('+60', $parts['phone_country_code']);
        $this->assertSame('', $parts['phone_number']);
    }

    public function test_split_for_form_with_non_default_country_keeps_digits(): void
    {
        $parts = CustomerPhoneNumberFormState::splitForForm('+441632960960');

        $this->assertSame('+60', $parts['phone_country_code']);
        $this->assertSame('441632960960', $parts['phone_number']);
    }
}
