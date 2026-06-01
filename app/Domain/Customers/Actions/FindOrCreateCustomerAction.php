<?php

namespace App\Domain\Customers\Actions;

use App\Models\Customer;

class FindOrCreateCustomerAction
{
    public function __invoke(array $attributes): Customer
    {
        $email = isset($attributes['email']) ? trim((string) $attributes['email']) : null;
        $phoneNumber = normalize_phone_number($attributes['phone_number'] ?? null);

        if (! $email && ! $phoneNumber) {
            return Customer::create([
                'name' => $attributes['name'],
                'email' => null,
                'phone_number' => null,
            ]);
        }

        $query = Customer::query();

        if ($email) {
            $query->orWhere('email', $email);
        }

        if ($phoneNumber) {
            $query->orWhere('phone_number', $phoneNumber);
        }

        $customer = $query->first();

        if ($customer) {
            $customer->fill([
                'name' => $attributes['name'],
                'email' => $email ?: $customer->email,
                'phone_number' => $phoneNumber ?: $customer->phone_number,
            ])->save();

            return $customer;
        }

        return Customer::create([
            'name' => $attributes['name'],
            'email' => $email,
            'phone_number' => $phoneNumber,
        ]);
    }
}
