<?php

namespace App\Models;

use Database\Factories\RoomMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property int|null $room_subroom_id
 * @property int|null $room_member_id
 * @property string $type
 * @property string $body
 * @property array<string, mixed>|null $metadata
 * @property Carbon|null $edited_at
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'room_id',
    'room_subroom_id',
    'room_member_id',
    'type',
    'body',
    'metadata',
    'edited_at',
    'deleted_at',
])]
class RoomMessage extends Model
{
    /** @use HasFactory<RoomMessageFactory> */
    use HasFactory;

    protected $attributes = [
        'type' => 'text',
    ];

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return BelongsTo<RoomSubroom, $this>
     */
    public function subroom(): BelongsTo
    {
        return $this->belongsTo(RoomSubroom::class, 'room_subroom_id');
    }

    /**
     * @return BelongsTo<RoomMember, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(RoomMember::class, 'room_member_id');
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'edited_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }
}
