<?php

namespace Database\Factories;

use App\Models\MedicinalAction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MedicinalAction>
 */
class MedicinalActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'Antibacterial', 'Astringent', 'Diuretic', 'Expectorant', 'Febrifuge',
            'Antiinflammatory', 'Antispasmodic', 'Tonic', 'Vermifuge', 'Antiseptic',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }
}
