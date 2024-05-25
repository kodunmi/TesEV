<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\Card;
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
        User::factory()->createMany([
            [
                'first_name' => 'Lekan',
                'last_name' => 'Kodunmi',
                'email' => 'lekan126@gmail.com',
                'wallet' => 2000000000,
                'stripe_id' => 'cus_Q4cQPZXJ77cqV3'
            ],
            [
                'first_name' => 'Muyiwa',
                'last_name' => 'Emmanuel',
                'email' => 'emmanuelmuyiwa19@gmail.com',
                'wallet' => 2000000000,
                'stripe_id' => 'cus_Q4cQPZXJ77cqV3'
            ]
        ])->each(function (User $user) {
            $card =  Card::create([
                'stripe_id' => 'card_1PETlZP574Eunt6g7CtHM95I',
                'user_id' => $user->id,
                'last_four' => '4242',
                'is_default' => true,
                'is_active' => true,
                'public_id' => uuid(),
            ]);
        });

        Building::factory()->count(10)->create();


        Vehicle::factory()->count(40)->create();
    }
}
