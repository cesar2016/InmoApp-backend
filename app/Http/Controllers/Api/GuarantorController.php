<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Guarantor;
use Illuminate\Http\Request;

class GuarantorController extends Controller
{
    public function index(Request $request)
    {
        $query = Guarantor::query();
        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }
        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'dni' => 'required|string',
            'address' => 'required|string',
            'whatsapp' => 'required|string',
            'email' => 'required|email',
            'tenant_id' => 'required|exists:tenants,id',
        ]);

        $guarantor = Guarantor::create($validated);
        return response()->json($guarantor, 201);
    }

    public function show(Guarantor $guarantor)
    {
        return response()->json($guarantor);
    }

    public function update(Request $request, Guarantor $guarantor)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|string',
            'last_name' => 'sometimes|string',
            'dni' => 'sometimes|string',
            'address' => 'sometimes|string',
            'whatsapp' => 'sometimes|string',
            'email' => 'sometimes|email',
        ]);

        $guarantor->update($validated);
        return response()->json($guarantor);
    }

    public function destroy(Guarantor $guarantor)
    {
        $guarantor->delete();
        return response()->json(['message' => 'Guarantor deleted']);
    }
}
