<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Restaurant\RestaurantController;

use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ProductController;

use App\Http\Controllers\Menu\MenuController;
use App\Http\Controllers\Menu\MenuProductController;
use App\Http\Controllers\Menu\MenuCategoryController;

use App\Http\Controllers\Inventory\SupplierController;
use App\Http\Controllers\Inventory\IngredientController;

use App\Http\Controllers\Orders\OrderController;

use App\Http\Controllers\Dashboard\DashboardController;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::post('/restaurants', [RestaurantController::class, 'store']);
    
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::put('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);
    Route::get('/categories/{id}', [CategoryController::class, 'show']);
    Route::delete('/categories/{id}/products/{productId}', [CategoryController::class, 'detachProduct']);
    Route::get('/categories/{id}/available-products', [CategoryController::class, 'getAvailableProducts']);
    Route::post('/categories/{id}/attach-products', [CategoryController::class, 'attachProducts']);

    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    Route::get('products/{id}/recipe', [ProductController::class, 'getRecipe']);
    Route::put('products/{id}/recipe', [ProductController::class, 'updateRecipe']);

    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    Route::get('/ingredients', [IngredientController::class, 'index']);
    Route::post('/ingredients', [IngredientController::class, 'store']);
    Route::put('/ingredients/{id}', [IngredientController::class, 'update']);
    Route::delete('/ingredients/{id}', [IngredientController::class, 'destroy']);

    Route::get('/menus', [MenuController::class, 'index']);
    Route::post('/menus', [MenuController::class, 'store']);
    Route::get('/menus/{menu}', [MenuController::class, 'show']);
    Route::put('/menus/{menu}', [MenuController::class, 'update']);
    Route::delete('/menus/{menu}', [MenuController::class, 'destroy']);
    //Route::get('/menus/{menu}/export', [MenuController::class, 'exportPdf']);

    Route::put('/menu-categories/reorder', [MenuCategoryController::class, 'reorder']);
    Route::post('/menu-categories', [MenuCategoryController::class, 'store']);
    Route::delete('/menu-categories/{menuCategory}', [MenuCategoryController::class, 'destroy']);

    Route::put('/menu-products/reorder', [MenuProductController::class, 'reorder']);
    Route::post('/menu-products', [MenuProductController::class, 'store']);
    Route::put('/menu-products/{menuProduct}', [MenuProductController::class, 'update']);
    Route::delete('/menu-products/{menuProduct}', [MenuProductController::class, 'destroy']);

    Route::get('/orders/history', [OrderController::class, 'getOrderHistory']);
    Route::get('/orders/active', [OrderController::class, 'getActiveOrders']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::post('/orders', [OrderController::class, 'store']);

    Route::get('/dashboard/stats', [DashboardController::class, 'getStats']);
});