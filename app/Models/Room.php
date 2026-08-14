<?php

namespace App\Models;

use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use Database\Factories\RoomFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $public_id
 * @property string $name
 * @property string|null $description
 * @property RoomVisibility $visibility
 * @property RoomStatus $status
 * @property string|null $password_hash
 * @property bool $waiting_room_enabled
 * @property int|null $max_participants
 * @property Carbon|null $ended_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'owner_id',
    'public_id',
    'name',
    'description',
    'visibility',
    'status',
    'password_hash',
    'waiting_room_enabled',
    'max_participants',
    'ended_at',
])]
class Room extends Model
{
    /** @use HasFactory<RoomFactory> */
    use HasFactory;

    protected $attributes = [
        'visibility' => 'private',
        'status' => 'active',
        'waiting_room_enabled' => false,
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<RoomMember, $this>
     */
    public function members(): HasMany
    {
        return $this->hasMany(RoomMember::class);
    }

    /**
     * @return HasMany<RoomBan, $this>
     */
    public function bans(): HasMany
    {
        return $this->hasMany(RoomBan::class);
    }

    /**
     * @return HasMany<WaitingRoomMember, $this>
     */
    public function waitingMembers(): HasMany
    {
        return $this->hasMany(WaitingRoomMember::class);
    }

    /**
     * @return HasMany<RoomMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(RoomMessage::class);
    }

    /**
     * @return HasMany<RoomSubroom, $this>
     */
    public function subrooms(): HasMany
    {
        return $this->hasMany(RoomSubroom::class);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id === $user->id;
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected function casts(): array
    {
        return [
            'visibility' => RoomVisibility::class,
            'status' => RoomStatus::class,
            'waiting_room_enabled' => 'boolean',
            'max_participants' => 'integer',
            'ended_at' => 'datetime',
        ];
    }
}
