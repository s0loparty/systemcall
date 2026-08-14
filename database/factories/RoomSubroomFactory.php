<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomSubroom;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RoomSubroom>
 */
class RoomSubroomFactory extends Factory
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
            'public_id' => Str::random(16),
            'name' => fake()->words(2, true),
            'livekit_room_name' => 'subroom:'.Str::uuid(),
            'position' => fake()->numberBetween(0, 4),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Main',
            'position' => 0,
            'is_default' => true,
        ]);
    }
}
