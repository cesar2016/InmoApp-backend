<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use Illuminate\Http\Request;

class PropertyController extends Controller
{
    public function index()
    {
        return response()->json(Property::with(['owner', 'activeContract.tenant'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:Departamento,Casa,Local,Cochera,Oficina,Otro',
            'listing_type' => 'required|in:Alquiler,Venta,Ambos',
            'real_estate_id' => 'required|string',
            'domain' => 'required|string',
            'street' => 'required|string',
            'number' => 'required|string',
            'floor' => 'nullable|string',
            'dept' => 'nullable|string',
            'location' => 'required|string',
            'owner_id' => 'required|exists:owners,id',
        ]);

        $property = Property::create($validated);

        return response()->json($property, 201);
    }

    public function show(Property $property)
    {
        return response()->json($property->load(['owner', 'contracts', 'maintenances']));
    }

    public function update(Request $request, Property $property)
    {
        $validated = $request->validate([
            'type' => 'sometimes|in:Departamento,Casa,Local,Cochera,Oficina,Otro',
            'listing_type' => 'sometimes|in:Alquiler,Venta,Ambos',
            'real_estate_id' => 'sometimes|string',
            'domain' => 'sometimes|string',
            'street' => 'sometimes|string',
            'number' => 'sometimes|string',
            'floor' => 'nullable|string',
            'dept' => 'nullable|string',
            'location' => 'sometimes|string',
            'owner_id' => 'sometimes|exists:owners,id',
        ]);

        $property->update($validated);

        return response()->json($property);
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return response()->json(['message' => 'Property deleted']);
    }
}
