<?php

namespace Database\Seeders;

use App\Actions\Payment\StripeService;
use App\Enum\SubscriptionPaymentFrequencyEnum;
use App\Models\Package;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function __construct(protected StripeService $stripeService)
    {
    }

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
                'frequency' => SubscriptionPaymentFrequencyEnum::YEAR->value,
                'status' => 'active',
                'active' => true,
                'public_id' => uuid(),
            ],
            [
                'title' => '20 Hours',
                'description' => '40 hours per month at 150',
                'amount' => 150,
                'hours' => 20,
                'frequency' => SubscriptionPaymentFrequencyEnum::YEAR->value,
                'status' => 'active',
                'active' => true,
                'public_id' => uuid(),
            ],
        ];

        try {
            foreach ($services as $service) {
                $this->stripeService->createProduct(
                    name: $service['title'],
                    price: $service['amount'],
                    frequency: $service['frequency'],
                    description: $service['description'],
                    hours: $service['hours']
                );
            }
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
}
