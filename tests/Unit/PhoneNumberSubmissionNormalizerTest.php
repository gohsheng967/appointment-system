<?php

namespace Tests\Unit;

use App\Support\PhoneNumberSubmissionNormalizer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhoneNumberSubmissionNormalizerTest extends TestCase
{
    public function test_normalize_converts_local_phone_number_using_selected_country_code(): void
    {
        $normalizer = new PhoneNumberSubmissionNormalizer();

        $normalized = $normalizer->normalize('+60', '011-222 3333');

        $this->assertSame('+60112223333', $normalized);
    }

    public function test_normalize_preserves_international_phone_number_input(): void
    {
        $normalizer = new PhoneNumberSubmissionNormalizer();

        $normalized = $normalizer->normalize('+60', '+441632960960');

        $this->assertSame('+441632960960', $normalized);
    }

    public function test_normalize_throws_for_invalid_local_phone_number(): void
    {
        $normalizer = new PhoneNumberSubmissionNormalizer();

        $this->expectException(ValidationException::class);

        $normalizer->normalize('+60', 'abc');
    }
}
