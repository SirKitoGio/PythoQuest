<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Demo Researcher',
            'email' => 'demo@phytoquest.test',
        ]);

        User::factory(9)->create();
    }
}
