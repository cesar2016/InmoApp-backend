<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;

class OwnerController extends Controller
{
    public function index()
    {
        return response()->json(Owner::with('properties')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'dni' => 'required|string|unique:owners',
            'address' => 'required|string',
            'whatsapp' => 'required|string',
            'email' => 'required|email|unique:owners',
        ]);

        $owner = Owner::create($validated);

        return response()->json($owner, 201);
    }

    public function show(Owner $owner)
    {
        return response()->json($owner->load('properties'));
    }

    public function update(Request $request, Owner $owner)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'dni' => 'sometimes|string|unique:owners,dni,' . $owner->id,
            'address' => 'sometimes|string',
            'whatsapp' => 'sometimes|string',
            'email' => 'sometimes|email|unique:owners,email,' . $owner->id,
        ]);

        $owner->update($validated);

        return response()->json($owner);
    }

    public function destroy(Owner $owner)
    {
        $owner->delete();

        return response()->json(['message' => 'Owner deleted']);
    }
}
