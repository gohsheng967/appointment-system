<?php

namespace App\Domain\Appointments\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use Illuminate\Validation\ValidationException;

class CustomerOngoingBookingLimitService
{
    public function assertCustomerCanHaveAnotherOngoingBooking(
        int $customerId,
        ?int $exceptAppointmentId = null,
    ): void {
        $limit = $this->maxOngoingBookingsPerCustomer();

        if ($limit <= 0) {
            return;
        }

        $ongoingCount = Appointment::query()
            ->where('customer_id', $customerId)
            ->whereIn(
                'status',
                array_map(
                    static fn (AppointmentStatus $status): string => $status->value,
                    AppointmentStatus::ongoingStatuses(),
                ),
            )
            ->when(
                $exceptAppointmentId !== null,
                static fn ($query) => $query->whereKeyNot($exceptAppointmentId),
            )
            ->count();

        if ($ongoingCount >= $limit) {
            throw ValidationException::withMessages([
                'customer_id' => [
                    'Customer already has the maximum number of ongoing bookings (pending, confirmed, in progress).',
                ],
            ]);
        }
    }

    private function maxOngoingBookingsPerCustomer(): int
    {
        return (int) config('booking.max_ongoing_bookings_per_customer', 1);
    }
}

