<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuickOrderTest extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = \App\Models\User::first();
        $product = \App\Models\Product::where('restaurant_id', $user->restaurant_id)->first();
    
        if (!$product) {
            $this->command->error("Nu am găsit niciun produs! Adaugă unul în meniu mai întâi.");
            return;
        }
    
        $orderController = new \App\Http\Controllers\Orders\OrderController();
        $request = new \Illuminate\Http\Request([
            'type' => 'dine_in',
            'table_id' => 'Masa 7',
            'items' => [
                ['product_id' => $product->id, 'quantity' => 2]
            ]
        ]);
    
        $request->setUserResolver(fn() => $user);
    
        $orderController->store($request);
        
        $this->command->info("🚀 Comandă de test trimisă cu succes!");
    }
}
