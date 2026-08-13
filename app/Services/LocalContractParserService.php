<?php

namespace App\Services;

use Illuminate\Support\Str;

class LocalContractParserService
{
    public function parseText(string $text): array
    {
        $cleanText = $this->normalizeText($text);

        return [
            'tenant' => $this->extractTenant($cleanText),
            'owner' => $this->extractOwner($cleanText),
            'property' => $this->extractProperty($cleanText),
            'contract' => $this->extractContract($cleanText),
            'guarantors' => $this->extractGuarantors($cleanText),
        ];
    }

    private function normalizeText(string $text): string
    {
        $text = preg_replace('/\s+/', ' ', $text);
        $text = Str::limit($text, 20000, '');

        return $text;
    }

    private function extractPerson(string $text, string $roleKeyword, string $roleLabel): array
    {
        $section = '';

        // The contract structure is:
        // "Entre: [OWNER INFO] en adelante EL LOCADOR, y [TENANT INFO] en adelante LA LOCATARIO, convienen"
        // So owner info is BEFORE "en adelante LOCADOR", tenant info is BETWEEN "en adelante LOCADOR" and "en adelante LOCATARIO"

        if (stripos($roleKeyword, 'LOCADOR') !== false) {
            // Owner: extract text BEFORE "en adelante LOCADOR"
            if (preg_match('/(.*?)(?=en\s+adelante\s+(?:EL\s+)?LOCADOR)/isu', $text, $m)) {
                $section = trim($m[1] ?? '');
            }
        } else {
            // Tenant: extract text BETWEEN "en adelante LOCADOR" and "en adelante LOCATARIO"
            if (preg_match('/en\s+adelante\s+(?:EL\s+)?LOCADOR[^,]*(.*?)(?=en\s+adelante\s+(?:LA\s+)?LOCATARIO)/isu', $text, $m)) {
                $section = trim($m[1] ?? '');
            }
        }

        // Fallback: try word boundary split
        if (! $section || trim($section) === '') {
            $parts = preg_split('/\b(LOCADOR[OA]|LOCATARI[OA])\b/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);

            for ($i = 0; $i < count($parts); $i += 2) {
                $marker = $parts[$i + 1] ?? '';
                $content = $parts[$i] ?? '';

                if (preg_match("/^{$roleKeyword}$/i", $marker)) {
                    $section = $content;
                    break;
                }
            }
        }

        // If role not found via markers, fall back to full text
        if (! $section) {
            $section = $text;
        }

        // Also try to find "en adelante EL LOCADOR" / "en adelante LA LOCATARIO" pattern
        if (! $section || trim($section) === '') {
            if (preg_match("/en\s+adelante\s+({$roleKeyword}).*?(?=convienen|$)/isu", $text, $m)) {
                $section = $m[0];
            }
        }

        $patterns = [
            'first_name' => [
                "/(?:{$roleLabel}|Inquilino|Arrendatario|Propietario|Dueñ[oa]|Arrendador)[^A-Z]*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)/i",
                '/(?:la\s+Sra?\.\s+|el\s+Sr?\.\s+)([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)(?:\s*,?\s*(?:DNI|D\.N\.I|Documento))/i',
                '/(?:la\s+Sra?\.\s+|el\s+Sr?\.\s+)([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)+)(?=\s*,?\s*(?:DNI|D\.N\.I|Documento))/i',
                // Handle "APELLIDO, NOMBRE" format - put first name (group 2) in group 1
                '/(?:el\s+Sr?\.\s+|la\s+Sra?\.\s+)?[A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]+,\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)(?:\s*,?\s*(?:DNI|D\.N\.I|Documento))/i',
            ],
            'last_name' => [
                '/(?:la\s+Sra?\.\s+|el\s+Sr?\.\s+)[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)(?:\s*,?\s*(?:DNI|D\.N\.I|Documento))/i',
                // Handle "APELLIDO, NOMBRE" format - group 1 is last name
                '/(?:el\s+Sr?\.\s+|la\s+Sra?\.\s+)?([A-ZÁÉÍÓÚÑ][A-ZÁÉÍÓÚÑ\s]+),\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+/i',
            ],
            'dni' => [
                '/(?:DNI|D\.N\.I|Documento)[\s:]*(\d{1,3}(?:\.\d{3})*(?:[-\s]\w)?)/i',
                '/DNI\s+N[º°]\s*(\d{1,3}(?:\.\d{3})*)/iu',
                "/(\d{1,3}(?:\.\d{3})*(?:[-\s]\w)?)\s*,\s*en\s+adelante\s+{$roleKeyword}/i",
            ],
            'email' => [
                '/([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,})/i',
            ],
            'whatsapp' => [
                '/(?:WhatsApp|Cel|Celular|Tel[ée]f[oó]nic?o?|Tel)[\s:]*\(?((?:\+?54\s?9?\s?)?\d{2,4}[\s-]?\d{4}[\s-]?\d{4}|(?:\+?54\s?9?\s?)?\d{2,4}[\s-]?\d{5,6})/iu',
            ],
            'address' => [
                '/(?:con\s+domicilio\s+en|domiciliad[ao]\s+en|resid[ae]\s+en)\s+(.+?)(?:\s*,\s*en\s+adelante)/iu',
                '/(?:con\s+domicilio\s+en|domiciliad[ao]\s+en|resid[ae]\s+en)\s+(.+?)(?:\s*,\s*(?:correo|email|número))/iu',
                '/(?:con\s+domicilio\s+en|domiciliad[ao]\s+en|resid[ae]\s+en)\s+([^,.]+(?:,\s*[^,.]+)*)/iu',
            ],
        ];

        $result = ['first_name' => '', 'last_name' => '', 'dni' => '', 'email' => '', 'whatsapp' => '', 'address' => ''];

        foreach ($patterns as $field => $fieldPatterns) {
            foreach ($fieldPatterns as $pattern) {
                if (preg_match($pattern, $section, $matches)) {
                    $value = trim($matches[1] ?? '');
                    if ($value && ! $result[$field]) {
                        $result[$field] = $this->cleanValue($value, $field);
                    }
                }
            }
        }

        // Special handling for "APELLIDO, NOMBRE" format
        // Check if first_name looks like a last name (all caps) and last_name looks like a first name (has lowercase)
        if ($result['first_name'] && $result['last_name']) {
            $fn = $result['first_name'];
            $ln = $result['last_name'];
            // If first_name is all uppercase (likely last name) and last_name has lowercase letters (likely first name)
            if (strtoupper($fn) === $fn && preg_match('/[a-záéíóú]/', $ln)) {
                $result['first_name'] = $ln;
                $result['last_name'] = $fn;
            }
        }

        if ($result['first_name'] && ! $result['last_name']) {
            $nameParts = explode(' ', $result['first_name']);
            if (count($nameParts) > 1) {
                $result['last_name'] = array_pop($nameParts);
                $result['first_name'] = implode(' ', $nameParts);
            }
        }

        return $result;
    }

    private function extractTenant(string $text): array
    {
        return $this->extractPerson($text, 'LOCATARI[OA]', 'LOCATARIO');
    }

    private function extractOwner(string $text): array
    {
        return $this->extractPerson($text, 'LOCADOR[OA]', 'LOCADOR');
    }

    private function extractProperty(string $text): array
    {
        $result = ['street' => '', 'number' => '', 'floor' => '', 'dept' => '', 'location' => '', 'type' => ''];

        // Priority 1: "inmueble sito en..." - most specific to property
        if (preg_match('/(?:inmueble|propiedad|departamento|casa|local)\s+(?:sito|ubicad[ao])\s+en\s+([^,.]+)/i', $text, $m)) {
            $addr = trim($m[1]);
            // Remove "calle", "Av", etc. from street name and N° from number
            // Handle trailing text like "(casa)" after the number
            if (preg_match('/^(?:calle|Av|Avenida|Bv|Boulevard)\s+(.+?)\s+N?[°º]?\s*(\d+)(?:\s*\([^)]+\))?/iu', $addr, $m2)) {
                $result['street'] = trim($m2[1]);
                $result['number'] = trim($m2[2]);
            } elseif (preg_match('/^(.+?)\s+N?[°º]\s*(\d+)(?:\s*\([^)]+\))?/u', $addr, $m2)) {
                $result['street'] = trim($m2[1]);
                $result['number'] = trim($m2[2]);
            } else {
                $result['street'] = $addr;
            }
        }
        // Priority 2: "calle X N° Y" - but avoid owner/tenant addresses by checking context
        elseif (preg_match('/(?:calle|Av|Avenida|Bv|Boulevard)\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)\s+N?[°º]?\s*(\d+)/i', $text, $m)) {
            $result['street'] = trim($m[1]);
            $result['number'] = trim($m[2]);
        }

        if (preg_match('/(?:piso|Piso|P\.?)\s*(\d+)/i', $text, $m)) {
            $result['floor'] = $m[1];
        }
        if (preg_match('/(?:depto|Depto|Dpto|departamento)\s*([A-Z0-9]+)/i', $text, $m)) {
            $result['dept'] = $m[1];
        }

        if (preg_match('/(?:ciudad\s+de|localidad\s+de|de\s+la\s+ciudad\s+de)\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)/iu', $text, $m)) {
            $result['location'] = trim($m[1]);
        } elseif (preg_match('/(?:en\s+)([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+(?:\s+[A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)*)/iu', $text, $m)) {
            $result['location'] = trim($m[1]);
        }

        if (preg_match('/(?:tipo|Tipo)\s*:?\s*(Casa|Departamento|Local|PH|Oficina|Galp[oó]n|Otro)/i', $text, $m)) {
            $result['type'] = $m[1];
        } elseif (str_contains(strtolower($text), 'departamento')) {
            $result['type'] = 'Departamento';
        } elseif (str_contains(strtolower($text), 'casa')) {
            $result['type'] = 'Casa';
        } elseif (str_contains(strtolower($text), 'local')) {
            $result['type'] = 'Local';
        } elseif (str_contains(strtolower($text), 'ph')) {
            $result['type'] = 'PH';
        } else {
            $result['type'] = 'Otro';
        }

        return $result;
    }

    private function extractContract(string $text): array
    {
        $result = ['start_date' => '', 'end_date' => '', 'rent_amount' => '', 'increase_frequency_months' => 6];

        if (preg_match('/(?:desde|comienzo|inicio|vigen[ct]e\s+desde)\s+(?:el\s+)?(?:d[ií]a\s+)?(\d{1,2}\s*de\s+[a-záéíóúÁÉÍÓÚ]+\s+de(?:l)?\s+\d{4})/iu', $text, $m)) {
            $result['start_date'] = $this->parseSpanishDate($m[1]);
        } elseif (preg_match('/(?:desde\s+el\s+d[ií]a\s+)(\d{1,2}\s*de\s+[a-záéíóúÁÉÍÓÚ]+\s+de(?:l)?\s+\d{4})/iu', $text, $m)) {
            $result['start_date'] = $this->parseSpanishDate($m[1]);
        } elseif (preg_match('/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/', $text, $m)) {
            $result['start_date'] = sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        if (preg_match('/(?:hasta|fin|vencimiento|vigen[ct]e\s+hasta)\s+(?:el\s+)?(?:d[ií]a\s+)?(\d{1,2}\s*de\s+[a-záéíóúÁÉÍÓÚ]+\s+de(?:l)?\s+\d{4})/iu', $text, $m)) {
            $result['end_date'] = $this->parseSpanishDate($m[1]);
        } elseif (preg_match('/(?:hasta\s+el\s+d[ií]a\s+)(\d{1,2}\s*de\s+[a-záéíóúÁÉÍÓÚ]+\s+de(?:l)?\s+\d{4})/iu', $text, $m)) {
            $result['end_date'] = $this->parseSpanishDate($m[1]);
        } elseif (preg_match('/(?:duraci[oó]n|plazo)\s+de\s+(\d+)\s*(?:años?|meses?)/iu', $text, $m)) {
            $years = (int) $m[1];
            if ($result['start_date']) {
                $date = new \DateTime($result['start_date']);
                $date->modify("+{$years} years");
                $result['end_date'] = $date->format('Y-m-d');
            }
        }

        if (preg_match('/(?:canon|alquiler|renta|precio)\s+(?:locativo|mensual)?\s*(?:de|es)?\s*(?:PESOS?|ARS|\$|USD?)\s*([\d.,]+)/i', $text, $m)) {
            $result['rent_amount'] = str_replace(['.', ','], ['', ''], $m[1]);
        } elseif (preg_match('/(?:canon\s+locativo)\s*(?:PESOS?|ARS|\$|USD?)\s*([\d.,]+)/i', $text, $m)) {
            $result['rent_amount'] = str_replace(['.', ','], ['', ''], $m[1]);
        } elseif (preg_match('/\$\s*([\d.,]+)/', $text, $m)) {
            $result['rent_amount'] = str_replace(['.', ','], ['', ''], $m[1]);
        } elseif (preg_match('/(?:canon|alquiler|renta|precio)\s+(?:locativo|mensual)?\s*(?:de|es)?\s*(?:seiscientos|ciento|doscientos|trescientos|cuatrocientos|quinientos|setecientos|ochocientos|novecientos|mil|millón|millones|veinte|treinta|cuarenta|cincuenta|sesenta|setenta|ochenta|noventa|cincuenta\s+y|sesenta\s+y|setenta\s+y|ochenta\s+y|noventa\s+y)[^$]*?\$([\d.,]+)/iu', $text, $m)) {
            $result['rent_amount'] = str_replace(['.', ','], ['', ''], $m[1]);
        }

        if (preg_match('/(?:ajuste|aumento|actualizaci[oó]n)\s+(?:semestral|cada\s+6\s+meses)/i', $text)) {
            $result['increase_frequency_months'] = 6;
        } elseif (preg_match('/(?:ajuste|aumento|actualizaci[oó]n)\s+(?:anual|cada\s+12\s+meses)/i', $text)) {
            $result['increase_frequency_months'] = 12;
        } elseif (preg_match('/(?:ajuste|aumento|actualizaci[oó]n)\s+(?:trimestral|cada\s+3\s+meses)/i', $text)) {
            $result['increase_frequency_months'] = 3;
        } elseif (preg_match('/(?:ajuste|aumento|actualizaci[oó]n)\s+(?:cuatrimestral|cada\s+4\s+meses)/i', $text)) {
            $result['increase_frequency_months'] = 4;
        } elseif (preg_match('/(?:ajuste|aumento|actualizaci[oó]n)\s+(?:mensual|cada\s+mes)/i', $text)) {
            $result['increase_frequency_months'] = 1;
        } elseif (preg_match('/(?:cada\s+(\d+)\s+meses?)/i', $text, $m)) {
            $result['increase_frequency_months'] = (int) $m[1];
        }

        return $result;
    }

    private function extractGuarantors(string $text): array
    {
        $guarantors = [];
        $patterns = [
            '/(?:garante|fiador|aval)[^A-Z]*([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)(?:\s*,\s*DNI\s*(\d{1,3}(?:\.\d{3})*))/i',
            '/(?:la\s+Sra?\.\s+|el\s+Sr?\.\s+)([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)\s+([A-ZÁÉÍÓÚÑ][a-záéíóúñ]+)(?:\s*,\s*DNI\s*(\d{1,3}(?:\.\d{3})*))/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $m) {
                    $guarantors[] = [
                        'first_name' => trim($m[1]),
                        'last_name' => trim($m[2]),
                        'dni' => isset($m[3]) ? str_replace('.', '', $m[3]) : '',
                    ];
                }
            }
        }

        return array_slice($guarantors, 0, 5);
    }

    private function parseSpanishDate(string $dateStr): string
    {
        $months = [
            'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04',
            'mayo' => '05', 'junio' => '06', 'julio' => '07', 'agosto' => '08',
            'septiembre' => '09', 'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12',
        ];

        // Handle "1 de Junio de 2026" or "31 de Mayo del 2028"
        if (preg_match('/(\d{1,2})\s+de\s+([a-záéíóú]+)\s+de(?:l)?\s+(\d{4})/iu', $dateStr, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = $months[strtolower($m[2])] ?? '01';

            return "{$m[3]}-{$month}-{$day}";
        }

        return '';
    }

    private function cleanValue(string $value, string $field): string
    {
        $value = trim($value);
        $value = preg_replace('/\s+/', ' ', $value);

        if ($field === 'dni') {
            $value = str_replace(['.', ' ', '-'], '', $value);
            $value = preg_replace('/[^\d]/', '', $value);
        }
        if ($field === 'rent_amount') {
            $value = str_replace(['.', ','], ['', ''], $value);
        }
        if ($field === 'whatsapp') {
            $value = preg_replace('/[^\d+]/', '', $value);
        }

        return $value;
    }
}
