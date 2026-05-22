<?php

namespace App\Http\Controllers\Restaurant;

use Illuminate\Http\Request;
use App\Models\Restaurant;
use App\Http\Controllers\Controller;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->restaurant_id !== null) {
            return response()->json([
                'message' => 'You already have a restaurant associated with your account.'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|min:2|max:50',
            'cui'  => 'required|string|min:2|max:20|unique:restaurants,cui',
        ]);

        $restaurant = Restaurant::create($validated);

        $request->user()->update([
            'restaurant_id' => $restaurant->id
        ]);

        return response()->json([
            'message' => 'Restaurant created successfully!',
            'restaurant' => $restaurant
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $restaurant = Restaurant::find($request->user()->restaurant_id);

        if (!$restaurant) {
            return response()->json(['message' => 'Restaurant not found'], 404);
        }

        return response()->json($restaurant);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
