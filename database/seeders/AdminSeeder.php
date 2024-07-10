<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;


class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Admin::truncate();
        try {
            Admin::create([
                'first_name' => fake()->name(),
                'last_name' => fake()->name(),
                'email' => "admin@tesev.com",
                'password' =>  Hash::make('password'),
                'public_id' => uuid()
            ]);
        } catch (\Throwable $th) {
            dd($th->getMessage());
        }
    }
}
