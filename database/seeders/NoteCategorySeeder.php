<?php

namespace Database\Seeders;

use App\Models\NoteCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class NoteCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->first();
        if (! $user) {
            return;
        }

        $names = ['Personales', 'Sistema'];
        foreach ($names as $name) {
            NoteCategory::firstOrCreate(
                ['user_id' => $user->id, 'name' => $name]
            );
        }
    }
}
