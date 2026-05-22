<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date')) 
            : Carbon::now()->subDays(30);
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date')) 
            : Carbon::now();

        $completedOrders = Order::with(['items.product.ingredients'])
            ->where('restaurant_id', $restaurantId)
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get(); 

        $totalRevenue = 0;
        $totalCost = 0;
        $trendsMap = [];
        $profitableProductsMap = [];

        foreach ($completedOrders as $order) {
            $totalRevenue += $order->total_amount;
            $date = $order->created_at->format('Y-m-d');
            
            if (!isset($trendsMap[$date])) {
                $trendsMap[$date] = [
                    'date' => $date, 
                    'total_revenue' => 0, 
                    'gross_profit' => 0
                ];
            }
            
            $trendsMap[$date]['total_revenue'] += $order->total_amount;
            
            $orderCost = 0;
            
            foreach ($order->items as $item) {
                $itemCost = 0;
                if ($item->product && $item->product->ingredients) {
                    foreach ($item->product->ingredients as $ingredient) {
                        $qty = $ingredient->pivot->quantity ?? 0;
                        $itemCost += ($qty * $ingredient->cost_per_unit);
                    }
                } 
                // DEMO
                if ($itemCost == 0) {
                    $itemCost = $item->unit_price * 0.30; 
                }
                
                $lineCost = $itemCost * $item->quantity;
                $orderCost += $lineCost;
                
                $lineRevenue = $item->unit_price * $item->quantity;
                $lineProfit = $lineRevenue - $lineCost;
                
                $pId = $item->product_id;
                if ($pId) {
                    if (!isset($profitableProductsMap[$pId])) {
                        $profitableProductsMap[$pId] = [
                            'name' => $item->product ? $item->product->name : 'Deleted',
                            'total_profit' => 0
                        ];
                    }
                    $profitableProductsMap[$pId]['total_profit'] += $lineProfit;
                }
            }
            
            $trendsMap[$date]['gross_profit'] += ($order->total_amount - $orderCost);
            $totalCost += $orderCost;
        }

        $profit = $totalRevenue - $totalCost;
        $totalOrdersCount = $completedOrders->count();
        $aov = $totalOrdersCount > 0 ? $totalRevenue / $totalOrdersCount : 0;

        $lostRevenue = (float) Order::where('restaurant_id', $restaurantId)
            ->where('status', 'cancelled')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total_amount');

        ksort($trendsMap);
        $revenueTrends = array_values($trendsMap);

        usort($profitableProductsMap, function($a, $b) {
            return $b['total_profit'] <=> $a['total_profit'];
        });
        $topProfitable = array_slice($profitableProductsMap, 0, 5);

        $topProducts = OrderItem::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->whereHas('order', function ($q) use ($restaurantId, $startDate, $endDate) {
                $q->where('restaurant_id', $restaurantId)
                  ->where('status', 'completed')
                  ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->with('product:id,name')
            ->groupBy('product_id')
            ->orderByRaw('SUM(quantity) DESC')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product ? $item->product->name : 'Deleted',
                    'total_sold' => (int) $item->total_sold
                ];
            });

        $salesMix = Order::where('restaurant_id', $restaurantId)
            ->whereIn('status', ['completed', 'delivering'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('type', DB::raw('COUNT(id) as count'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy('type')
            ->get()
            ->map(function ($item) {
                return [
                    'type' => $item->type,
                    'count' => (int) $item->count,
                    'revenue' => (float) $item->revenue,
                ];
            });

        return response()->json([
            'kpis' => [
                'total_revenue' => round($totalRevenue, 2),
                'gross_profit' => round($profit, 2),
                'total_orders' => $totalOrdersCount,
                'aov' => round($aov, 2),
                'lost_revenue' => round($lostRevenue, 2),
            ],
            'charts' => [
                'revenue_trends' => $revenueTrends,
                'sales_mix' => $salesMix,
                'top_products' => $topProducts,
                'top_profitable' => $topProfitable,
            ],
            'period' => [
                'start' => $startDate->toDateTimeString(),
                'end' => $endDate->toDateTimeString(),
            ]
        ]);
    }
}