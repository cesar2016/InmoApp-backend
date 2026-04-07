<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ContractFileController extends Controller
{
    public function show(Contract $contract)
    {
        if (!$contract->file_path || !Storage::disk('local')->exists($contract->file_path)) {
            return response()->json(['message' => 'Archivo no encontrado'], 404);
        }

        $path = Storage::disk('local')->path($contract->file_path);

        return response()->file($path);
    }
}
