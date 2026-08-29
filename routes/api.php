<?php

use App\Http\Controllers\Api\MobileRoomController;
use Illuminate\Support\Facades\Route;

Route::prefix('mobile')->group(function (): void {
    Route::get('rooms/{room}', [MobileRoomController::class, 'show'])
        ->middleware('throttle:60,1')
        ->name('api.mobile.rooms.show');

    Route::post('rooms/{room}/join', [MobileRoomController::class, 'join'])
        ->middleware('throttle:20,1')
        ->name('api.mobile.rooms.join');
});
