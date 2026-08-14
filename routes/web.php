<?php

use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomMembershipController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
Route::post('rooms/{room}/memberships', [RoomMembershipController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('rooms.memberships.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::patch('rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
});

require __DIR__.'/settings.php';
