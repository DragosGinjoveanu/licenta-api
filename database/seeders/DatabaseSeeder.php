<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Restaurant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $restaurant = Restaurant::create([
            'name' => 'Admin restaurant',
            'cui' => 'RO12345678'
        ]);

        User::factory()->create([
            'name' => 'Admin Manager',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
            'restaurant_id' => $restaurant->id,
        ]);
    }
}
