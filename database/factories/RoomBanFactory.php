<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomBan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomBan>
 */
class RoomBanFactory extends Factory
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
            'banned_by_user_id' => User::factory(),
            'reason' => fake()->optional()->sentence(),
            'expires_at' => null,
        ];
    }

    public function guest(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'guest_id' => 'guest:'.Str::random(24),
        ]);
    }

    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->addHour(),
        ]);
    }
}
