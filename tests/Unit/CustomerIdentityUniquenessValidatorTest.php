<?php

namespace Tests\Unit;

use App\Domain\Customers\Services\CustomerIdentityUniquenessValidator;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CustomerIdentityUniquenessValidatorTest extends TestCase
{
    public function test_assert_at_least_one_contact_throws_when_both_are_missing(): void
    {
        $validator = new CustomerIdentityUniquenessValidator();

        try {
            $validator->assertAtLeastOneContact(null, null);

            $this->fail('Expected ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Either email or phone number is required.',
                $exception->errors()['email'][0] ?? null,
            );
            $this->assertSame(
                'Either email or phone number is required.',
                $exception->errors()['phone_number'][0] ?? null,
            );
        }
    }

    public function test_assert_at_least_one_contact_allows_single_contact_channel(): void
    {
        $validator = new CustomerIdentityUniquenessValidator();

        $validator->assertAtLeastOneContact('customer@example.com', null);
        $validator->assertAtLeastOneContact(null, '+60112223333');

        $this->addToAssertionCount(1);
    }
}

