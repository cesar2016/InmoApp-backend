<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;

class ContractParserService
{
    /** @var string Clean text from `strings -e l` for .doc files */
    protected $stringsText = '';

    public function parse($filePath, $extension)
    {
        $this->stringsText = '';
        $text = $this->extractText($filePath, $extension);

        return $this->parseText($text);
    }

    public function parseText(string $text): array
    {
        // Limit text size to avoid token limits (llama-3-70b has a large window but let's be safe)
        $cleanText = Str::limit($text, 15000);

        try {
            $response = $this->callGroq($cleanText);

            // Merge with preview fields expected by the frontend
            return array_merge($response, [
                'plain_text' => $text,
                'raw_text_preview' => Str::limit($text, 500),
            ]);
        } catch (\Throwable $e) {
            Log::error('Groq Parsing Error: '.$e->getMessage());

            // Fallback empty structure
            return [
                'tenant' => ['first_name' => '', 'last_name' => '', 'dni' => '', 'email' => '', 'whatsapp' => '', 'address' => ''],
                'owner' => ['first_name' => '', 'last_name' => '', 'dni' => '', 'email' => '', 'whatsapp' => '', 'address' => ''],
                'property' => ['street' => '', 'number' => '', 'location' => '', 'type' => 'Otro'],
                'contract' => ['start_date' => '', 'end_date' => '', 'rent_amount' => '', 'increase_frequency_months' => 6],
                'guarantors' => [],
                'plain_text' => $text,
                'raw_text_preview' => Str::limit($text, 500),
                'error' => 'No se pudo procesar el documento automáticamente: '.$e->getMessage(),
            ];
        }
    }

    private function callGroq(string $text)
    {
        $apiKey = config('services.groq.key');
        $model = config('services.groq.model');

        if (! $apiKey) {
            throw new \Exception('Groq API Key not configured.');
        }

        $systemPrompt = 'Sos un asistente experto en procesamiento de contratos legales argentinos. 
        Tu tarea es extraer información estructurada de un texto de contrato y devolverla estrictamente en formato JSON.
        SIGUE ESTAS REGLAS:
        1. Devuelve SOLO el JSON, sin texto explicativo antes ni después.
        2. Si un dato no está en el texto, devuelve una cadena vacía ("") o el valor predeterminado especificado.
        3. Formatea las fechas como YYYY-MM-DD.
        4. El monto de alquiler debe ser un número o string numérico (sin puntos de miles).
        5. Identifica correctamente al Locador (Dueño) y Locatario (Inquilino).
        
        ESTRUCTURA REQUERIDA:
        {
          "tenant": {"first_name": "", "last_name": "", "dni": "", "email": "", "whatsapp": "", "address": ""},
          "owner": {"first_name": "", "last_name": "", "dni": "", "email": "", "whatsapp": "", "address": ""},
          "property": {"street": "", "number": "", "location": "", "type": "Casa/Departamento/Local/Otro"},
          "contract": {"start_date": "", "end_date": "", "rent_amount": "", "increase_frequency_months": 6},
          "guarantors": [{"first_name": "", "last_name": "", "dni": ""}]
        }';

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => "Extrae la información de este contrato:\n\n".$text],
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.1,
            ]);

        if ($response->failed()) {
            throw new \Exception('Groq API failed: '.$response->body());
        }

        $result = $response->json();
        $content = $result['choices'][0]['message']['content'] ?? '{}';

        return json_decode($content, true);
    }

    private function extractText($filePath, $extension)
    {
        $extension = strtolower($extension);

        if ($extension === 'pdf') {
            return $this->extractPdfText($filePath);
        } elseif ($extension === 'docx') {
            $phpWord = IOFactory::load($filePath, 'Word2007');

            return $this->getPhpWordText($phpWord);
        } elseif ($extension === 'doc') {
            try {
                $phpWord = IOFactory::load($filePath, 'MsDoc');
                $text = $this->getPhpWordText($phpWord);
            } catch (\Throwable $e) {
                $text = '';
            }
            $this->stringsText = $this->getStringsText($filePath);
            $text .= "\n".$this->stringsText;

            return $text;
        } elseif ($extension === 'odt') {
            return $this->extractTextFromOdt($filePath);
        }

        return '';
    }

    private function extractPdfText(string $filePath): string
    {
        $process = new Process(['pdftotext', '-layout', '-enc', 'UTF-8', $filePath, '-']);
        try {
            $process->run();
            $text = trim($process->getOutput());
            if ($process->isSuccessful() && $text !== '') {
                return $text;
            }
        } catch (\Throwable $e) {
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    private function extractTextFromOdt($filePath)
    {
        $text = '';
        try {
            $zip = new \ZipArchive;
            if ($zip->open($filePath) === true) {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = $zip->getNameIndex($i);
                    if (basename($name) === 'content.xml') {
                        $contents = $zip->getFromIndex($i);
                        if ($contents) {
                            $text = strip_tags($contents);
                        }
                        break;
                    }
                }
                $zip->close();
            }
        } catch (\Throwable $e) {
        }

        return $text;
    }

    private function getPhpWordText($phpWord)
    {
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            $text .= $this->getRecursiveText($section);
        }

        return $text;
    }

    private function getRecursiveText($element)
    {
        $text = '';
        if (method_exists($element, 'getElements')) {
            foreach ($element->getElements() as $child) {
                $text .= $this->getRecursiveText($child);
            }
        } elseif (method_exists($element, 'getText')) {
            $t = $element->getText();
            if (is_string($t)) {
                $text .= $t."\n";
            } elseif (method_exists($t, 'getText')) {
                $text .= $t->getText()."\n";
            }
        }
        if ($element instanceof Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    $text .= $this->getRecursiveText($cell);
                }
            }
        }
        if ($element instanceof TextRun) {
            foreach ($element->getElements() as $child) {
                if (method_exists($child, 'getText')) {
                    $text .= $child->getText();
                }
            }
            $text .= "\n";
        }

        return $text;
    }

    private function getStringsText($filePath)
    {
        $cmd1 = 'strings "'.$filePath.'"';
        $cmd2 = 'strings -e l "'.$filePath.'"';
        $out1 = shell_exec($cmd1) ?: '';
        $out2 = shell_exec($cmd2) ?: '';

        return $out1."\n".$out2;
    }
}
