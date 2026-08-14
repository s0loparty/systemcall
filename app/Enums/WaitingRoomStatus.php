<?php

namespace App\Enums;

enum WaitingRoomStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
