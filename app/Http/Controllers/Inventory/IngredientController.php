<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IngredientController extends Controller
{
    public function index(Request $request)
    {
        return Ingredient::with('supplier')
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('ingredients')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })
            ],
            'supplier_id' => [
                'nullable',
                Rule::exists('suppliers', 'id')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })
            ],
            'unit_of_measure' => 'required|string|max:20',
            'stock_quantity' => 'required|numeric|min:0',
            'alert_level' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $ingredient = Ingredient::create([
            ...$validated,
            'restaurant_id' => $restaurantId,
        ]);

        $ingredient->load('supplier');

        return response()->json([
            'message' => 'Ingredient added successfully!',
            'ingredient' => $ingredient
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $ingredient = Ingredient::where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('ingredients')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })->ignore($ingredient->id)
            ],
            'supplier_id' => [
                'nullable', 
                Rule::exists('suppliers', 'id')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })
            ],
            'unit_of_measure' => 'required|string|max:20',
            'stock_quantity' => 'required|numeric|min:0',
            'alert_level' => 'nullable|numeric|min:0',
            'cost_per_unit' => 'required|numeric|min:0',
        ]);

        $ingredient->update($validated);
        
        $ingredient->load('supplier');

        return response()->json([
            'message' => 'Ingredient updated successfully!',
            'ingredient' => $ingredient
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $ingredient = Ingredient::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();

        $ingredient->delete();

        return response()->json([
            'message' => 'Ingredient removed successfully.'
        ]);
    }
}