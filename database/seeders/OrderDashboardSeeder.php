<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Faker\Factory as Faker;
use Carbon\Carbon;

class OrderDashboardSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $user = User::first();
        if (!$user || !$user->restaurant_id) {
            $this->command->error('Nu s-a găsit niciun user cu restaurant_id. Te rog creează un user mai întâi!');
            return;
        }

        $products = Product::where('restaurant_id', $user->restaurant_id)->get();
        if ($products->count() === 0) {
            $this->command->error('Nu ai produse în meniu! Creează măcar 2-3 produse înainte să rulezi seeder-ul.');
            return;
        }

        $this->command->info('Generăm 150 de comenzi pentru grafice...');

        $orderTypes = ['dine_in', 'delivery', 'pickup'];
        $statuses = ['completed', 'completed', 'completed', 'cancelled']; 

        for ($i = 0; $i < 150; $i++) {
            $randomDate = $faker->dateTimeBetween('-30 days', 'now');

            $order = Order::create([
                'restaurant_id' => $user->restaurant_id,
                'user_id' => $user->id,
                'type' => $faker->randomElement($orderTypes),
                'status' => $faker->randomElement($statuses),
                'table_id' => $faker->numberBetween(1, 15),
                'customer_name' => $faker->name,
                'total_amount' => 0, 
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);

            $orderTotal = 0;
            $numberOfItems = $faker->numberBetween(1, 4);

            for ($j = 0; $j < $numberOfItems; $j++) {
                $product = $products->random();
                $quantity = $faker->numberBetween(1, 3);
                $unitPrice = $product->price ?? $faker->randomFloat(2, 10, 50); 

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'created_at' => $randomDate,
                    'updated_at' => $randomDate,
                ]);

                $orderTotal += ($quantity * $unitPrice);
            }

            $order->update(['total_amount' => $orderTotal]);
        }

        $this->command->info('Gata! Au fost generate 150 de comenzi cu succes!');
    }
}