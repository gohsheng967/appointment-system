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
            'email' => ['nullable', 'email', 'required_without:phone', 'max:255'],
            'phone' => ['nullable', 'string', 'required_without:email', 'regex:/^\+[1-9]\d{7,14}$/'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'start_at' => ['required', 'date_format:Y-m-d\TH:i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('phone')) {
            $this->merge([
                'phone' => normalize_phone_number($this->input('phone')),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Phone must be in international format (E.164), for example +60123456789.',
            'email.required_without' => 'Either email or phone is required.',
            'phone.required_without' => 'Either email or phone is required.',
        ];
    }
}
