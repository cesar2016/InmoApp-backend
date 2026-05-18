<?php

namespace Tests\Unit;

use App\Services\AiContractParserService;
use App\Services\LLM\LLMProviderInterface;
use Tests\TestCase;

class AiContractParserServiceTest extends TestCase
{
    public function test_falls_back_to_local_parser_when_providers_fail(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'contract');
        $path = $tmp.'.odt';
        rename($tmp, $path);

        $contractText = 'Entre la Sra. HILDA PEREZ, DNI 12.345.678, en adelante LOCADORA. '.
            'y la Sra. ANA LOPEZ, DNI 23.456.789, en adelante LOCATARIA. '.
            'El inmueble sito en calle Salta N° 224, de la ciudad de San Cristóbal. '.
            'desde el día 1 de abril de 2026 hasta el día 31 de marzo de 2027. '.
            'canon locativo PESOS 250.000 ajuste semestral.';

        $zip = new \ZipArchive;
        if ($zip->open($path, \ZipArchive::CREATE) !== true) {
            $this->markTestIncomplete('Could not create temporary odt file');

            return;
        }

        $zip->addFromString(
            'content.xml',
            '<?xml version="1.0" encoding="UTF-8"?><office:document-content xmlns:office="http://www.w3.org/2002/07/office">'.
            $contractText.
            '</office:document-content>'
        );
        $zip->close();

        $service = new AiContractParserService(new FailingProvider);
        $data = $service->parse($path, 'odt');

        $this->assertSame('224', $data['property']['number']);
        $this->assertSame('2026-04-01', $data['contract']['start_date']);
        $this->assertSame('250000', $data['contract']['rent_amount']);
        $this->assertSame(6, $data['contract']['increase_frequency_months']);

        @unlink($path);
    }
}

class FailingProvider implements LLMProviderInterface
{
    public function parseContract(string $plainText): array
    {
        throw new \RuntimeException('provider unavailable');
    }

    public function getName(): string
    {
        return 'failing';
    }
}
