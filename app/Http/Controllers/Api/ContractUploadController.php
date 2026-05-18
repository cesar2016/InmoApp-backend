<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ContractParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

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
            'file' => 'required|file|mimes:pdf,doc,docx,odt|max:10240',
        ]);

        $file = $request->file('file');
        $extension = $file->getClientOriginalExtension();
        Log::info('Contrato upload: formato detectado', ['extension' => $extension]);
        $path = $file->storeAs('temp_contracts', time().'_'.$file->getClientOriginalName());
        $fullPath = \Storage::disk('local')->path($path);
        \Log::info('Contrato upload: guardado temporal', [
            'path' => $path,
            'fullPath' => $fullPath,
            'originalName' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'extension' => $extension,
        ]);

        try {
            $data = $this->parser->parse($fullPath, $extension);

            // DO NOT clean up temp file here, it will be moved during Contract@store
            // Storage::delete($path);

            return response()->json([
                'success' => true,
                'data' => array_merge($data, ['temp_file' => $path, 'extension' => $extension]),
                'message' => 'Documento procesado con éxito',
            ]);
        } catch (Throwable $e) {
            Storage::delete($path);
            \Log::error('Error processing contract file: '.$e->getMessage(), [
                'exception' => $e,
                'file' => $file->getClientOriginalName(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el documento: '.$e->getMessage(),
            ], 422);
        }
    }
}
