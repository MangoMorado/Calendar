<?php

namespace Database\Factories;

use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Calendar>
 */
class CalendarFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'description' => fake()->optional()->sentence(),
            'color' => fake()->hexColor(),
            'user_id' => User::factory(),
            'is_active' => true,
            'start_time' => '06:00',
            'end_time' => '19:00',
            'slot_duration' => 30,
            'time_format' => '12',
            'timezone' => 'America/Bogota',
            'business_days' => [1, 2, 3, 4, 5, 6],
            'visibility' => 'todos',
        ];
    }

    /**
     * Indicate that the calendar is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the calendar has no owner.
     */
    public function withoutOwner(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }
}
