<?php

namespace App\Services;

use App\Services\LLM\LLMProviderInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Smalot\PdfParser\Parser;

class AiContractParserService
{
    private const MAX_LLM_TEXT_LENGTH = 20000;

    private array $providers;

    private string $stringsText = '';

    public function __construct(LLMProviderInterface ...$providers)
    {
        $this->providers = $providers;
    }

    public function parse(string $filePath, string $extension): array
    {
        $this->stringsText = '';
        $text = $this->extractText($filePath, $extension);
        $plainText = preg_replace('/\s+/', ' ', $text);

        $result = $this->parseWithFallback($plainText);

        $result['plain_text'] = $plainText;
        $result['raw_text_preview'] = Str::limit($plainText, 500);

        return $result;
    }

    private function parseWithFallback(string $plainText): array
    {
        $lastException = null;

        foreach ($this->providers as $provider) {
            try {
                Log::info('AiContractParser: intentando con proveedor '.$provider->getName());
                $result = $provider->parseContract(Str::limit($plainText, self::MAX_LLM_TEXT_LENGTH, ''));
                Log::info('AiContractParser: proveedor '.$provider->getName().' respondió exitosamente');

                return $this->normalizeResult($result);
            } catch (\Throwable $e) {
                $lastException = $e;
                Log::warning('AiContractParser: proveedor '.$provider->getName().' falló: '.$e->getMessage());
            }
        }

        Log::warning('AiContractParser: usando parser local de respaldo', [
            'last_error' => $lastException?->getMessage(),
        ]);

        return $this->normalizeResult((new ContractParserService)->parseText($plainText));
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

    private function extractText($filePath, $extension): string
    {
        $extension = strtolower($extension);

        if ($extension === 'pdf') {
            $parser = new Parser;
            $pdf = $parser->parseFile($filePath);

            return $pdf->getText();
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
                            $text = strip_tags($contents);
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
