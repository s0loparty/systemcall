<?php

namespace App\Models;

use Database\Factories\RoomSubroomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property string $public_id
 * @property string $name
 * @property string $livekit_room_name
 * @property int $position
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'room_id',
    'public_id',
    'name',
    'livekit_room_name',
    'position',
    'is_default',
])]
class RoomSubroom extends Model
{
    /** @use HasFactory<RoomSubroomFactory> */
    use HasFactory;

    protected $attributes = [
        'position' => 0,
        'is_default' => false,
    ];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    /**
     * @return BelongsTo<Room, $this>
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * @return HasMany<RoomMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(RoomMember::class);
    }

    /**
     * @return HasMany<RoomMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(RoomMessage::class);
    }

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_default' => 'boolean',
        ];
    }
}
