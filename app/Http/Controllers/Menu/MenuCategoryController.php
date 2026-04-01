<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuCategoryController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'menu_id' => 'required|exists:menus,id',
            'category_id' => 'required|exists:categories,id'
        ]);

        $restaurantId = $request->user()->restaurant_id;

        $menuExists = DB::table('menus')
            ->where('id', $data['menu_id'])
            ->where('restaurant_id', $restaurantId)
            ->exists();

        if (!$menuExists) {
            return response()->json(['message' => 'Unauthorized menu'], 403);
        }

        $alreadyExists = MenuCategory::where('menu_id', $data['menu_id'])
            ->where('category_id', $data['category_id'])
            ->exists();

        if ($alreadyExists) {
            return response()->json(['message' => 'Category already in this menu'], 422);
        }

        $lastOrder = MenuCategory::where('menu_id', $data['menu_id'])->max('order');
        $newOrder = is_null($lastOrder) ? 0 : $lastOrder + 1;

        $menuCategory = MenuCategory::create([
            'menu_id' => $data['menu_id'],
            'category_id' => $data['category_id'],
            'order' => $newOrder
        ]);

        $menuCategory->load('categoryDetails');

        return response()->json($menuCategory, 201);
    }

    public function reorder(Request $request)
    {
        $data = $request->validate([
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:menu_categories,id',
            'orders.*.order' => 'required|integer',
            'menu_id' => 'required|exists:menus,id'
        ]);

        $restaurantId = $request->user()->restaurant_id;

        $menuExists = DB::table('menus')
            ->where('id', $data['menu_id'])
            ->where('restaurant_id', $restaurantId)
            ->exists();

        if (!$menuExists) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::transaction(function () use ($data) {
            foreach ($data['orders'] as $item) {
                MenuCategory::where('id', $item['id'])
                    ->update(['order' => $item['order']]);
            }
        });

        return response()->json(['message' => 'Categories reordered successfully']);
    }

    public function destroy(MenuCategory $menuCategory, Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $menuExists = DB::table('menus')
            ->where('id', $menuCategory->menu_id)
            ->where('restaurant_id', $restaurantId)
            ->exists();

        if (!$menuExists) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $menuCategory->delete();

        return response()->json(['message' => 'Category removed from menu']);
    }
}