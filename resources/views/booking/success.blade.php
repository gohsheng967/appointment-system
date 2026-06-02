<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Successful</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gray-100 py-10">
<main class="mx-auto w-full max-w-xl rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm">
    <h1 class="text-2xl font-semibold text-gray-900">{{ $title }}</h1>
    <p class="mt-3 text-gray-700">
        {{ $message }}
    </p>
    <p class="mt-2 text-sm text-gray-600">
        Reference: <span class="font-semibold text-gray-900">{{ $reference }}</span>
    </p>
    <a href="{{ route('booking.create') }}"
       class="mt-6 inline-flex items-center rounded-lg bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
        Book another appointment
    </a>
</main>
</body>
</html>
