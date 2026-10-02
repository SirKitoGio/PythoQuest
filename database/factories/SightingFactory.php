<?php

namespace Database\Factories;

use App\Models\Plant;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sighting>
 */
class SightingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $observed = fake()->dateTimeBetween('-10 days', 'now');

        return [
            'plant_id' => Plant::inRandomOrder()->value('id') ?? Plant::factory(),
            'user_id' => User::inRandomOrder()->value('id') ?? User::factory(),
            // Around José Rizal University, Mandaluyong City.
            'latitude' => fake()->latitude(14.5800, 14.5940),
            'longitude' => fake()->longitude(121.0290, 121.0430),
            'observed_on' => $observed,
            'plant_part' => fake()->randomElement(Plant::PLANT_PARTS),
            'visibility' => fake()->randomElement(Sighting::VISIBILITIES),
            'status' => fake()->randomElement(Sighting::STATUSES),
            'location_name' => fake()->randomElement([
                'JRU Main Quadrangle', 'JRU Botanical Corner', 'Shaw Blvd. median strip',
                'Mandaluyong City Hall garden', 'Wack-Wack Road sidewalk', 'JRU Gymnasium grounds',
            ]),
            'photo_path' => null,
            'notes' => fake()->optional(0.8)->sentence(10),
            'created_at' => $observed,
            'updated_at' => $observed,
        ];
    }
}
