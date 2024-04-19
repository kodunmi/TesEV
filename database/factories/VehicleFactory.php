<?php

namespace Database\Factories;

use App\Models\Building;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Model Y', "Model X", 'Model Z']),
            'color' => fake()->colorName(),
            'status' => 'active',
            'price_per_hour' => fake()->randomElement([10, 20, 30]),
            'image' => fake()->randomElement([
                'https://res.cloudinary.com/bikievents/image/upload/v1712600661/image_8_poikne.png',
                'https://res.cloudinary.com/bikievents/image/upload/v1712600660/image_10_pcexwp.png',
                'https://res.cloudinary.com/bikievents/image/upload/v1712600660/image_9_haqnhv.png'
            ]),
            'plate_number' => generateRandomNumber(10),
            'building_id' => Building::all()->random(),
            'public_id' => uuid(),
        ];
    }
}
