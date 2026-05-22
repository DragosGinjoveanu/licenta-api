<?php

namespace App\Http\Controllers\Menu;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\MenuCategory;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $query = Category::where('restaurant_id', $restaurantId)
            ->withCount('products');

        if ($request->filled('search')) {
            $query->where('name', 'ilike', '%' . $request->search . '%');
        }

        $sort = $request->input('sort', 'name_asc');

        match ($sort) {
            'name_desc'     => $query->orderBy('name', 'desc'),
            'newest'        => $query->orderBy('created_at', 'desc'),
            'oldest'        => $query->orderBy('created_at', 'asc'),
            'products_asc'  => $query->orderBy('products_count', 'asc'),
            'products_desc' => $query->orderBy('products_count', 'desc'),
            default         => $query->orderBy('name', 'asc'),
        };

        return $query->get();
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })
            ]
        ]);

        $category = Category::create([
            'name' => $validated['name'],
            'restaurant_id' => $restaurantId,
        ]);

        return response()->json([
            'message' => 'Category created successfully!',
            'category' => $category
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $category = Category::where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('categories')->where(function ($query) use ($restaurantId) {
                    return $query->where('restaurant_id', $restaurantId);
                })->ignore($category->id)
            ]
        ]);

        $category->update($validated);

        return response()->json([
            'message' => 'Category updated successfully!',
            'category' => $category
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        $category = Category::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();

        return DB::transaction(function () use ($category) {
            MenuCategory::where('category_id', $category->id)->delete();
    
            $category->delete();
    
            return response()->json([
                'message' => 'Category deleted successfully from catalog and all active menus.'
            ]);
        });
    }

    public function show(Request $request, string $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $category = Category::where('id', $id)
            ->where('restaurant_id', $restaurantId)
            ->firstOrFail();

        $productsQuery = $category->products();
        
        if ($request->filled('search')) {
            $productsQuery->where('name', 'ilike', '%' . $request->search . '%');
        }

        if ($request->filled('min_price')) {
            $productsQuery->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
            $productsQuery->where('price', '<=', $request->max_price);
        }

        if ($request->has('is_available') && $request->is_available !== '') {
            $isAvailable = filter_var($request->is_available, FILTER_VALIDATE_BOOLEAN);
            $productsQuery->where('is_available', $isAvailable);
        }
        
        $sortField = $request->input('sort_field', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSortFields = ['name', 'price'];
        if (in_array($sortField, $allowedSortFields)) {
            $productsQuery->orderBy($sortField, strtolower($sortDirection) === 'desc' ? 'desc' : 'asc');
        } else {
            $productsQuery->orderBy('name', 'asc');
        }

        $category->setRelation('products', $productsQuery->get());

        return response()->json($category);
    }

    public function detachProduct(Request $request, $id, $productId)
    {
        $category = Category::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();
    
        $category->products()->detach($productId);
    
        return response()->json(['message' => 'Product removed from this category.']);
    }

    public function getAvailableProducts(Request $request, $id)
    {
        $restaurantId = $request->user()->restaurant_id;

        $products = Product::where('restaurant_id', $restaurantId)
            ->whereDoesntHave('categories', function ($query) use ($id) {
                $query->where('categories.id', $id);
            })
            ->with('categories')
            ->get();

        return response()->json($products);
    }

    public function attachProducts(Request $request, $id)
    {
        $category = Category::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        $category->products()->syncWithoutDetaching($validated['product_ids']);

        return response()->json(['message' => 'Products added to category!']);
    }
}