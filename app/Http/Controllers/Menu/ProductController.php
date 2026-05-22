<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\MenuProduct;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        // 1. AICI AM MODIFICAT: Aducem și ingredientele (pentru a calcula stocul mai jos)
        $query = Product::where('restaurant_id', $restaurantId)->with(['categories', 'ingredients']);

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('category_ids')) {
            $categoryIds = $request->category_ids;
            
            if (is_array($categoryIds) && count($categoryIds) > 0) {
                $wantsUncategorized = in_array(0, $categoryIds) || in_array('0', $categoryIds);
                $realCategoryIds = array_diff($categoryIds, [0, '0']); 

                $query->where(function ($q) use ($wantsUncategorized, $realCategoryIds) {
                    if (count($realCategoryIds) > 0) {
                        $q->whereHas('categories', function ($subQ) use ($realCategoryIds) {
                            $subQ->whereIn('categories.id', $realCategoryIds);
                        });
                    }

                    if ($wantsUncategorized) {
                        if (count($realCategoryIds) > 0) {
                            $q->orDoesntHave('categories');
                        } else {
                            $q->doesntHave('categories');
                        }
                    }
                });
            }
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->has('is_available') && $request->is_available !== '') {
            $isAvailable = filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN);
            $query->where('is_available', $isAvailable);
        }

        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSortFields = ['name', 'price'];
        if (in_array($sortField, $allowedSortFields)) {
            $query->orderBy($sortField, $sortDirection === 'desc' ? 'desc' : 'asc');
        } else {
            $query->orderBy('name', 'asc');
        }

        // 2. AICI AM MODIFICAT: Executăm query-ul și calculăm `max_quantity` pentru fiecare produs
        $products = $query->get();

        $products->each(function ($product) {
            if ($product->ingredients->isEmpty()) {
                // Dacă produsul nu are nicio rețetă setată, presupunem stoc nelimitat
                $product->max_quantity = 999;
            } else {
                $maxQuantity = PHP_INT_MAX;

                foreach ($product->ingredients as $ingredient) {
                    if ($ingredient->pivot->quantity_required > 0) {
                        // Câte porții putem face din ACEST ingredient?
                        $possible = floor($ingredient->stock_quantity / $ingredient->pivot->quantity_required);
                        
                        // Reținem numărul minim dictat de cel mai limitat ingredient
                        if ($possible < $maxQuantity) {
                            $maxQuantity = $possible;
                        }
                    }
                }

                // Prevenim cantitățile negative
                $product->max_quantity = max(0, $maxQuantity);
            }
        });

        // 3. Returnăm colecția procesată
        return response()->json($products);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('products')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_available' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        $product = Product::create([
            'restaurant_id' => $restaurantId,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'is_available' => $validated['is_available'] ?? true,
        ]);

        if (isset($validated['category_ids'])) {
            $product->categories()->attach($validated['category_ids']);
        }

        return response()->json([
            'message' => 'Product created successfully!',
            'product' => $product->load('categories') 
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $product = Product::where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:150',
                Rule::unique('products')->where(fn ($q) => $q->where('restaurant_id', $restaurantId))->ignore($product->id)
            ],
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'is_available' => 'boolean',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id'
        ]);

        return DB::transaction(function () use ($product, $validated) {
            $product->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'price' => $validated['price'],
                'is_available' => $validated['is_available'] ?? true,
            ]);
        
            if (array_key_exists('category_ids', $validated)) {
                $product->categories()->sync($validated['category_ids']);
                
                // make sure to delete product from menus, if necessary
                $queryToDelete = MenuProduct::where('product_id', $product->id)
                    ->whereHas('menuCategory', function($query) use ($validated) {
                        $query->whereNotIn('category_id', $validated['category_ids'] ?? []);
                    });
        
                $deletedRows = $queryToDelete->delete();
            }
        
            return response()->json($product->load('categories'));
        });
    }

    public function destroy(Request $request, string $id)
    {
        $product = Product::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();

        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.'
        ]);
    }

    public function getRecipe(Request $request, string $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $product = Product::where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->with('ingredients') 
            ->firstOrFail();

        return response()->json($product);
    }

    public function updateRecipe(Request $request, string $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $product = Product::where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $validated = $request->validate([
            'ingredients' => 'nullable|array',
            'ingredients.*.ingredient_id' => 'required|exists:ingredients,id',
            'ingredients.*.quantity_required' => 'required|numeric|min:0',
        ]);

        $syncData = [];
        if (!empty($validated['ingredients'])) {
            foreach ($validated['ingredients'] as $item) {
                $syncData[$item['ingredient_id']] = [
                    'quantity_required' => $item['quantity_required']
                ];
            }
        }

        $product->ingredients()->sync($syncData);

        return response()->json([
            'message' => 'Recipe saved successfully!',
            'product' => $product->load('ingredients')
        ]);
    }
}