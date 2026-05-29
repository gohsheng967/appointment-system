<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 py-10">
<main class="mx-auto w-full max-w-3xl rounded-lg bg-white p-8 shadow-sm">
    <h1 class="text-2xl font-semibold text-slate-900">Public Booking Form</h1>
    <p class="mt-2 text-sm text-slate-600">Enter your contact details, choose a branch, service, and datetime.</p>

    @if ($errors->any())
        <div class="mt-6 rounded-md border border-red-300 bg-red-50 p-4 text-sm text-red-700">
            <p class="font-medium">Please fix the following issues:</p>
            <ul class="mt-2 list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('booking.store') }}" class="mt-6 grid gap-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-slate-700">Name</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" required
                   class="mt-1 w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}"
                       class="mt-1 w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700">Phone (+countrycode)</label>
                <input id="phone" name="phone" type="text" value="{{ old('phone') }}" placeholder="+60123456789"
                       class="mt-1 w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label for="branch_id" class="block text-sm font-medium text-slate-700">Branch</label>
                <select id="branch_id" name="branch_id" required
                        class="mt-1 w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Select branch</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id') == $branch->id)>
                            {{ $branch->name }} ({{ $branch->timezone }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="service_id" class="block text-sm font-medium text-slate-700">Service</label>
                <select id="service_id" name="service_id" required
                        class="mt-1 w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
                    <option value="">Select service</option>
                    @foreach ($services as $service)
                        <option value="{{ $service->id }}" @selected(old('service_id') == $service->id)>
                            {{ $service->name }} ({{ $service->duration_minutes }} mins)
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label for="start_at" class="block text-sm font-medium text-slate-700">Appointment datetime (branch local)</label>
            <input id="start_at" name="start_at" type="datetime-local" value="{{ old('start_at') }}" required
                   class="mt-1 w-full rounded-md border-slate-300 focus:border-slate-500 focus:ring-slate-500">
        </div>

        <button type="submit"
                class="mt-2 inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
            Submit Booking
        </button>
    </form>
</main>
</body>
</html>
