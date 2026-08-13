<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiContractParserService;
use App\Services\LLM\LLMProviderInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatTestController extends Controller
{
    private array $providers;

    private AiContractParserService $parser;

    public function __construct(AiContractParserService $parser, LLMProviderInterface ...$providers)
    {
        $this->parser = $parser;
        $this->providers = $providers;
    }

    public function getProviders()
    {
        $data = array_map(fn ($p) => $p->getName(), $this->providers);

        return response()->json($data);
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required_without:file|string|nullable',
            'file' => 'nullable|file|mimes:pdf,docx,doc,odt|max:10240',
            'provider' => 'nullable|string',
        ]);

        $providerName = $request->input('provider');
        $provider = $this->getProvider($providerName);

        if (! $provider) {
            return response()->json(['message' => 'No hay proveedores de IA configurados o el seleccionado no es válido.'], 422);
        }

        $message = $request->input('message', '');
        $context = '';

        if ($request->hasFile('file')) {
            try {
                $file = $request->file('file');
                $tempPath = $file->path();
                $extension = $file->getClientOriginalExtension();

                // Now using public extractText method
                $context = $this->parser->extractText($tempPath, $extension);

                if (empty($message)) {
                    $message = 'Analiza este documento y resúmelo.';
                }
            } catch (\Throwable $e) {
                Log::error('Chat Sandbox Extraction Error: '.$e->getMessage());

                return response()->json(['message' => 'Error al extraer texto del archivo: '.$e->getMessage()], 422);
            }
        }

        $prompt = $message;
        if (! empty($context)) {
            $prompt = "Contexto del documento:\n".$context."\n\nPregunta: ".$message;
        }

        try {
            Log::info('Chat Sandbox Request', ['provider' => $provider->getName(), 'msg_len' => strlen($message)]);
            $response = $provider->chat($prompt);

            return response()->json([
                'response' => $response,
                'provider' => $provider->getName(),
                'context_length' => strlen($context),
                'fullContext' => $context,
            ]);
        } catch (\Throwable $e) {
            Log::error('Chat Sandbox LLM Error: '.$e->getMessage());

            return response()->json(['message' => 'Error en la IA ('.$provider->getName().'): '.$e->getMessage()], 500);
        }
    }

    public function parseText(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'provider' => 'nullable|string',
        ]);

        try {
            $data = $this->parser->parseFromText($request->input('text'), $request->input('provider'));

            return response()->json($data);
        } catch (\Throwable $e) {
            Log::error('Chat Parse Text Error: '.$e->getMessage());

            return response()->json(['message' => 'Error al estructurar el contrato: '.$e->getMessage()], 500);
        }
    }

    private function getProvider(?string $name): ?LLMProviderInterface
    {
        if (empty($this->providers)) {
            return null;
        }

        if ($name) {
            foreach ($this->providers as $p) {
                if ($p->getName() === $name) {
                    return $p;
                }
            }
        }

        return $this->providers[0];
    }
}
