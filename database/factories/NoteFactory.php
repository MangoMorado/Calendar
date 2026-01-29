<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'visibility' => 'solo_yo',
            'share_token' => null,
            'user_id' => User::factory(),
            'note_category_id' => null,
        ];
    }

    /**
     * Indicate that the note is visible to all users.
     */
    public function visibleToAll(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'todos',
        ]);
    }

    /**
     * Indicate that the note has a share token.
     */
    public function withShareToken(): static
    {
        return $this->state(fn (array $attributes) => [
            'share_token' => \Illuminate\Support\Str::random(32),
        ]);
    }
}
