<?php

namespace App\Http\Controllers;

use App\Broadcasting\RoomApplicationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class RoomBroadcastAuthController extends Controller
{
    public function __invoke(Request $request, RoomApplicationChannel $channel): JsonResponse
    {
        $data = $request->validate([
            'socket_id' => ['required', 'string', 'regex:/^\d+\.\d+$/'],
            'channel_name' => ['required', 'string'],
        ]);

        if (! str_starts_with($data['channel_name'], 'private-room.')) {
            return response()->json(Broadcast::auth($request));
        }

        $roomPublicId = mb_substr($data['channel_name'], mb_strlen('private-room.'));

        if ($roomPublicId === '' || ! $channel->join($request->user(), $roomPublicId)) {
            throw new AccessDeniedHttpException;
        }

        return response()->json([
            'auth' => $this->signature($data['socket_id'], $data['channel_name']),
        ]);
    }

    private function signature(string $socketId, string $channelName): string
    {
        $key = config('broadcasting.connections.reverb.key');
        $secret = config('broadcasting.connections.reverb.secret');

        if (! is_string($key) || $key === '' || ! is_string($secret) || $secret === '') {
            throw ValidationException::withMessages([
                'channel_name' => __('Reverb is not configured.'),
            ]);
        }

        return $key.':'.hash_hmac('sha256', $socketId.':'.$channelName, $secret);
    }
}
