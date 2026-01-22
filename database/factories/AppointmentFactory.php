<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Calendar;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->dateTimeBetween('now', '+1 month');
        $endTime = (clone $startTime)->modify('+1 hour');

        return [
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'calendar_id' => Calendar::factory(),
            'user_id' => User::factory(),
            'all_day' => false,
            'color' => fake()->optional()->hexColor(),
        ];
    }

    /**
     * Indicate that the appointment is all day.
     */
    public function allDay(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = fake()->dateTimeBetween('now', '+1 month');
            $startTime->setTime(0, 0, 0);
            $endTime = (clone $startTime)->setTime(23, 59, 59);

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'all_day' => true,
            ];
        });
    }

    /**
     * Indicate that the appointment has no assigned user.
     */
    public function withoutUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
        ]);
    }

    /**
     * Indicate that the appointment is in the past.
     */
    public function past(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = fake()->dateTimeBetween('-1 month', 'now');
            $endTime = (clone $startTime)->modify('+1 hour');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }

    /**
     * Indicate that the appointment is in the future.
     */
    public function future(): static
    {
        return $this->state(function (array $attributes) {
            $startTime = fake()->dateTimeBetween('+1 day', '+1 month');
            $endTime = (clone $startTime)->modify('+1 hour');

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        });
    }
}
