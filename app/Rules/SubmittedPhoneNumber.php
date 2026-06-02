<?php

namespace App\Rules;

use App\Support\PhoneNumberSubmissionNormalizer;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\ValidationException;

class SubmittedPhoneNumber implements ValidationRule
{
    private ?PhoneNumberSubmissionNormalizer $normalizer = null;

    public function __construct(
        private readonly string $countryCode,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $trimmedValue = trim((string) $value);

        if ($trimmedValue === '') {
            return;
        }

        $normalizer = $this->normalizer();

        try {
            $normalizedPhoneNumber = $normalizer->normalize($this->countryCode, $trimmedValue);
        } catch (ValidationException) {
            $fail($normalizer->validationMessage());

            return;
        }

        if ($normalizer->isValid($normalizedPhoneNumber)) {
            return;
        }

        $fail($normalizer->validationMessage());
    }

    private function normalizer(): PhoneNumberSubmissionNormalizer
    {
        return $this->normalizer ??= app(PhoneNumberSubmissionNormalizer::class);
    }
}
