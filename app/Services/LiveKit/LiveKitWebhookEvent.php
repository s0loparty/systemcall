<?php

namespace App\Services\LiveKit;

class LiveKitWebhookEvent
{
    /**
     * @param  array<string, mixed>  $participantMetadata
     */
    public function __construct(
        public readonly string $event,
        public readonly ?string $roomName,
        public readonly ?string $participantIdentity,
        public readonly array $participantMetadata = [],
    ) {}
}
