<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ReviewController;
use Illuminate\Support\Facades\Route;

// Публичные роуты
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Защищенные роуты
Route::middleware('auth:api')->group(function () {
    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/me', [AuthController::class, 'me']);

    // Rooms
    Route::apiResource('rooms', RoomController::class);
    Route::get('/rooms/{room}/schedule', [RoomController::class, 'schedule']);
    Route::get('/search/rooms', [RoomController::class, 'search']);

    // Bookings
    Route::apiResource('bookings', BookingController::class)->except(['update']);
    Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);

    // Reviews
    Route::post('/reviews', [ReviewController::class, 'store']);
});