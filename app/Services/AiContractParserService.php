<?php

namespace App\Services;

use App\Services\LLM\LLMProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;
use Symfony\Component\Process\Process;

class AiContractParserService
{
    private const MAX_LLM_TEXT_LENGTH = 20000;

    private array $providers;

    private string $stringsText = '';

    public function __construct(LLMProviderInterface ...$providers)
    {
        $this->providers = $providers;
    }

    public function parse(string $filePath, string $extension, ?string $providerName = null): array
    {
        $this->stringsText = '';
        $text = $this->extractText($filePath, $extension);
        Log::info('AiContractParser: texto extraído', [
            'length' => strlen($text),
            'extension' => $extension,
            'preview' => Str::limit($text, 100),
        ]);
        $plainText = preg_replace('/\s+/', ' ', $text);

        if (mb_strlen(trim($plainText)) < 1) {
            Log::warning('AiContractParser: No se pudo extraer texto, intentando solo con prompt');
            $plainText = '[El documento no contiene texto legible por OCR estándar]';
        }

        $result = $this->parseWithFallback($plainText, $providerName);

        $result['plain_text'] = $plainText;
        $result['raw_text_preview'] = Str::limit($plainText, 500);

        return $result;
    }

    public function parseFromText(string $text, ?string $providerName = null): array
    {
        $plainText = preg_replace('/\s+/', ' ', $text);
        $result = $this->parseWithFallback($plainText, $providerName);

        $result['plain_text'] = $plainText;
        $result['raw_text_preview'] = Str::limit($plainText, 500);

        return $result;
    }

    private function parseWithFallback(string $plainText, ?string $providerName = null): array
    {
        $localResult = (new LocalContractParserService)->parseText($plainText);
        $minRemoteTextLength = config('ai-parser.min_remote_text_length', 120);

        if (mb_strlen(trim($plainText)) < $minRemoteTextLength) {
            Log::warning('AiContractParser: texto corto, usando parser local de respaldo', [
                'length' => mb_strlen(trim($plainText)),
            ]);

            return $this->normalizeResult($localResult);
        }

        $lastException = null;

        // If a specific provider is requested, try only that one
        if ($providerName) {
            foreach ($this->providers as $provider) {
                if ($provider->getName() === $providerName) {
                    try {
                        Log::info('AiContractParser: intentando con proveedor solicitado '.$provider->getName());
                        $result = $provider->parseContract(Str::limit($plainText, self::MAX_LLM_TEXT_LENGTH, ''));

                        return $this->normalizeResult($this->mergeWithLocal($result, $localResult));
                    } catch (\Throwable $e) {
                        Log::warning('AiContractParser: proveedor solicitado '.$provider->getName().' falló: '.$e->getMessage());
                        // Fall back to local parser instead of throwing
                        break;
                    }
                }
            }
        }

        $providerLimit = max(0, (int) config('ai-parser.remote_provider_limit', 2));
        $providers = $providerLimit > 0 ? array_slice($this->providers, 0, $providerLimit) : [];

        foreach ($providers as $provider) {
            try {
                Log::info('AiContractParser: intentando con proveedor '.$provider->getName());
                $result = $provider->parseContract(Str::limit($plainText, self::MAX_LLM_TEXT_LENGTH, ''));
                Log::info('AiContractParser: proveedor '.$provider->getName().' respondió exitosamente');

                return $this->normalizeResult($this->mergeWithLocal($result, $localResult));
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning('AiContractParser: proveedor '.$provider->getName().' falló: '.$e->getMessage());
            }
        }

        Log::warning('AiContractParser: usando parser local de respaldo', [
            'last_error' => $lastException?->getMessage(),
        ]);

        return $this->normalizeResult($localResult);
    }

