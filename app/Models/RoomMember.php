<?php

namespace App\Models;

use App\Enums\RoomRole;
use Database\Factories\RoomMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $room_id
 * @property int|null $room_subroom_id
 * @property int|null $user_id
 * @property string|null $guest_id
 * @property string $display_name
 * @property RoomRole $role
 * @property string $livekit_identity
 * @property bool $can_publish_audio
 * @property bool $can_publish_video
 * @property bool $can_publish_screen
 * @property Carbon|null $hand_raised_at
 * @property Carbon|null $joined_at
 * @property Carbon|null $left_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'room_id',
    'room_subroom_id',
    'user_id',
    'guest_id',
    'display_name',
    'role',
    'livekit_identity',
    'can_publish_audio',
    'can_publish_video',
    'can_publish_screen',
    'hand_raised_at',
    'joined_at',
    'left_at',
])]
class RoomMember extends Model
{
    /** @use HasFactory<RoomMemberFactory> */
    use HasFactory;

    protected $attributes = [
        'role' => 'participant',
        'can_publish_audio' => true,
        'can_publish_video' => true,
        'can_publish_screen' => true,
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<RoomMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(RoomMessage::class);
    }

    public function isModerator(): bool
    {
        return in_array($this->role, [RoomRole::Owner, RoomRole::Moderator], true);
    }

    protected function casts(): array
    {
        return [
            'role' => RoomRole::class,
            'can_publish_audio' => 'boolean',
            'can_publish_video' => 'boolean',
            'can_publish_screen' => 'boolean',
            'hand_raised_at' => 'datetime',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }
}
