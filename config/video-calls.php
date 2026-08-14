<?php

return [
    'waiting_room' => [
        'stale_minutes' => (int) env('VIDEO_CALL_WAITING_ROOM_STALE_MINUTES', 30),
    ],
];