    private function mergeWithLocal(array $aiResult, array $localResult): array
    {
        foreach ($localResult as $key => $localValue) {
            if (! array_key_exists($key, $aiResult)) {
                $aiResult[$key] = $localValue;

                continue;
            }

            if (is_array($localValue) && is_array($aiResult[$key])) {
                $aiResult[$key] = $this->mergeWithLocal($aiResult[$key], $localValue);

                continue;
            }

            if ($this->shouldPreferLocal($key) && $localValue !== null && $localValue !== '' && $localValue !== []) {
                $aiResult[$key] = $localValue;

                continue;
            }

            if ($aiResult[$key] === null || $aiResult[$key] === '' || $aiResult[$key] === []) {
                $aiResult[$key] = $localValue;
            }
        }

        // Guard against a common AI hallucination: copying one party's contact
        // details (email/phone) onto the other party.
        foreach (['tenant', 'owner'] as $party) {
            $other = $party === 'tenant' ? 'owner' : 'tenant';
            if (! isset($aiResult[$party], $aiResult[$other])) {
                continue;
            }
            foreach (['email', 'whatsapp'] as $contactField) {
                $aiValue = $aiResult[$party][$contactField] ?? null;
                $otherLocalValue = $localResult[$other][$contactField] ?? null;
                if ($otherLocalValue && $aiValue && $aiValue === $otherLocalValue) {
                    $aiResult[$party][$contactField] = '';
                }
            }
        }

        return $aiResult;
    }

    private function shouldPreferLocal(string $key): bool
    {
        return in_array($key, ['first_name', 'last_name', 'start_date', 'end_date', 'rent_amount', 'increase_frequency_months'], true);
    }

    private function normalizeResult(array $data): array
    {
        return [
            'tenant' => $data['tenant'] ?? [
                'first_name' => null, 'last_name' => null, 'dni' => null,
                'address' => null, 'whatsapp' => null, 'email' => null,
            ],
            'owner' => $data['owner'] ?? [
                'first_name' => null, 'last_name' => null, 'dni' => null,
                'address' => null, 'whatsapp' => null, 'email' => null,
            ],
            'property' => $data['property'] ?? [
                'street' => null, 'number' => null, 'floor' => null, 'dept' => null,
                'location' => null, 'type' => null,
            ],
            'contract' => $data['contract'] ?? [
                'start_date' => null, 'end_date' => null,
                'rent_amount' => null, 'increase_frequency_months' => null,
            ],
            'guarantors' => $data['guarantors'] ?? [],
        ];
    }

    public function extractText($filePath, $extension): string
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
        $process->setTimeout((int) config('ai-parser.pdf_text_timeout', 8));

        try {
            $process->run();
            $text = trim($process->getOutput());

            if ($process->isSuccessful() && $text !== '') {
                return $text;
            }
        } catch (\Throwable $e) {
            Log::warning('AiContractParser: pdftotext falló: '.$e->getMessage());
        }

        $parser = new Parser;
        $pdf = $parser->parseFile($filePath);

        return $pdf->getText();
    }

    private function extractTextFromOdt($filePath): string
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
                            // ODT stores spaces as empty <text:s/> elements (no text node), so
                            // textContent silently drops them between styled runs ("calleSan
                            // Martin"). Convert semantic whitespace to literal characters first.
                            $contents = preg_replace_callback('/<text:s(?:\s+text:c="(\d+)")?\s*\/>/u', function ($m) {
                                return str_repeat(' ', (int) ($m[1] ?? 1));
                            }, $contents);
                            $contents = preg_replace('/<text:tab(?:\s+[^>]*)?\/>/u', "\t", $contents);
                            $contents = preg_replace('/<text:line-break(?:\s+[^>]*)?\/>/u', "\n", $contents);

                            $dom = new \DOMDocument;
                            $dom->loadXML($contents, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);
                            $xpath = new \DOMXPath($dom);
                            $xpath->registerNamespace('text', 'urn:oasis:names:tc:opendocument:xmlns:text:1.0');

                            // Paragraphs and headings only; spans would duplicate their text
                            $nodes = $xpath->query('//text:p | //text:h');
                            foreach ($nodes as $node) {
                                $text .= trim($node->textContent)."\n";
                            }

                            // Also try getting all text if xpath returns nothing
                            if (trim($text) === '') {
                                $text = strip_tags($contents);
                            }
                        }
                        break;
                    }
                }
                $zip->close();
            }
        } catch (\Throwable $e) {
            $text = '';
        }

        return $text;
    }

    private function getPhpWordText($phpWord): string
    {
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            $text .= $this->getRecursiveText($section);
        }

        return $text;
    }

    private function getRecursiveText($element): string
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

    private function getStringsText($filePath): string
    {
        $cmd1 = 'strings "'.$filePath.'"';
        $cmd2 = 'strings -e l "'.$filePath.'"';
        $out1 = shell_exec($cmd1) ?: '';
        $out2 = shell_exec($cmd2) ?: '';

        return $out1."\n".$out2;
    }
}
