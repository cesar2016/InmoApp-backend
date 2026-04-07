<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function index()
    {
        return response()->json(Tenant::with(['contracts.property', 'guarantors'])->get());
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'dni' => 'required',
            'address' => 'required',
            'whatsapp' => 'required',
            'email' => 'required|email|unique:tenants',
            'property_id' => 'nullable|exists:properties,id',
            'rent_amount' => 'nullable|numeric|required_with:property_id',
            'start_date' => 'nullable|date|required_with:property_id',
            'end_date' => 'nullable|date|required_with:property_id',
            'increase_frequency_months' => 'nullable|integer',
            'guarantor_ids' => 'nullable|array',
            'guarantor_ids.*' => 'exists:guarantors,id',
        ]);

        if ($validator->fails()) {
            \Log::error('Tenant Validation Errors:', $validator->errors()->toArray());
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        return \DB::transaction(function () use ($validated) {
            $tenantData = \Illuminate\Support\Arr::except($validated, ['property_id', 'rent_amount', 'start_date', 'end_date', 'increase_frequency_months', 'guarantor_ids']);
            $tenant = Tenant::create($tenantData);

            if (!empty($validated['guarantor_ids'])) {
                \App\Models\Guarantor::whereIn('id', $validated['guarantor_ids'])->update(['tenant_id' => $tenant->id]);
            }

            if (!empty($validated['property_id'])) {
                $tenant->contracts()->create([
                    'property_id' => $validated['property_id'],
                    'rent_amount' => $validated['rent_amount'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'increase_frequency_months' => $validated['increase_frequency_months'] ?? 6,
                    'is_active' => true,
                ]);
            }

            return response()->json($tenant, 201);
        });
    }

    public function show(Tenant $tenant)
    {
        return response()->json($tenant->load(['contracts.property', 'activeContract.property']));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $validator = \Validator::make($request->all(), [
            'first_name' => 'sometimes',
            'last_name' => 'sometimes',
            'dni' => 'sometimes',
            'address' => 'sometimes',
            'whatsapp' => 'sometimes',
            'email' => 'sometimes|email|unique:tenants,email,' . $tenant->id,
            'property_id' => 'nullable|exists:properties,id',
            'rent_amount' => 'nullable|numeric|required_with:property_id',
            'start_date' => 'nullable|date|required_with:property_id',
            'end_date' => 'nullable|date|required_with:property_id',
            'increase_frequency_months' => 'nullable|integer',
            'guarantor_ids' => 'nullable|array',
            'guarantor_ids.*' => 'exists:guarantors,id',
        ]);

        if ($validator->fails()) {
            \Log::error('Tenant Update Validation Errors:', $validator->errors()->toArray());
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        return \DB::transaction(function () use ($validated, $tenant) {
            $tenantData = \Illuminate\Support\Arr::except($validated, ['property_id', 'rent_amount', 'start_date', 'end_date', 'increase_frequency_months', 'guarantor_ids']);
            $tenant->update($tenantData);

            if (isset($validated['guarantor_ids'])) {
                // Remove old links
                \App\Models\Guarantor::where('tenant_id', $tenant->id)->update(['tenant_id' => null]);
                // Add new links
                \App\Models\Guarantor::whereIn('id', $validated['guarantor_ids'])->update(['tenant_id' => $tenant->id]);
            }

            if (!empty($validated['property_id'])) {
                // If there's an active contract for a DIFFERENT property, we might want to close it?
                // The prompt says "vincularla (o sea alquilar)", so we'll create a new contract.
                $tenant->contracts()->where('is_active', true)->update(['is_active' => false]);

                $tenant->contracts()->create([
                    'property_id' => $validated['property_id'],
                    'rent_amount' => $validated['rent_amount'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'increase_frequency_months' => $validated['increase_frequency_months'] ?? 6,
                    'is_active' => true,
                ]);
            }

            return response()->json($tenant);
        });
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return response()->json(['message' => 'Tenant deleted']);
    }
}
