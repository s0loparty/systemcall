<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\RoomMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RoomMessage>
 */
class RoomMessageFactory extends Factory
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
            'room_member_id' => null,
            'type' => 'text',
            'body' => fake()->sentence(),
            'metadata' => null,
            'edited_at' => null,
            'deleted_at' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes) => [
            'room_member_id' => null,
            'type' => 'system',
        ]);
    }
}
