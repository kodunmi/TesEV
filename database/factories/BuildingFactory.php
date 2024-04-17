<?php

namespace Database\Factories;

use App\Models\Building;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Building>
 */
class BuildingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(), // The name of the building
            'public_id' => uuid(), // The public identifier of the building
            'address' => fake()->address(), // The address of the building
            'opening_time' => fake()->time(), // The opening time of the building
            'closing_time' => fake()->time(), // The closing time of the building
            'status' => fake()->randomElement(['active', 'inactive']), // The status of the building (e.g., active, inactive)
            'image' => uuid(), // The image URL or path of the building
            'code' => generateCode(6)
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Building $building) {
            // ...
        })->afterCreating(function (Building $building) {

            Vehicle::factory()->count(5)->for($building, 'building')->create();
        });
    }
}
