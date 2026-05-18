<?php

namespace Tests\Feature;

use App\Services\ContractParserService;
use Tests\TestCase;

class ContractParserOdTTest extends TestCase
{
    public function test_od_t_extraction_basic()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'odt');
        $odtPath = $tmp.'.odt';
        rename($tmp, $odtPath);

        $zip = new \ZipArchive;
        if ($zip->open($odtPath, \ZipArchive::CREATE) !== true) {
            $this->markTestIncomplete('Could not create temporary odt file');

            return;
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<office:document-content xmlns:office="http://www.w3.org/2002/07/office">'
            .'Entre la Sra. ANA LOPEZ, DNI 12.345.678, en adelante LOCATARIA. '
            .'Entre la Sra. HECTOR PEREZ, DNI 98.765.432, en adelante LOCADORA. '
            .'Inmueble sito en CALLE FALSA 123, CIUDAD.'
            .'</office:document-content>';

        $zip->addFromString('content.xml', $xml);
        $zip->close();

        $service = new ContractParserService;
        $data = $service->parse($odtPath, 'odt');

        $this->assertIsArray($data);
        $this->assertArrayHasKey('tenant', $data);
        $this->assertArrayHasKey('property', $data);

        @unlink($odtPath);
    }
}
