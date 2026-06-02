<?php

namespace Tests\Unit;

use App\Rules\SubmittedPhoneNumber;
use App\Support\PhoneNumberSubmissionNormalizer;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SubmittedPhoneNumberTest extends TestCase
{
    public function test_rule_accepts_local_phone_numbers(): void
    {
        $validator = Validator::make(
            ['phone_number' => '011-222 3333'],
            ['phone_number' => [new SubmittedPhoneNumber('+60')]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_rule_accepts_international_phone_numbers(): void
    {
        $validator = Validator::make(
            ['phone_number' => '+441632960960'],
            ['phone_number' => [new SubmittedPhoneNumber('+60')]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_rule_rejects_invalid_phone_numbers_with_shared_message(): void
    {
        $validator = Validator::make(
            ['phone_number' => 'abc'],
            ['phone_number' => [new SubmittedPhoneNumber('+60')]],
        );

        $this->assertFalse($validator->passes());
        $this->assertSame(
            (new PhoneNumberSubmissionNormalizer())->validationMessage(),
            $validator->errors()->first('phone_number'),
        );
    }
}
