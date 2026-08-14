<?php

namespace App\Models;

use App\Enums\WaitingRoomStatus;
use Database\Factories\WaitingRoomMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property int|null $user_id
 * @property string|null $guest_id
 * @property string $display_name
 * @property WaitingRoomStatus $status
 * @property int|null $decided_by_user_id
 * @property Carbon|null $decided_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'room_id',
    'user_id',
    'guest_id',
    'display_name',
    'status',
    'decided_by_user_id',
    'decided_at',
])]
class WaitingRoomMember extends Model
{
    /** @use HasFactory<WaitingRoomMemberFactory> */
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    protected function casts(): array
    {
        return [
            'status' => WaitingRoomStatus::class,
            'decided_at' => 'datetime',
        ];
    }
}
