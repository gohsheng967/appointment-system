<?php

namespace App\Domain\Customers\Services;

use App\Models\Customer;
use Illuminate\Validation\ValidationException;

class CustomerIdentityUniquenessValidator
{
    public function normalizeEmail(mixed $value): ?string
    {
        $normalized = strtolower(trim((string) $value));

        return $normalized !== '' ? $normalized : null;
    }

    public function assertAtLeastOneContact(?string $email, ?string $phoneNumber): void
    {
        if ($email || $phoneNumber) {
            return;
        }

        throw ValidationException::withMessages([
            'email' => ['Either email or phone number is required.'],
            'phone_number' => ['Either email or phone number is required.'],
        ]);
    }

    public function assertUniqueIdentity(
        ?string $email,
        ?string $phoneNumber,
        ?int $exceptCustomerId = null,
    ): void {
        if ($email && $phoneNumber) {
            $exists = Customer::query()
                ->where('email', $email)
                ->where('phone_number', $phoneNumber)
                ->when(
                    $exceptCustomerId !== null,
                    static fn ($query) => $query->whereKeyNot($exceptCustomerId),
                )
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => ['A customer with this email and phone number already exists.'],
                    'phone_number' => ['A customer with this email and phone number already exists.'],
                ]);
            }

            return;
        }

        if ($email) {
            $exists = Customer::query()
                ->where('email', $email)
                ->whereNull('phone_number')
                ->when(
                    $exceptCustomerId !== null,
                    static fn ($query) => $query->whereKeyNot($exceptCustomerId),
                )
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    'email' => ['A customer with this email already exists.'],
                ]);
            }

            return;
        }

        if (! $phoneNumber) {
            return;
        }

        $exists = Customer::query()
            ->whereNull('email')
            ->where('phone_number', $phoneNumber)
            ->when(
                $exceptCustomerId !== null,
                static fn ($query) => $query->whereKeyNot($exceptCustomerId),
            )
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'phone_number' => ['A customer with this phone number already exists.'],
            ]);
        }
    }
}
