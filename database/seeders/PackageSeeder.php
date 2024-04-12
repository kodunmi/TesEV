<?php

namespace Database\Seeders;

use App\Enum\SubscriptionPaymentFrequencyEnum;
use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Package::truncate();
        $services = [
            [
                'title' => '40 Hours',
                'description' => '40 hours per month at 300',
                'amount' => 300,
                'hours' => 40,
                'frequency' => SubscriptionPaymentFrequencyEnum::MONTHLY->value,
                'status' => 'active',
                'active' => true,
                'public_id' => uuid(),
            ],
            [
                'title' => '20 Hours',
                'description' => '40 hours per month at 150',
                'amount' => 150,
                'hours' => 20,
                'frequency' => SubscriptionPaymentFrequencyEnum::MONTHLY->value,
                'status' => 'active',
                'active' => true,
                'public_id' => uuid(),
            ],
        ];

        try {
            foreach ($services as $service) {
                Package::create($service);
            }
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
}
