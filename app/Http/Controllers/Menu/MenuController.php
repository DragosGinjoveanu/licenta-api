<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
  public function index()
  {
      return Menu::with('menuCategories')
          ->where('restaurant_id', Auth::user()->restaurant_id)
          ->orderBy('created_at', 'desc')
          ->get();
  }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $menu = Menu::create([
            'restaurant_id' => Auth::user()->restaurant_id,
            'name' => $data['name'],
        ]);

        return response()->json($menu, 201);
    }

    public function show(Menu $menu)
    {
        if ($menu->restaurant_id !== Auth::user()->restaurant_id) {
            abort(403, 'Unauthorized action.');
        }

        return $menu->load([
            'menuCategories' => function($q) { $q->orderBy('order'); },
            'menuCategories.categoryDetails',
            'menuCategories.menuProducts' => function($q) { $q->orderBy('order'); },
            'menuCategories.menuProducts.productDetails.ingredients'
        ]);
    }

    public function update(Request $request, Menu $menu)
    {
        if ($menu->restaurant_id !== Auth::user()->restaurant_id) {
            abort(403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $menu->update($data);

        return response()->json($menu);
    }

    public function destroy(Menu $menu)
    {
        if ($menu->restaurant_id !== Auth::user()->restaurant_id) {
            abort(403);
        }

        $menu->delete();

        return response()->json(['message' => 'Menu deleted']);
    }
}