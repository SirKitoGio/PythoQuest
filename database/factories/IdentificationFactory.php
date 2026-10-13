<?php

namespace Database\Factories;

use App\Models\Identification;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Identification>
 */
class IdentificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'image_path' => 'identifications/sample.jpg',
            'plant_part' => fake()->randomElement(Plant::PLANT_PARTS),
            'status' => 'queued',
        ];
    }

    /**
     * Indicate that the model has returned ranked candidates for the given plant.
     */
    public function completed(Plant $plant, float $confidence = 0.82): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'model_version' => 'mock-1',
            'candidates' => [[
                'plant_id' => $plant->id,
                'scientific_name' => $plant->scientific_name,
                'common_name' => $plant->common_name,
                'score' => $confidence,
            ]],
            'top_plant_id' => $plant->id,
            'top_confidence' => $confidence,
            'needs_more_images' => $confidence < config('services.ml.low_confidence_threshold'),
        ]);
    }
}
