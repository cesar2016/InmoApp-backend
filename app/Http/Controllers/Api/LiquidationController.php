<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LiquidationMail;
use App\Models\Liquidation;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class LiquidationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $owner_id = $request->get('owner_id');

        $query = Liquidation::with(['owner', 'contract.property', 'contract.tenant']);

        if ($owner_id) {
            $query->where('owner_id', $owner_id);
        }

        return $query->latest()->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'required|exists:owners,id',
            'contract_id' => 'required|exists:contracts,id',
            'month' => 'required|string',
            'year' => 'required|integer',
            'alquiler' => 'required|numeric',
            'tasa_municipal' => 'nullable|numeric',
            'pago_tasa_municipal' => 'nullable|numeric',
            'recargo' => 'nullable|numeric',
            'pago_luz' => 'nullable|numeric',
            'descuento_admin' => 'nullable|numeric',
            'total_percibido' => 'required|numeric',
            'total_liquidado' => 'required|numeric',
            'note' => 'nullable|string',
        ]);

        $liquidation = Liquidation::create($validated);

        return response()->json([
            'message' => 'Liquidación guardada correctamente',
            'liquidation' => $liquidation->load(['owner', 'contract.property']),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Liquidation $liquidation)
    {
        return $liquidation->load(['owner', 'contract.property', 'contract.tenant']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Liquidation $liquidation)
    {
        $liquidation->delete();

        return response()->json(['message' => 'Liquidación eliminada']);
    }

    public function sendEmail(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf|max:10000',
            'owner_id' => 'required|exists:owners,id',
            'email' => 'required|email',
            'subject' => 'required|string',
        ]);

        $owner = Owner::findOrFail($request->owner_id);
        $file = $request->file('file');

        // Temporarily store the file in local disk
        $tempPath = $file->storeAs('temp', $file->getClientOriginalName(), 'local');
        $absolutePath = storage_path('app/'.$tempPath);

        try {
            // Priority: Use the email from the database to be 100% sure
            Mail::to($owner->email)->send(new LiquidationMail(
                $owner->first_name.' '.$owner->last_name,
                $request->subject,
                $absolutePath
            ));

            // Clean up
            Storage::delete($tempPath);

            return response()->json(['message' => 'Email enviado correctamente']);
        } catch (\Exception $e) {
            Storage::delete($tempPath);

            return response()->json(['message' => 'Error al enviar email: '.$e->getMessage()], 500);
        }
    }
}
