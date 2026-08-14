<?php

namespace App\Support;

use Illuminate\Contracts\Cookie\QueueingFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GuestIdentity
{
    private const CookieName = 'video_call_guest_id';

    public function __construct(private QueueingFactory $cookies) {}

    public function getOrCreate(Request $request): string
    {
        $guestId = $this->current($request);

        if ($guestId !== null) {
            return $guestId;
        }

        $guestId = 'guest_'.Str::random(40);

        $this->cookies->queue(
            cookie(
                name: self::CookieName,
                value: $guestId,
                minutes: 60 * 24 * 365,
                path: null,
                domain: null,
                secure: null,
                httpOnly: true,
                raw: false,
                sameSite: 'lax',
            )
        );

        return $guestId;
    }

    public function current(Request $request): ?string
    {
        $guestId = $request->cookie(self::CookieName);

        if (is_string($guestId) && Str::startsWith($guestId, 'guest_')) {
            return $guestId;
        }

        return null;
    }
}
