<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Owner;
use App\Models\Property;
use App\Models\Tenant;
use App\Services\AiContractParserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ContractUploadController extends Controller
{
    protected $parser;

    public function __construct(AiContractParserService $parser)
    {
        $this->parser = $parser;
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:pdf,doc,docx,odt,json|max:10240',
            'provider' => 'nullable|string',
        ]);

        $file = $request->file('file');
        $providerName = $request->input('provider');
        $extension = strtolower($file->getClientOriginalExtension());

        Log::info('Contrato upload: formato detectado', ['extension' => $extension, 'provider' => $providerName]);

        // Shortcut for JSON files
        if ($extension === 'json') {
            try {
                $jsonContent = file_get_contents($file->getRealPath());
                $data = json_decode($jsonContent, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new \Exception('El archivo JSON proporcionado no es válido.');
                }

                return response()->json([
                    'success' => true,
                    'data' => array_merge($data, ['extension' => 'json']),
                    'message' => 'JSON procesado con éxito',
                ]);
            } catch (Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al leer el JSON: '.$e->getMessage(),
                ], 422);
            }
        }

        $path = $file->storeAs('temp_contracts', time().'_'.$file->getClientOriginalName());
        $fullPath = Storage::disk('local')->path($path);

        try {
            $data = $this->parser->parse($fullPath, $extension, $providerName);

            return response()->json([
                'success' => true,
                'data' => array_merge($data, ['temp_file' => $path, 'extension' => $extension]),
                'message' => 'Documento procesado con éxito por '.($providerName ?? 'el sistema'),
            ]);
        } catch (Throwable $e) {
            Storage::delete($path);
            Log::error('Error processing contract file: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el documento: '.$e->getMessage(),
            ], 422);
        }
    }

    public function smartSave(Request $request)
    {
        $data = $request->validate([
            'tenant' => 'required|array',
            'owner' => 'required|array',
            'property' => 'required|array',
            'contract' => 'required|array',
            'guarantors' => 'nullable|array',
        ]);

        try {
            return \DB::transaction(function () use ($data) {
                // 1. Handle Tenant
                $tenant = Tenant::updateOrCreate(
                    ['dni' => $data['tenant']['dni']],
                    $data['tenant']
                );

                // 2. Handle Owner
                $owner = Owner::updateOrCreate(
                    ['dni' => $data['owner']['dni']],
                    $data['owner']
                );

                // 3. Handle Property
                $propertyData = $data['property'];
                $propertyData['owner_id'] = $owner->id;
                $property = Property::updateOrCreate(
                    ['street' => $propertyData['street'], 'number' => $propertyData['number'], 'location' => $propertyData['location']],
                    $propertyData
                );

                // 4. Handle Contract
                $contractData = $data['contract'];
                $contractData['property_id'] = $property->id;
                $contractData['tenant_id'] = $tenant->id;
                $contractData['is_active'] = true;

                $contract = Contract::create($contractData);

                // 5. Handle Guarantors
                if (! empty($data['guarantors'])) {
                    foreach ($data['guarantors'] as $g) {
                        if (! empty($g['first_name'])) {
                            $tenant->guarantors()->create([
                                'first_name' => $g['first_name'],
                                'last_name' => $g['last_name'] ?? null,
                                'dni' => $g['dni'] ?? null,
                                'address' => $g['address'] ?? null,
                                'whatsapp' => $g['whatsapp'] ?? null,
                                'email' => $g['email'] ?? null,
                            ]);
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Contrato, propietarios e inquilinos creados/actualizados con éxito.',
                    'contract_id' => $contract->id,
                ]);
            });
        } catch (Throwable $e) {
            Log::error('Smart Save Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar los datos consolidados: '.$e->getMessage(),
            ], 500);
        }
    }
}
