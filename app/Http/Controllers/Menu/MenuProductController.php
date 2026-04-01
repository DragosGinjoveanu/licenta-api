<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\Product;
use App\Models\MenuProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuProductController extends Controller
{
    public function store(Request $request)
    {
        $restaurantId = Auth::user()->restaurant_id;

        $data = $request->validate([
            'menu_category_id' => 'required|exists:menu_categories,id',
            'product_id' => 'required|exists:products,id'
        ]);

        $menuCategory = MenuCategory::whereHas('menu', function ($q) use ($restaurantId) {
            $q->where('restaurant_id', $restaurantId);
        })->findOrFail($data['menu_category_id']);

        $product = Product::where('restaurant_id', $restaurantId)
            ->findOrFail($data['product_id']);

        $belongsToCategory = $product->categories()
            ->where('categories.id', $menuCategory->category_id)
            ->exists();

        if (!$belongsToCategory) {
            return response()->json([
                'error' => 'Product does not belong to this category in the catalog.'
            ], 422);
        }

        $lastOrder = MenuProduct::where('menu_category_id', $menuCategory->id)->max('order');
        $newOrder = is_null($lastOrder) ? 0 : $lastOrder + 1;

        $menuProduct = MenuProduct::create([
            'menu_category_id' => $menuCategory->id,
            'product_id' => $product->id,
            'order' => $newOrder,
            'show_price' => true,
            'show_description' => true,
            'show_ingredients' => true,
        ]);

        return response()->json($menuProduct->load('productDetails.ingredients'));
    }

    public function update(Request $request, MenuProduct $menuProduct)
    {
        $restaurantId = Auth::user()->restaurant_id;

        $isValid = $menuProduct->menuCategory()->whereHas('menu', function($q) use ($restaurantId) {
            $q->where('restaurant_id', $restaurantId);
        })->exists();

        if (!$isValid) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $data = $request->validate([
            'show_price' => 'sometimes|boolean',
            'show_description' => 'sometimes|boolean',
            'show_ingredients' => 'sometimes|boolean',
        ]);

        $menuProduct->update($data);

        return response()->json($menuProduct);
    }

    public function destroy(MenuProduct $menuProduct)
    {
        $restaurantId = Auth::user()->restaurant_id;

        $isValid = $menuProduct->menuCategory()->whereHas('menu', function($q) use ($restaurantId) {
            $q->where('restaurant_id', $restaurantId);
        })->exists();

        if (!$isValid) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        $menuProduct->delete();

        return response()->json(['message' => 'Product removed from menu successfully']);
    }

    public function reorder(Request $request)
    {
        $restaurantId = Auth::user()->restaurant_id;

        $request->validate([
            'menu_category_id' => 'required|exists:menu_categories,id',
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:menu_products,id',
            'orders.*.order' => 'required|integer',
        ]);

        $isValidCategory = MenuCategory::whereHas('menu', function ($q) use ($restaurantId) {
            $q->where('restaurant_id', $restaurantId);
        })->where('id', $request->menu_category_id)->exists();

        if (!$isValidCategory) {
            return response()->json(['message' => 'Unauthorized action.'], 403);
        }

        foreach ($request->orders as $item) {
            MenuProduct::where('id', $item['id'])
                       ->where('menu_category_id', $request->menu_category_id)
                       ->update(['order' => $item['order']]);
        }

        return response()->json(['message' => 'Products reordered']);
    }
}