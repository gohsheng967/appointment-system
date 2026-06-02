<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Customer Booking Rules
    |--------------------------------------------------------------------------
    |
    | Maximum number of ongoing bookings per customer. Ongoing means:
    | pending, confirmed, in_progress.
    |
    | Set to 0 or a negative number to disable this cap.
    |
    */
    'max_ongoing_bookings_per_customer' => env('BOOKING_MAX_ONGOING_PER_CUSTOMER', 1),
];

