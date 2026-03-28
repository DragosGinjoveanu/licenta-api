<?php

namespace App\Http\Controllers\Inventory;

use Illuminate\Validation\Rule;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Supplier;


class SupplierController extends Controller
{
    public function index(Request $request)
    {
        return Supplier::where('restaurant_id', $request->user()->restaurant_id)
            ->orderBy('name')
            ->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('suppliers')->where(function ($query) use ($request) {
                    return $query->where('restaurant_id', $request->user()->restaurant_id);
                })
            ],
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|regex:/^\+?[0-9\s\-]+$/|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        $supplier = Supplier::create([
            ...$validated,
            'restaurant_id' => $request->user()->restaurant_id,
        ]);

        return response()->json([
            'message' => 'Supplier added successfully!',
            'supplier' => $supplier
        ], 201);
    }

    public function update(Request $request, string $id)
    {
        $supplier = Supplier::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('suppliers')->where(function ($query) use ($request) {
                    return $query->where('restaurant_id', $request->user()->restaurant_id);
                })->ignore($supplier->id)
            ],
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|regex:/^\+?[0-9\s\-]+$/|max:20',
            'email' => 'nullable|email|max:100',
        ]);

        $supplier->update($validated);

        return response()->json([
            'message' => 'Supplier updated successfully!',
            'supplier' => $supplier
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $supplier = Supplier::where('id', $id)
            ->where('restaurant_id', $request->user()->restaurant_id)
            ->firstOrFail();

        $supplier->delete();

        return response()->json([
            'message' => 'Supplier removed successfully.'
        ]);
    }
}
