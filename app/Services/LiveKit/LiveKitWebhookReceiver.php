<?php

namespace App\Services\LiveKit;

use Agence104\LiveKit\WebhookReceiver;
use Illuminate\Support\Facades\Config;
use JsonException;
use Throwable;

class LiveKitWebhookReceiver
{
    public function receive(string $body, ?string $authorizationHeader): LiveKitWebhookEvent
    {
        $apiKey = $this->configString('api_key');
        $apiSecret = $this->configString('api_secret');

        try {
            $receiver = new WebhookReceiver($apiKey, $apiSecret);
            $event = $receiver->receive($body, $authorizationHeader);
        } catch (Throwable $exception) {
            throw new InvalidLiveKitWebhookException(
                'LiveKit webhook signature or payload is invalid.',
                previous: $exception,
            );
        }

        $participant = $event->getParticipant();
        $metadata = [];

        if ($participant !== null && $participant->getMetadata() !== '') {
            try {
                $decoded = json_decode($participant->getMetadata(), true, flags: JSON_THROW_ON_ERROR);
                $metadata = is_array($decoded) ? $decoded : [];
            } catch (JsonException) {
                $metadata = [];
            }
        }

        return new LiveKitWebhookEvent(
            event: $event->getEvent(),
            roomName: $event->getRoom()?->getName(),
            participantIdentity: $participant?->getIdentity(),
            participantMetadata: $metadata,
        );
    }

    private function configString(string $key): string
    {
        $value = Config::get('services.livekit.'.$key);

        if (! is_string($value) || $value === '') {
            throw new \RuntimeException('LiveKit webhook configuration is incomplete.');
        }

        return $value;
    }
}
