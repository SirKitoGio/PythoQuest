<?php

namespace Database\Seeders;

use App\Models\Plant;
use App\Models\Sighting;
use App\Models\SightingConfirmation;
use App\Models\User;
use Illuminate\Database\Seeder;

class SightingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pilotPlantIds = Plant::whereIn('scientific_name', array_column(PlantSeeder::PILOT_SPECIES, 1))->pluck('id');
        $plantIds = $pilotPlantIds->isNotEmpty() ? $pilotPlantIds : Plant::pluck('id');
        $reviewer = User::where('email', 'reviewer@phytoquest.test')->first();
        $demo = User::where('email', 'demo@phytoquest.test')->first();
        $researchers = User::where('role', 'researcher')->pluck('id');

        $sightings = Sighting::factory(15)
            ->sequence(fn () => [
                'plant_id' => $plantIds->random(),
                'user_id' => $researchers->random(),
                'status' => fake()->randomElement(['trusted', 'trusted', 'trusted', 'pending', 'pending', 'needs correction', 'rejected', 'sensitive-restricted']),
            ])
            ->create()
            ->merge(Sighting::factory(4)->sequence(fn () => ['plant_id' => $plantIds->random()])->create(['user_id' => $demo->id]));

        $sightings->reject(fn (Sighting $sighting) => $sighting->status === 'pending')
            ->each(fn (Sighting $sighting) => $sighting->forceFill([
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => $sighting->created_at->addDay(),
                'review_note' => $sighting->status === 'needs correction' ? 'Please add a clearer photo of the leaf arrangement.' : null,
            ])->save());

        $sightings->each(function (Sighting $sighting) use ($researchers) {
            $researchers->reject(fn ($id) => $id === $sighting->user_id)
                ->shuffle()
                ->take(fake()->numberBetween(0, 3))
                ->each(fn ($id) => SightingConfirmation::create(['sighting_id' => $sighting->id, 'user_id' => $id]));
        });
    }
}
