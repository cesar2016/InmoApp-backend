<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContractParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ContractUploadController extends Controller
{
    protected $parser;

    public function __construct(ContractParserService $parser)
    {
        $this->parser = $parser;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs('temp_contracts', time() . '_' . $file->getClientOriginalName());
        $fullPath = \Storage::disk('local')->path($path);

        try {
            $data = $this->parser->parse($fullPath, $extension);

            // DO NOT clean up temp file here, it will be moved during Contract@store
            // Storage::delete($path);

            return response()->json([
                'success' => true,
                'data' => array_merge($data, ['temp_file' => $path]),
                'message' => 'Documento procesado con éxito'
            ]);
        } catch (\Exception $e) {
            Storage::delete($path);
            \Log::error('Error processing contract file: ' . $e->getMessage(), [
                'exception' => $e,
                'file' => $file->getClientOriginalName()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el documento: ' . $e->getMessage()
            ], 422);
        }
    }
}
