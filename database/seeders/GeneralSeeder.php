<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneralSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'first_name' => 'Lekan',
            'last_name' => 'Kodunmi',
            'email' => fake()->email(),
        ]);

        Building::factory()->count(10)->create();


        Vehicle::factory()->count(40)->create();
    }
}
