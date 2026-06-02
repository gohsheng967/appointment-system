<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 py-10">
<main class="mx-auto w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
    <div class="flex items-center gap-3">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">A</span>
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Public Booking Form</h1>
            <p class="mt-1 text-sm text-gray-600">Enter your contact details, choose a branch, service, and datetime.</p>
        </div>
    </div>

    @if ($errors->any())
        <div class="mt-6 rounded-lg border border-red-300 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-medium">{{ \App\Support\SubmissionFeedback::failureTitle() }}</p>
            <p class="mt-1">{{ \App\Support\SubmissionFeedback::failureIntro() }}</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('booking.store') }}" class="mt-6 grid gap-4" data-loading-form>
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
            </div>
            @php
                $phoneNumber = old('phone_number');

                if (is_string($phoneNumber) && str_starts_with($phoneNumber, '+60')) {
                    $phoneNumber = substr($phoneNumber, 3);
                }
            @endphp
            <div>
                <label for="phone_number" class="block text-sm font-medium text-gray-700">Phone Number</label>
                <div class="mt-1 flex gap-2">
                    <select id="phone_country_code" name="phone_country_code"
                            class="w-24 rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                        <option value="+60" @selected(old('phone_country_code', '+60') === '+60')>+60</option>
                    </select>
                    <input id="phone_number" name="phone_number" type="text" inputmode="numeric" autocomplete="tel-national" value="{{ $phoneNumber }}"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                </div>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="branch_id" class="block text-sm font-medium text-gray-700">Branch</label>
                <select id="branch_id" name="branch_id" required
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                    <option value="">Select branch</option>
                    @foreach ($branches as $branch)
                        @php
                            $offset = timezone_utc_offset_label($branch->timezone);
                        @endphp
                        <option value="{{ $branch->id }}" data-timezone="{{ $branch->timezone }}" data-offset="{{ $offset }}" @selected(old('branch_id') == $branch->id)>
                            {{ $branch->name }} ({{ $offset }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="service_id" class="block text-sm font-medium text-gray-700">Service</label>
                <select id="service_id" name="service_id" required
                        class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
                    <option value="">Select service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>
                            {{ $service->name }} ({{ $service->duration_minutes }} mins)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
            <h2 class="text-sm font-semibold text-amber-900">Service Price List</h2>
            <p class="mt-1 text-xs text-amber-800">Reference prices for available services.</p>
            <div class="mt-3 overflow-hidden rounded-lg border border-amber-200 bg-white">
                <table class="min-w-full divide-y divide-amber-100 text-sm">
                    <thead class="bg-amber-50">
                    <tr>
                        <th class="px-3 py-2 text-left font-medium text-amber-900">Service</th>
                        <th class="px-3 py-2 text-left font-medium text-amber-900">Duration</th>
                        <th class="px-3 py-2 text-right font-medium text-amber-900">Price</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-amber-100">
                    @foreach ($services as $service)
                        <tr>
                            <td class="px-3 py-2 text-gray-800">{{ $service->name }}</td>
                            <td class="px-3 py-2 text-gray-600">{{ $service->duration_minutes }} mins</td>
                            <td class="px-3 py-2 text-right font-medium text-gray-900">
                                {{ $service->price !== null ? '$'.number_format((float) $service->price, 2) : 'N/A' }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div>
            <label id="start_at_label" for="start_at" class="block text-sm font-medium text-gray-700">Appointment datetime (branch local)</label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ old('start_at') }}" min="{{ today()->format('Y-m-d') }}T00:00" required
                   class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-500/25">
            <p id="start_at_hint" class="mt-1 text-xs text-gray-500">
                Time shown in selected branch timezone.
            </p>
        </div>

        <button type="submit"
                class="mt-2 inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/30 disabled:cursor-not-allowed disabled:opacity-80"
                data-loading-button>
            <span data-loading-default>Submit Booking</span>
            <span class="hidden items-center gap-2" data-loading-active aria-live="polite">
                <x-loading-spinner
                    size="1rem"
                    track-color="rgba(255, 255, 255, 0.35)"
                    indicator-color="currentColor"
                    class="text-white"
                />
                <span>Submitting...</span>
            </span>
        </button>
    </form>
</main>
<script>
    (() => {
        const branchSelect = document.getElementById('branch_id');
        const label = document.getElementById('start_at_label');
        const hint = document.getElementById('start_at_hint');

        if (!branchSelect || !label || !hint) {
            return;
        }

        const updateTimezoneCopy = () => {
            const option = branchSelect.options[branchSelect.selectedIndex];
            const offset = option?.dataset?.offset || null;
            const timezone = option?.dataset?.timezone || null;

            if (!offset || !timezone) {
                label.textContent = 'Appointment datetime (branch local)';
                hint.textContent = 'Time shown in selected branch timezone.';
                return;
            }

            label.textContent = `Appointment datetime (${offset})`;
            hint.textContent = `Time shown in ${timezone} (${offset}).`;
        };

        branchSelect.addEventListener('change', updateTimezoneCopy);
        updateTimezoneCopy();
    })();
</script>
</body>
</html>
