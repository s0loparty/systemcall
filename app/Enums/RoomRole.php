<?php

namespace App\Enums;

enum RoomRole: string
{
    case Owner = 'owner';
    case Moderator = 'moderator';
    case Participant = 'participant';
}
