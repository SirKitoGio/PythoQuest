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
        User::factory()->admin()->create([
            'name' => 'Keith Alan Speirs',
            'email' => 'keithalan.speirs25@gmail.com',
        ]);

        User::factory()->create([
            'name' => 'Demo Researcher',
            'email' => 'demo@phytoquest.test',
        ]);

        User::factory()->reviewer()->create([
            'name' => 'Demo Reviewer',
            'email' => 'reviewer@phytoquest.test',
        ]);

        User::factory(7)->create();
    }
}
