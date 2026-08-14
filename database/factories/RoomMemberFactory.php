<?php

namespace Database\Factories;

use App\Enums\RoomRole;
use App\Models\Room;
use App\Models\RoomMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomMember>
 */
class RoomMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'room_id' => Room::factory(),
            'room_subroom_id' => null,
            'user_id' => User::factory(),
            'guest_id' => null,
            'display_name' => fake()->name(),
            'role' => RoomRole::Participant,
            'livekit_identity' => 'room-member:'.Str::uuid(),
            'can_publish_audio' => true,
            'can_publish_video' => true,
            'can_publish_screen' => true,
            'hand_raised_at' => null,
            'joined_at' => now(),
            'left_at' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoomRole::Owner,
        ]);
    }

    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => RoomRole::Moderator,
        ]);
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_id' => 'guest_'.Str::random(40),
        ]);
    }
}
