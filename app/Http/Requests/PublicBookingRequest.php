<?php

namespace App\Http\Requests;

use App\Rules\SubmittedPhoneNumber;
use App\Support\CustomerPhoneNumberFormState;
use App\Support\PhoneNumberSubmissionNormalizer;
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
            'phone_country_code' => ['nullable', 'in:'.CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE],
            'phone_number' => [
                'nullable',
                'string',
                'required_without:email',
                new SubmittedPhoneNumber(
                    (string) $this->input('phone_country_code', CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE),
                ),
            ],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'start_at' => ['required', 'date_format:Y-m-d\TH:i', 'after_or_equal:today'],
        ];
    }

    protected function passedValidation(): void
    {
        $this->merge([
            'phone_number' => app(PhoneNumberSubmissionNormalizer::class)->normalize(
                (string) $this->input('phone_country_code', CustomerPhoneNumberFormState::DEFAULT_COUNTRY_CODE),
                $this->input('phone_number'),
            ),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required_without' => 'Either email or phone number is required.',
            'phone_number.required_without' => 'Either email or phone number is required.',
            'start_at.after_or_equal' => 'Appointment date cannot be earlier than today.',
        ];
    }
}
