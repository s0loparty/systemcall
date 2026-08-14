<?php

use App\Http\Controllers\LiveKitTokenController;
use App\Http\Controllers\RoomApplicationStateController;
use App\Http\Controllers\RoomBroadcastAuthController;
use App\Http\Controllers\RoomCallController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\RoomMembershipController;
use App\Http\Controllers\RoomMessageController;
use App\Http\Controllers\RoomModerationController;
use App\Http\Controllers\RoomSubroomController;
use App\Http\Controllers\RoomSubroomTokenController;
use App\Http\Controllers\WaitingRoomController;
use App\Http\Controllers\WaitingRoomDecisionController;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::match(['get', 'post'], 'broadcasting/room-auth', RoomBroadcastAuthController::class)
    ->withoutMiddleware(PreventRequestForgery::class);

Route::get('rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');
Route::get('rooms/{room}/call', [RoomCallController::class, 'show'])->name('rooms.call.show');
Route::post('rooms/{room}/memberships', [RoomMembershipController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('rooms.memberships.store');
Route::post('rooms/{room}/livekit-token', [LiveKitTokenController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('rooms.livekit_token.store');
Route::post('rooms/{room}/subroom-token', [RoomSubroomTokenController::class, 'store'])
    ->middleware('throttle:20,1')
    ->name('rooms.subroom_token.store');
Route::post('rooms/{room}/messages', [RoomMessageController::class, 'store'])
    ->middleware('throttle:room-chat')
    ->name('rooms.messages.store');
Route::patch('rooms/{room}/hand', [RoomApplicationStateController::class, 'updateHand'])
    ->middleware('throttle:room-state')
    ->name('rooms.hand.update');
Route::patch('rooms/{room}/global-pin', [RoomApplicationStateController::class, 'updateGlobalPin'])
    ->middleware('throttle:room-state')
    ->name('rooms.global_pin.update');
Route::get('rooms/{room}/waiting-room', [WaitingRoomController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('rooms.waiting_room.index');
Route::post('rooms/{room}/waiting-room/{waitingRoomMember}/approve', [WaitingRoomDecisionController::class, 'approve'])
    ->middleware(['auth', 'verified', 'throttle:60,1'])
    ->name('rooms.waiting_room.approve');
Route::post('rooms/{room}/waiting-room/{waitingRoomMember}/reject', [WaitingRoomDecisionController::class, 'reject'])
    ->middleware(['auth', 'verified', 'throttle:60,1'])
    ->name('rooms.waiting_room.reject');

Route::middleware(['auth', 'verified', 'throttle:60,1'])->group(function () {
    Route::patch('rooms/{room}/members/{roomMember}/role', [RoomModerationController::class, 'updateRole'])
        ->name('rooms.members.role.update');
    Route::post('rooms/{room}/members/{roomMember}/mute', [RoomModerationController::class, 'mute'])
        ->name('rooms.members.mute');
    Route::post('rooms/{room}/members/mute-all', [RoomModerationController::class, 'muteAll'])
        ->name('rooms.members.mute_all');
    Route::delete('rooms/{room}/members/{roomMember}', [RoomModerationController::class, 'kick'])
        ->name('rooms.members.kick');
    Route::post('rooms/{room}/members/{roomMember}/ban', [RoomModerationController::class, 'ban'])
        ->name('rooms.members.ban');
    Route::post('rooms/{room}/subrooms', [RoomSubroomController::class, 'store'])
        ->name('rooms.subrooms.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::post('rooms', [RoomController::class, 'store'])->name('rooms.store');
    Route::patch('rooms/{room}', [RoomController::class, 'update'])->name('rooms.update');
    Route::delete('rooms/{room}', [RoomController::class, 'destroy'])->name('rooms.destroy');
});

require __DIR__.'/settings.php';
