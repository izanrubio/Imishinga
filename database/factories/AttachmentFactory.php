<?php

namespace Database\Factories;

use App\Models\Attachment;
use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'card_id' => Card::factory(),
            'user_id' => User::factory(),
            'filename' => fake()->word().'.png',
            'path' => 'attachments/0/'.fake()->uuid().'.png',
            'mime_type' => 'image/png',
            'size' => fake()->numberBetween(1000, 500000),
        ];
    }
}
