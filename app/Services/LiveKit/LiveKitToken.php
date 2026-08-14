<?php

namespace App\Services\LiveKit;

class LiveKitToken
{
    public function __construct(
        public readonly string $url,
        public readonly string $token,
        public readonly string $roomName,
        public readonly string $identity,
        public readonly int $expiresIn,
    ) {}
}
