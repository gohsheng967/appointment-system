<?php

namespace App\Domain\Customers\Actions;

use App\Domain\Customers\Services\CustomerIdentityUniquenessValidator;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class FindOrCreateCustomerAction
{
    public function __construct(
        private readonly CustomerIdentityUniquenessValidator $identityValidator,
    ) {}

    public function __invoke(array $attributes): Customer
    {
        $name = trim((string) $attributes['name']);
        $email = $this->identityValidator->normalizeEmail($attributes['email'] ?? null);
        $phoneNumber = normalize_phone_number($attributes['phone_number'] ?? null);

        if (! $email && ! $phoneNumber) {
            return Customer::query()->create([
                'name' => $name,
                'email' => null,
                'phone_number' => null,
            ]);
        }

        try {
            return DB::transaction(function () use ($name, $email, $phoneNumber): Customer {
                if ($email && $phoneNumber) {
                    $customerByEmail = Customer::query()
                        ->where('email', $email)
                        ->lockForUpdate()
                        ->first();
                    $customerByPhone = Customer::query()
                        ->where('phone_number', $phoneNumber)
                        ->lockForUpdate()
                        ->first();

                    if ($customerByEmail && $customerByPhone && $customerByEmail->is($customerByPhone)) {
                        return $this->updateCustomer(
                            $customerByEmail,
                            $name,
                            $email,
                            $phoneNumber,
                        );
                    }

                    return Customer::query()->create([
                        'name' => $name,
                        'email' => $email,
                        'phone_number' => $phoneNumber,
                    ]);
                }

                if ($email) {
                    $customerByEmail = Customer::query()
                        ->where('email', $email)
                        ->lockForUpdate()
                        ->first();

                    if ($customerByEmail) {
                        return $this->updateCustomer(
                            $customerByEmail,
                            $name,
                            $email,
                            $phoneNumber,
                        );
                    }
                }

                if ($phoneNumber) {
                    $customerByPhone = Customer::query()
                        ->where('phone_number', $phoneNumber)
                        ->lockForUpdate()
                        ->first();

                    if ($customerByPhone) {
                        return $this->updateCustomer(
                            $customerByPhone,
                            $name,
                            $email,
                            $phoneNumber,
                        );
                    }
                }

                return Customer::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'phone_number' => $phoneNumber,
                ]);
            }, 3);
        } catch (QueryException $exception) {
            $existing = $this->findExistingCustomer($email, $phoneNumber);

            if ($existing) {
                return $this->updateCustomer($existing, $name, $email, $phoneNumber);
            }

            throw $exception;
        }
    }

    private function findExistingCustomer(?string $email, ?string $phoneNumber): ?Customer
    {
        if ($email && $phoneNumber) {
            return Customer::query()
                ->where('email', $email)
                ->where('phone_number', $phoneNumber)
                ->first();
        }

        if ($email) {
            return Customer::query()
                ->where('email', $email)
                ->first();
        }

        if ($phoneNumber) {
            return Customer::query()
                ->where('phone_number', $phoneNumber)
                ->first();
        }

        return null;
    }

    private function updateCustomer(Customer $customer, string $name, ?string $email, ?string $phoneNumber): Customer
    {
        $customer->fill([
            'name' => $name,
            'email' => $email ?: $customer->email,
            'phone_number' => $phoneNumber ?: $customer->phone_number,
        ])->save();

        return $customer;
    }
}
