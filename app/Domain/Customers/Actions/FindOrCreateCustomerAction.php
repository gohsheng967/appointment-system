<?php

namespace App\Domain\Customers\Actions;

use App\Models\Customer;

class FindOrCreateCustomerAction
{
    public function __invoke(array $attributes): Customer
    {
        $email = isset($attributes['email']) ? trim((string) $attributes['email']) : null;
        $phone = normalize_phone_number($attributes['phone'] ?? null);

        if (! $email && ! $phone) {
            return Customer::create([
                'name' => $attributes['name'],
                'email' => null,
                'phone' => null,
            ]);
        }

        $query = Customer::query();

        if ($email) {
            $query->orWhere('email', $email);
        }

        if ($phone) {
            $query->orWhere('phone', $phone);
        }

        $customer = $query->first();

        if ($customer) {
            $customer->fill([
                'name' => $attributes['name'],
                'email' => $email ?: $customer->email,
                'phone' => $phone ?: $customer->phone,
            ])->save();

            return $customer;
        }

        return Customer::create([
            'name' => $attributes['name'],
            'email' => $email,
            'phone' => $phone,
        ]);
    }
}
