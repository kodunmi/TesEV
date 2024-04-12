<?php

namespace Database\Seeders;

use App\Models\TripSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TripSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TripSetting::truncate();

        try {
            TripSetting::create([
                'tax_percentage' => 15,
                'min_extension_time_buffer' => 20,
                'subscriber_price_per_hour' => 10
            ]);
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
}
