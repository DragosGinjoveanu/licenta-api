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

        // 1. Luăm primul user/ospătar și restaurantul lui pentru a asocia comenzile
        $user = User::first();
        if (!$user || !$user->restaurant_id) {
            $this->command->error('Nu s-a găsit niciun user cu restaurant_id. Te rog creează un user mai întâi!');
            return;
        }

        // 2. Luăm câteva produse din baza de date ca să le punem pe comenzi
        $products = Product::where('restaurant_id', $user->restaurant_id)->get();
        if ($products->count() === 0) {
            $this->command->error('Nu ai produse în meniu! Creează măcar 2-3 produse înainte să rulezi seeder-ul.');
            return;
        }

        $this->command->info('Generăm 150 de comenzi pentru grafice...');

        $orderTypes = ['dine_in', 'delivery', 'pickup'];
        $statuses = ['completed', 'completed', 'completed', 'cancelled']; // 75% șansă de completed, 25% cancelled

        for ($i = 0; $i < 150; $i++) {
            // Generăm o dată aleatorie în ultimele 30 de zile
            $randomDate = $faker->dateTimeBetween('-30 days', 'now');

            // Creăm Comanda
            $order = Order::create([
                'restaurant_id' => $user->restaurant_id,
                'user_id' => $user->id,
                'type' => $faker->randomElement($orderTypes),
                'status' => $faker->randomElement($statuses),
                'table_id' => $faker->numberBetween(1, 15),
                'customer_name' => $faker->name,
                'total_amount' => 0, // Îl calculăm mai jos
                'created_at' => $randomDate,
                'updated_at' => $randomDate,
            ]);

            $orderTotal = 0;
            // Câte produse are această comandă? (între 1 și 4)
            $numberOfItems = $faker->numberBetween(1, 4);

            for ($j = 0; $j < $numberOfItems; $j++) {
                $product = $products->random();
                $quantity = $faker->numberBetween(1, 3);
                $unitPrice = $product->price ?? $faker->randomFloat(2, 10, 50); // Prețul produsului

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

            // Updatăm totalul comenzii
            $order->update(['total_amount' => $orderTotal]);
        }

        $this->command->info('Gata! Au fost generate 150 de comenzi cu succes!');
    }
}