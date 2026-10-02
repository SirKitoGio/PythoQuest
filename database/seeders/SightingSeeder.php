<?php

namespace Database\Seeders;

use App\Models\Sighting;
use App\Models\User;
use Illuminate\Database\Seeder;

class SightingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Sighting::factory(15)->create();

        $demo = User::where('email', 'demo@phytoquest.test')->first();
        Sighting::factory(4)->create(['user_id' => $demo->id]);
    }
}
