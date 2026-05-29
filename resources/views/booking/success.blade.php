<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-100 py-10">
<main class="mx-auto w-full max-w-xl rounded-lg bg-white p-8 shadow-sm text-center">
    <h1 class="text-2xl font-semibold text-slate-900">Booking Submitted</h1>
    <p class="mt-3 text-slate-700">
        Your appointment request has been received.
    </p>
    <p class="mt-2 text-sm text-slate-600">
        Reference: <span class="font-semibold text-slate-900">{{ $reference }}</span>
    </p>
    <a href="{{ route('booking.create') }}"
       class="mt-6 inline-flex items-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-700">
        Book another appointment
    </a>
</main>
</body>
</html>
