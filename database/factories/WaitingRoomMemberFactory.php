<?php

namespace Database\Factories;

use App\Enums\WaitingRoomStatus;
use App\Models\Room;
use App\Models\User;
use App\Models\WaitingRoomMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WaitingRoomMember>
 */
class WaitingRoomMemberFactory extends Factory
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
            'user_id' => User::factory(),
            'guest_id' => null,
            'display_name' => fake()->name(),
            'status' => WaitingRoomStatus::Pending,
            'decided_by_user_id' => null,
            'decided_at' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_id' => 'guest:'.Str::random(24),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WaitingRoomStatus::Approved,
            'decided_by_user_id' => User::factory(),
            'decided_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WaitingRoomStatus::Rejected,
            'decided_by_user_id' => User::factory(),
            'decided_at' => now(),
        ]);
    }
}
