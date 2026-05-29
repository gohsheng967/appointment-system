<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/book');

Route::get('/book', [BookingController::class, 'create'])->name('booking.create');
Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
Route::get('/book/success', [BookingController::class, 'success'])->name('booking.success');
