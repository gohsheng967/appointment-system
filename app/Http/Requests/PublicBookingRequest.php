<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PublicBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'required_without:phone_number', 'max:255'],
            'phone_country_code' => ['nullable', 'in:+60'],
            'phone_number' => ['nullable', 'string', 'required_without:email', 'regex:/^\+[1-9]\d{7,14}$/'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'start_at' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawPhoneNumber = trim((string) $this->input('phone_number', ''));
        $countryCode = (string) $this->input('phone_country_code', '+60');
        $normalizedPhoneNumber = null;

        if ($rawPhoneNumber !== '') {
            if (str_starts_with($rawPhoneNumber, '+')) {
                $normalizedPhoneNumber = normalize_phone_number($rawPhoneNumber);
            } else {
                $localPhoneNumber = preg_replace('/\D+/', '', $rawPhoneNumber);

                if ($localPhoneNumber !== '') {
                    $normalizedPhoneNumber = normalize_phone_number($countryCode.$localPhoneNumber);
                }
            }
        }

        if ($normalizedPhoneNumber !== null) {
            $this->merge([
                'phone_number' => $normalizedPhoneNumber,
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone_number.regex' => 'Phone number must be in international format (E.164), for example +60123456789.',
            'email.required_without' => 'Either email or phone number is required.',
            'phone_number.required_without' => 'Either email or phone number is required.',
            'start_at.after_or_equal' => 'Appointment date cannot be earlier than today.',
        ];
    }
}
