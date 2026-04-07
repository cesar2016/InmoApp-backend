<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Maintenance;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function index()
    {
        return response()->json(Maintenance::with('property')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'description' => 'required|string',
            'cost' => 'required|numeric',
            'date' => 'required|date',
        ]);

        $maintenance = Maintenance::create($validated);

        return response()->json($maintenance, 201);
    }

    public function show(Maintenance $maintenance)
    {
        return response()->json($maintenance->load('property.owner'));
    }

    public function update(Request $request, Maintenance $maintenance)
    {
        $validated = $request->validate([
            'description' => 'sometimes|string',
            'cost' => 'sometimes|numeric',
            'date' => 'sometimes|date',
        ]);

        $maintenance->update($validated);

        return response()->json($maintenance);
    }

    public function destroy(Maintenance $maintenance)
    {
        $maintenance->delete();

        return response()->json(['message' => 'Maintenance record deleted']);
    }
}
