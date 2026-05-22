<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    
    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $validated = $request->validate([
            'type' => 'required|in:dine_in,delivery,pickup',
            'table_id' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'delivery_address' => 'nullable|string',
            'scheduled_for' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $order = DB::transaction(function () use ($validated, $restaurantId, $request) {
                
                foreach ($validated['items'] as $itemData) {
                    $product = Product::with('ingredients')->findOrFail($itemData['product_id']);
                    $orderQty = $itemData['quantity'];

                    foreach ($product->ingredients as $ingredient) {
                        $requiredAmount = $ingredient->pivot->quantity_required * $orderQty;
                        
                        if ($ingredient->stock_quantity < $requiredAmount) {
                            throw new \Exception("Not enough stock for {$product->name}. Missing ingredient: {$ingredient->name}");
                        }
                    }
                }

                $order = Order::create([
                    'restaurant_id' => $restaurantId,
                    'user_id' => $request->user()->id,
                    'type' => $validated['type'],
                    'status' => 'pending',
                    'table_id' => $validated['table_id'] ?? null,
                    'customer_name' => $validated['customer_name'] ?? null,
                    'customer_phone' => $validated['customer_phone'] ?? null,
                    'delivery_address' => $validated['delivery_address'] ?? null,
                    'scheduled_for' => $validated['scheduled_for'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'total_amount' => 0,
                ]);

                $totalAmount = 0;
                
                foreach ($validated['items'] as $itemData) {
                    $product = Product::with('ingredients')->findOrFail($itemData['product_id']);
                    $subtotal = $product->price * $itemData['quantity'];
                    $totalAmount += $subtotal;

                    $order->items()->create([
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $product->price,
                    ]);

                    foreach ($product->ingredients as $ingredient) {
                        $requiredAmount = $ingredient->pivot->quantity_required * $itemData['quantity'];
                        $ingredient->decrement('stock_quantity', $requiredAmount);
                    }
                }

                $order->update(['total_amount' => $totalAmount]);
                return $order;
            });

            $order->load(['items.product', 'user']);
            
            broadcast(new OrderCreated($order))->toOthers();

            return response()->json([
                'message' => 'Order placed successfully',
                'order' => $order
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getActiveOrders(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['pending', 'cooking', 'ready', 'delivering'])
            ->with(['items.product', 'user'])
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($orders);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,cooking,ready,delivering,completed,cancelled',
        ]);

        $order = Order::with('items.product.ingredients')
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->findOrFail($id);

        $oldStatus = $order->status;
        $newStatus = $validated['status'];

        // 'pending' -> restock
        // 'cooking' or 'ready' -> waste
        if ($newStatus === 'cancelled' && $oldStatus === 'pending') {
            DB::transaction(function () use ($order) {
                foreach ($order->items as $item) {
                    if ($item->product) {
                        foreach ($item->product->ingredients as $ingredient) {
                            $restockAmount = $ingredient->pivot->quantity_required * $item->quantity;
                            $ingredient->increment('stock_quantity', $restockAmount);
                        }
                    }
                }
            });
        }

        $order->update(['status' => $newStatus]);
        
        $order->load(['items.product', 'user']);

        broadcast(new OrderUpdated($order))->toOthers();

        return response()->json([
            'message' => 'Order status updated to ' . $newStatus,
            'order' => $order
        ]);
    }

    public function getOrderHistory(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $orders = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['completed', 'cancelled'])
            ->with(['items.product', 'user'])
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($orders);
    }
}