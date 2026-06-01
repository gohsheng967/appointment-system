<?php

namespace App\Http\Controllers;

use App\Domain\Appointments\Actions\CreateAppointmentAction;
use App\Domain\Customers\Actions\FindOrCreateCustomerAction;
use App\Http\Requests\PublicBookingRequest;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function create(): View
    {
        return view('booking.form', [
            'branches' => Branch::query()->orderBy('name')->get(),
            'services' => Service::query()->orderBy('name')->get(),
        ]);
    }

    public function store(
        PublicBookingRequest $request,
        FindOrCreateCustomerAction $findOrCreateCustomer,
        CreateAppointmentAction $createAppointmentAction,
    ): RedirectResponse {
        $customer = $findOrCreateCustomer([
            'name' => $request->string('name')->toString(),
            'email' => $request->input('email'),
            'phone_number' => $request->input('phone_number'),
        ]);

        $appointment = $createAppointmentAction([
            'branch_id' => (int) $request->input('branch_id'),
            'service_id' => (int) $request->input('service_id'),
            'customer_id' => $customer->id,
            'start_at' => (string) $request->input('start_at'),
        ]);

        return redirect()
            ->route('booking.success')
            ->with('booking_reference', $appointment->booking_reference);
    }

    public function success(): View|RedirectResponse
    {
        $reference = session('booking_reference');

        if (! $reference) {
            return redirect()->route('booking.create');
        }

        return view('booking.success', [
            'reference' => $reference,
        ]);
    }
}
