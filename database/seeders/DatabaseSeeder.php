<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\User;
use App\Models\Vehicle;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PackageSeeder::class,
            GeneralSeeder::class,
            TripSettingSeeder::class
        ]);
    }
}
