<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Guarantor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContractController extends Controller
{
    public function index()
    {
        return response()->json(Contract::with(['property.owner', 'tenant.guarantors'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'nullable|exists:properties,id',
            'tenant_id' => 'nullable|exists:tenants,id',
            'property_data' => 'nullable|array',
            'tenant_data' => 'nullable|array',
            'owner_data' => 'nullable|array',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'rent_amount' => 'required|numeric',
            'increase_frequency_months' => 'required|integer',
            'temp_file' => 'nullable|string',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $propertyId = $validated['property_id'];
            $tenantId = $validated['tenant_id'];

            // 1. Handle Tenant Creation if needed
            if (!$tenantId && $request->has('tenant_data')) {
                $tenantData = $request->tenant_data;
                $dni = $tenantData['dni'] ?? ('S/D ' . now()->timestamp);

                // Safety check to avoid duplicate DNI if matching failed
                $tenant = \App\Models\Tenant::updateOrCreate(
                    ['dni' => $dni],
                    [
                        'first_name' => $tenantData['first_name'] ?? 'S/D',
                        'last_name' => $tenantData['last_name'] ?? 'S/D',
                        'address' => $tenantData['address'] ?? 'S/D',
                        'whatsapp' => $tenantData['whatsapp'] ?? 'S/D',
                        'email' => $tenantData['email'] ?? ('sd' . now()->timestamp . '@inmo.com'),
                    ]
                );
                $tenantId = $tenant->id;
            }

            // 2. Handle Property & Owner Creation if needed
            if (!$propertyId && $request->has('property_data')) {
                $ownerId = $request->input('property_data.owner_id');

                // create owner if owner_data is provided
                if (!$ownerId && $request->has('owner_data')) {
                    $ownerData = $request->owner_data;
                    $dni = $ownerData['dni'] ?? ('S/D ' . now()->timestamp);

                    $owner = \App\Models\Owner::updateOrCreate(
                        ['dni' => $dni],
                        [
                            'first_name' => $ownerData['first_name'] ?? 'S/D',
                            'last_name' => $ownerData['last_name'] ?? 'S/D',
                            'address' => $ownerData['address'] ?? 'S/D',
                            'whatsapp' => $ownerData['whatsapp'] ?? 'S/D',
                            'email' => $ownerData['email'] ?? ('sd' . now()->timestamp . '@inmo.com'),
                        ]
                    );
                    $ownerId = $owner->id;
                }

                $propertyData = $request->property_data;
                $propertyData['owner_id'] = $ownerId;
                // Default values for required fields based on DB schema
                $propertyData['type'] = $propertyData['type'] ?? 'Dpto';
                $propertyData['real_estate_id'] = $propertyData['real_estate_id'] ?? 'S/D ' . now()->timestamp;
                $propertyData['domain'] = $propertyData['domain'] ?? 'S/D';
                $propertyData['location'] = $propertyData['location'] ?? 'S/D';

                $property = \App\Models\Property::create($propertyData);
                $propertyId = $property->id;
            }

            // 3. Deactivate old active contracts for this property
            Contract::where('property_id', $propertyId)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            // 4. Handle File Movement
            $filePath = null;
            if ($request->filled('temp_file')) {
                $tempPath = $validated['temp_file'];
                if (\Storage::disk('local')->exists($tempPath)) {
                    $fileName = basename($tempPath);
                    $newPath = 'contracts/' . $fileName;
                    \Storage::disk('local')->move($tempPath, $newPath);
                    $filePath = $newPath;
                }
            }

            // 5. Create Contract
            $contractData = [
                'property_id' => $propertyId,
                'tenant_id' => $tenantId,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'rent_amount' => $validated['rent_amount'],
                'increase_frequency_months' => $validated['increase_frequency_months'],
                'file_path' => $filePath,
                'is_active' => true,
            ];

            $contract = Contract::create($contractData);

            return response()->json($contract->load(['property.owner', 'tenant']), 201);
        });
    }

    public function show(Contract $contract)
    {
        return response()->json($contract->load(['property.owner', 'tenant.guarantors', 'payments']));
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'end_date' => 'sometimes|date|after:start_date',
            'rent_amount' => 'sometimes|numeric',
            'increase_frequency_months' => 'sometimes|integer',
            'is_active' => 'sometimes|boolean',
        ]);

        $contract->update($validated);

        return response()->json($contract);
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return response()->json(['message' => 'Contract deleted']);
    }
}
