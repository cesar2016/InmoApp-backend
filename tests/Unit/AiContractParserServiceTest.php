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

    public function test_backfills_missing_fields_from_local_parser_when_provider_succeeds(): void
    {
        $text = 'CONTRATO DE LOCACIÓN '.
            'Entre: La Sra. MARIA ROSA DUTRUEL DNI: 4.965.119, con domicilio en calle San Martin N° 2404, de la ciudad de Villa Constitución, en adelante EL LOCADOR, '.
            'y el Sr. VIGNATTI, GERARDO OSVALDO DNI Nº 14.720.467, con domicilio en calle Cristobal Colon N° 866, San Cristóbal, correo electrónico vignattigerardo@gmail.com, número telefónico 3498-431196, en adelante LA LOCATARIO, '.
            'convienen en celebrar el presente Contrato de Locación. '.
            'La locación tendrá una duración de veinticuatro (24) meses, a contar desde el día 1 de Junio de 2026 hasta el día 31 de Mayo del 2028; sin opción a prórroga. '.
            'El alquiler mensual será de seiscientos cincuenta mil pesos ($650.000), pagadero por mes adelantado. '.
            'AJUSTE CUATRIMESTRAL: el importe del canon locativo se actualizará de manera cuatrimestral.';

        $service = new AiContractParserService(new PartialProvider);
        $data = $service->parseFromText($text, 'partial');

        $this->assertSame('2026-06-01', $data['contract']['start_date']);
        $this->assertSame('2028-05-31', $data['contract']['end_date']);
        $this->assertSame('650000', $data['contract']['rent_amount']);
        $this->assertSame('VIGNATTI', $data['tenant']['last_name']);
        $this->assertSame('3498431196', $data['tenant']['whatsapp']);
    }

    public function test_odt_extraction_preserves_spaces_between_styled_runs(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'odt');
        $path = $tmp.'.odt';
        rename($tmp, $path);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.
            '<office:document-content xmlns:office="http://www.w3.org/2002/07/office" '.
            'xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0">'.
            '<office:body><office:text><text:p>'.
            '<text:span>Entre:</text:span><text:s/>'.
            '<text:span>La Sra. MARIA ROSA DUTRUEL</text:span><text:s/>'.
            '<text:span>DNI:</text:span><text:s text:c="2"/>'.
            '<text:span>4.965.119</text:span>'.
            '</text:p></office:text></office:body>'.
            '</office:document-content>';

        $zip = new \ZipArchive;
        $this->assertTrue($zip->open($path, \ZipArchive::CREATE) === true);
        $zip->addFromString('content.xml', $xml);
        $zip->close();

        $service = new AiContractParserService(new FailingProvider);
        $text = $service->extractText($path, 'odt');

        $this->assertStringContainsString('Entre: La Sra. MARIA ROSA DUTRUEL DNI:  4.965.119', $text);
        $this->assertStringNotContainsString('Entre:La Sra.', $text);

        @unlink($path);
    }

    public function test_normalizes_property_type_to_valid_values(): void
    {
        $service = new AiContractParserService(new FailingProvider);

        $departamento = $service->parseFromText('Contrato de locación de un departamento sito en calle Salta 224.');
        $this->assertSame('Departamento', $departamento['property']['type']);

        $ph = $service->parseFromText('Tipo: PH. Contrato de locación.');
        $this->assertSame('Otro', $ph['property']['type']);

        $galpon = $service->parseFromText('Tipo: Galpón. Contrato de locación.');
        $this->assertSame('Otro', $galpon['property']['type']);
    }

    public function test_merge_clears_ai_contact_data_copied_to_the_wrong_party(): void
    {
        $text = 'CONTRATO DE LOCACIÓN '.
            'Entre: La Sra. MARIA ROSA DUTRUEL DNI: 4.965.119, con domicilio en calle San Martin N° 2404, de la ciudad de Villa Constitución, en adelante EL LOCADOR, '.
            'y el Sr. VIGNATTI, GERARDO OSVALDO DNI Nº 14.720.467, con domicilio en calle Cristobal Colon N° 866, San Cristóbal, correo electrónico vignattigerardo@gmail.com, número telefónico 3498-431196, en adelante LA LOCATARIO, '.
            'convienen en celebrar el presente Contrato de Locación. '.
            'La locación tendrá una duración de veinticuatro (24) meses, a contar desde el día 1 de Junio de 2026 hasta el día 31 de Mayo del 2028. '.
            'El alquiler mensual será de seiscientos cincuenta mil pesos ($650.000).';

        $service = new AiContractParserService(new ContaminatingProvider);
        $data = $service->parseFromText($text, 'contaminating');

        $this->assertSame('', $data['owner']['email']);
        $this->assertSame('', $data['owner']['whatsapp']);
        $this->assertSame('vignattigerardo@gmail.com', $data['tenant']['email']);
    }
}

class FailingProvider implements LLMProviderInterface
{
    public function parseContract(string $plainText): array
    {
        throw new \RuntimeException('provider unavailable');
    }

    public function chat(string $prompt, ?string $systemPrompt = null): string
    {
        throw new \RuntimeException('provider unavailable');
    }

    public function getName(): string
    {
        return 'failing';
    }
}

class PartialProvider implements LLMProviderInterface
{
    public function parseContract(string $plainText): array
    {
        return [
            'tenant' => [
                'first_name' => 'VIGNATTI, GERARDO OSVALDO',
                'last_name' => null,
                'dni' => '14720467',
                'whatsapp' => null,
                'email' => 'vignattigerardo@gmail.com',
            ],
            'contract' => [
                'start_date' => '',
                'end_date' => '',
                'rent_amount' => '',
                'increase_frequency_months' => 6,
            ],
        ];
    }

    public function chat(string $prompt, ?string $systemPrompt = null): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'partial';
    }
}

class ContaminatingProvider implements LLMProviderInterface
{
    public function parseContract(string $plainText): array
    {
        return [
            'tenant' => [
                'first_name' => 'GERARDO',
                'last_name' => 'OSVALDO VIGNATTI',
                'dni' => '14720467',
                'whatsapp' => '3498431196',
                'email' => 'vignattigerardo@gmail.com',
            ],
            'owner' => [
                'first_name' => 'MARIA',
                'last_name' => 'ROSA DUTRUEL',
                'dni' => '4965119',
                'whatsapp' => '3498431196',
                'email' => 'vignattigerardo@gmail.com',
            ],
            'contract' => [
                'start_date' => '', 'end_date' => '', 'rent_amount' => '', 'increase_frequency_months' => 6,
            ],
        ];
    }

    public function chat(string $prompt, ?string $systemPrompt = null): string
    {
        return '';
    }

    public function getName(): string
    {
        return 'contaminating';
    }
}
