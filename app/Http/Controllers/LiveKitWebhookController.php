<?php

namespace App\Http\Controllers;

use App\Actions\Rooms\HandleLiveKitParticipantLeftAction;
use App\Services\LiveKit\InvalidLiveKitWebhookException;
use App\Services\LiveKit\LiveKitWebhookReceiver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LiveKitWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        LiveKitWebhookReceiver $receiver,
        HandleLiveKitParticipantLeftAction $handleParticipantLeft,
    ): Response {
        try {
            $event = $receiver->receive(
                $request->getContent(),
                $request->header('Authorization'),
            );
        } catch (InvalidLiveKitWebhookException) {
            abort(401, 'Invalid LiveKit webhook.');
        }

        $handleParticipantLeft->handle($event);

        return response()->noContent();
    }
}
