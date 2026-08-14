<?php

namespace Database\Factories;

use App\Enums\RoomStatus;
use App\Enums\RoomVisibility;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'public_id' => Str::random(16),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->paragraph(),
            'visibility' => RoomVisibility::Private,
            'status' => RoomStatus::Active,
            'password_hash' => null,
            'waiting_room_enabled' => false,
            'max_participants' => fake()->optional()->numberBetween(2, 50),
            'ended_at' => null,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => RoomVisibility::Public,
        ]);
    }

    public function ended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RoomStatus::Ended,
            'ended_at' => now(),
        ]);
    }

    public function withWaitingRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'waiting_room_enabled' => true,
        ]);
    }
}
