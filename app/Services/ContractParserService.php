<?php

namespace App\Services;

use Smalot\PdfParser\Parser;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Str;

class ContractParserService
{
    /** @var string Clean text from `strings -e l` for .doc files */
    protected $stringsText = '';

    public function parse($filePath, $extension)
    {
        $this->stringsText = '';
        $text = $this->extractText($filePath, $extension);

        return [
            'tenant' => $this->extractTenant($text),
            'owner' => $this->extractOwner($text),
            'property' => $this->extractProperty($text),
            'contract' => $this->extractContractDetails($text),
            'guarantors' => $this->extractGuarantors($text),
            'raw_text_preview' => Str::limit($text, 500)
        ];
    }

    private function extractText($filePath, $extension)
    {
        $extension = strtolower($extension);

        if ($extension === 'pdf') {
            $parser = new Parser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } elseif ($extension === 'docx') {
            $phpWord = IOFactory::load($filePath, 'Word2007');
            return $this->getPhpWordText($phpWord);
        } elseif ($extension === 'doc') {
            try {
                $phpWord = IOFactory::load($filePath, 'MsDoc');
                $text = $this->getPhpWordText($phpWord);
            } catch (\Exception $e) {
                $text = '';
            }

            // For .doc, the strings extraction (UTF-16LE) gives much cleaner text.
            // We store it separately so extraction methods can use it when needed.
            $this->stringsText = $this->getStringsText($filePath);
            $text .= "\n" . $this->stringsText;
            return $text;
        }
        return '';
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
                $text .= $t . "\n";
            } elseif (method_exists($t, 'getText')) {
                // Handle cases where getText() returns a TextRun or similar
                $text .= $t->getText() . "\n";
            }
        }

        // Specific handling for Tables
        if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    $text .= $this->getRecursiveText($cell);
                }
            }
        }

        // Specific handling for TextRuns (which contain multiple Text elements)
        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $child) {
                if (method_exists($child, 'getText')) {
                    $text .= $child->getText();
                }
            }
            $text .= "\n";
        }

        return $text;
    }

    private function extractTenant($text)
    {
        // 1. Look for "Entre ... y la [NOMBRE] ... en adelante LOCATARIO"
        // Example: y la Sra. GRAMAGLIA, SILVIA MARIELA, DNI Nº 24.342.756, ... en adelante LA LOCATARIA
        $pattern1 = '/y\s+(?:la|el)\s+(?:Sra\.|Sr\.)\s+([A-Z\sÁÉÍÓÚÑ\,]+?)(?:\,|\s+DNI|[\s\,]+en\s+adelante)/i';
        // Improved version of pattern1 to not stop at first comma if it's part of the name
        $pattern1 = '/y\s+(?:la|el)\s+(?:Sra\.|Sr\.)\s+([A-Z\sÁÉÍÓÚÑ\,]{5,50}?)(?:\s+DNI|[\s\,]+en\s+adelante)/i';
        preg_match($pattern1, $text, $matches);
        $fullName = isset($matches[1]) ? trim($matches[1], " \t\n\r\0\x0B,") : null;

        if (!$fullName) {
            // Fallback to more generic pattern
            $pattern2 = '/(?:LOCATARIO|INQUILINO|LOCATARIA)[\s\:]+([A-Z\sÁÉÍÓÚÑ]+?)(?:\s+DNI|[\s\,]+|$)/i';
            preg_match($pattern2, $text, $matches);
            $fullName = isset($matches[1]) ? trim($matches[1]) : null;
        }

        // Try to Split Name (handle "LASTNAME, FIRSTNAME" or "FIRSTNAME LASTNAME")
        $firstName = null;
        $lastName = null;
        if ($fullName) {
            $fullName = preg_replace('/,?\s*(?:argentino|argentina|soltero|soltera|casado|casada|divorciado|divorciada)/i', '', $fullName);
            if (strpos($fullName, ',') !== false) {
                $parts = array_map('trim', explode(',', $fullName));
                $lastName = $parts[0];
                $firstName = $parts[1] ?? '';
            } else {
                $parts = explode(' ', $fullName);
                if (count($parts) > 1) {
                    $lastName = array_pop($parts);
                    $firstName = implode(' ', $parts);
                } else {
                    $firstName = $fullName;
                }
            }
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'dni' => $this->findFirstDniNear($text, $fullName ?: 'LOCATARIA', true),
            'email' => $this->findEmailNear($text, $fullName ?: 'LOCATARIA'),
            'whatsapp' => $this->findPhoneNear($text, $fullName ?: 'LOCATARIA') ?: $this->findGlobalPhone($text, 'locataria'),
            'address' => $this->findAddressNear($text, $fullName ?: 'LOCATARIA')
        ];
    }

    private function extractOwner($text)
    {
        // 1. Look for "Entre ... [NOMBRE] ... en adelante LOCADOR"
        // Example: Entre la Sra. HILDA CARINA GRAMAGLIA, ... en adelante denominado LOCADOR
        $pattern1 = '/Entre\s+(?:la|el)\s+(?:Sra\.|Sr\.)\s+([A-Z\sÁÉÍÓÚÑ\,]{5,50}?)(?:\s+DNI|[\s\,]+en\s+adelante)/i';
        preg_match($pattern1, $text, $matches);
        $fullName = isset($matches[1]) ? trim($matches[1], " \t\n\r\0\x0B,") : null;

        if (!$fullName) {
            $pattern2 = '/(?:LOCADOR|PROPIETARIO|LOCADORA)[\s\:]+([A-Z\sÁÉÍÓÚÑ]+?)(?:\s+DNI|[\s\,]+|$)/i';
            preg_match($pattern2, $text, $matches);
            $fullName = isset($matches[1]) ? trim($matches[1]) : null;
        }

        $firstName = null;
        $lastName = null;
        if ($fullName) {
            $fullName = preg_replace('/,?\s*(?:argentino|argentina|soltero|soltera|casado|casada|divorciado|divorciada)/i', '', $fullName);
            if (strpos($fullName, ',') !== false) {
                $parts = array_map('trim', explode(',', $fullName));
                $lastName = $parts[0];
                $firstName = $parts[1] ?? '';
            } else {
                $parts = explode(' ', $fullName);
                if (count($parts) > 1) {
                    $lastName = array_pop($parts);
                    $firstName = implode(' ', $parts);
                }
            }
        }

        return [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'dni' => $this->findFirstDniNear($text, $fullName ?: 'LOCADORA', true),
            'email' => $this->findEmailNear($text, $fullName ?: 'LOCADORA'),
            'whatsapp' => $this->findPhoneNear($text, $fullName ?: 'LOCADORA') ?: $this->findGlobalPhone($text, 'locadora'),
            'address' => $this->findAddressNear($text, $fullName ?: 'LOCADORA')
        ];
    }

    private function extractProperty($text)
    {
        // Prioritize "inmueble sito en" which is more specific to the rented object
        // Example: inmueble sito en calle Salta N° 224, de la ciudad de San Cristóbal, provincia de Santa Fe
        $pattern = '/(?:inmueble\s+sito\s+en|inmueble\s+ubicado\s+en)[\s\:]+(.*?(?:\,|\.|$))/i';
        if (!preg_match($pattern, $text, $matches)) {
            $patternFallback = '/(?:ubicado\s+en|domicilio\s+en)[\s\:]+(.*?(?:\,|\.|$))/i';
            preg_match($patternFallback, $text, $matches);
        }

        $fullAddress = isset($matches[1]) ? trim($matches[1], " \t\n\r\0\x0B,.") : null;

        $street = $fullAddress;
        $number = null;
        if ($fullAddress && preg_match('/^(.*?)\s+N°?\s*(\d+)/i', $fullAddress, $addrMatches)) {
            $street = trim($addrMatches[1]);
            $number = $addrMatches[2];
        }

        // Extract Location (City)
        $location = $this->findLocation($text);
        // Look for location near the property mention
        $propPos = strpos($text, 'inmueble');
        if ($propPos !== false) {
            $sub = substr($text, $propPos, 300);
            if (preg_match('/(?:ciudad|localidad)\s+de\s+([A-ZÁÉÍÓÚÑ\s]+?)(?:\,|\.|$)/i', $sub, $locMatches)) {
                $location = trim($locMatches[1]);
            }
        }

        return [
            'street' => $street,
            'number' => $number,
            'location' => $location,
            'type' => 'Otro'
        ];
    }

    private function extractContractDetails($text)
    {
        // Example: a contar desde el día 1 de abril de 2026 hasta el día 31 de marzo de 2027
        $startDate = null;
        $endDate = null;

        // Try specialized pattern for "desde ... hasta"
        $datePattern = '/desde\s+el\s+día\s+([\d\w\sÁÉÍÓÚÑ\/]+?)\s+hasta\s+el\s+día\s+([\d\w\sÁÉÍÓÚÑ\/]+?)(?:\;|\.|$)/i';
        if (preg_match($datePattern, $text, $dateMatches)) {
            $startDateStr = $this->parseSpanishDate($dateMatches[1]);
            $endDateStr = $this->parseSpanishDate($dateMatches[2]);
            $startDate = $this->formatDateForDb($startDateStr);
            $endDate = $this->formatDateForDb($endDateStr);
        }

        // Fallback to generic date search
        if (!$startDate) {
            preg_match_all('/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/', $text, $dates);
            $startDate = isset($dates[0][0]) ? $this->formatDateForDb($dates[0][0]) : null;
            $endDate = isset($dates[0][1]) ? $this->formatDateForDb($dates[0][1]) : null;
        }

        // Rent Amount
        // Look specifically near keywords to avoid matching DNI or other numbers
        $rentKeywords = ['canon locativo', 'alquiler mensual', 'precio', 'suma de', 'valor locativo', 'monto'];
        $amount = null;

        foreach ($rentKeywords as $kw) {
            $pos = stripos($text, $kw);
            if ($pos !== false) {
                // Focus on 100 chars after the keyword
                $sub = substr($text, $pos, 100);
                if (preg_match('/(?:\$|PESOS)\s*([\d\.]+)(?:,\d{2})?/i', $sub, $m)) {
                    $val = str_replace('.', '', $m[1]);
                    if ((int) $val > 1000) {
                        $amount = $val;
                        break;
                    }
                }
                // Also check without currency sign if it's very close
                if (preg_match('/([\d\.]+)(?:,\d{2})?/', $sub, $m)) {
                    $val = str_replace('.', '', $m[1]);
                    if ((int) $val > 1000 && (int) $val < 5000000) { // Safety reasonable range for rent
                        $amount = $val;
                        break;
                    }
                }
            }
        }

        // Fallback to global search if not found near keywords
        if (!$amount) {
            $patternPrice = '/(?:\$|PESOS)\s*([\d\.]+(?:,\d{2})?)/i';
            preg_match_all($patternPrice, $text, $priceMatches);
            if (!empty($priceMatches[1])) {
                foreach ($priceMatches[1] as $price) {
                    $val = str_replace('.', '', $price);
                    $val = explode(',', $val)[0];
                    if ((int) $val > 1000 && (int) $val < 5000000) { // Standard rent range
                        $amount = $val;
                        break;
                    }
                }
            }
        }

        return [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rent_amount' => $amount,
            'increase_frequency_months' => $this->extractIncreaseFrequency($text)
        ];
    }

    private function extractGuarantors($text)
    {
        $guarantors = [];
        // Look for chunks starting with "FIADOR" or "GARANTE"
        $pattern = '/(?:FIADOR|GARANTE)[\s\:]+([A-Z\sÁÉÍÓÚÑ]+?)(?:\s+DNI|[\s\,]+|$)/i';
        preg_match_all($pattern, $text, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $index => $name) {
                if (strlen(trim($name)) < 3)
                    continue;
                $guarantors[] = [
                    'first_name' => trim($name),
                    'last_name' => '',
                    'dni' => null // Would need more complex logic to find specific DNIs
                ];
            }
        }
        return $guarantors;
    }

    // Helper Utils
    private function findFirstDniNear($text, $keyword, $preferForward = false)
    {
        $pos = stripos($text, $keyword);
        if ($pos === false)
            return null;

        // Look around the keyword
        if ($preferForward) {
            // Focus more on what's after the name in legal docs
            $sub = substr($text, $pos, 300);
        } else {
            $start = max(0, $pos - 200);
            $sub = substr($text, $start, 400);
        }

        // Improved DNI regex to handle dots and potentially ignore very large numbers (like CUIT)
        if (preg_match_all('/\d{1,2}\.?\d{3}\.?\d{3}/', $sub, $matches)) {
            // Return the one closest to the name (pos 0 in the substring if preferForward)
            return str_replace(['.', ' '], '', $matches[0][0]);
        }
        return null;
    }

    private function findEmailNear($text, $target)
    {
        $sub = $this->getChunkNear($text, $target);
        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $sub, $matches)) {
            return $matches[0];
        }
        // Fallback: search across the full document
        if (preg_match('/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,4}/i', $text, $matches)) {
            return $matches[0];
        }
        return null;
    }

    private function findPhoneNear($text, $target)
    {
        $sub = $this->getChunkNear($text, $target);
        // Look for common phone patterns in Argentina
        // 03408-15443322, 3408 676225, (03408) 15443322
        $pattern = '/(?:tel|cel|teléfono|telefono|celular)[\s\:]*([\d\-\(\)\s]{8,20})/i';
        if (preg_match($pattern, $sub, $matches)) {
            return preg_replace('/[^\d]/', '', $matches[1]);
        }

        // Fallback: look for 8-12 digits with optional dashes/spaces
        if (preg_match('/(?:\s|^)(\d{2,4}[\s\-]\d{6,8}|\d{8,12})(?:\s|$)/', $sub, $matches)) {
            return preg_replace('/[^\d]/', '', $matches[1]);
        }
        return null;
    }

    private function findAddressNear($text, $target)
    {
        // Use clean strings-based chunk (wider window)
        $sub = $this->getChunkNear($text, $target, 1000);

        // Collapse newlines so split-across-lines text is matched as one
        $sub = preg_replace('/\n\s*/', ' ', $sub);

        // Match "domicilio [especial] en calle STREET N[º°] NUMBER"
        // Stop at: "de la localidad", "de la ciudad", "Provincia", "en adelante", or comma
        $pattern = '/domicilio[\s\w]{0,20}?\s+en\s+(calle\s+[\w\s\xC0-\xFF]+?N[º°]?\s*\d[\w\/]*)\s*(?:de la|Provincia|en\s+adelante|,)/i';
        if (preg_match($pattern, $sub, $matches)) {
            return trim($matches[1]);
        }

        // Fallback: "calle X N 1785" or "calle X S/N" (N without º is common in strings -e l output)
        if (preg_match('/calle\s+([\w\s\xC0-\xFF]+?)\s+(?:N\s+(\d[\w\/]*)|S\/N)/i', $sub, $matches)) {
            $number = isset($matches[2]) && $matches[2] ? 'N° ' . $matches[2] : 'S/N';
            return 'calle ' . trim($matches[1]) . ' ' . $number;
        }

        return null;
    }

    private function getChunkNear($text, $target, $length = 700)
    {
        // Prefer clean strings text for .doc files if available
        $searchIn = $this->stringsText ?: $text;

        $pos = stripos($searchIn, $target);
        if ($pos === false) {
            // Fallback to main text
            $pos = stripos($text, $target);
            $searchIn = $text;
        }
        if ($pos === false)
            return substr($searchIn, 0, $length);

        $start = max(0, $pos - 150);
        return substr($searchIn, $start, $length);
    }

    private function getStringsText($filePath)
    {
        // Try both standard and UTF-16LE strings extraction
        $cmd1 = 'strings "' . $filePath . '"';
        $cmd2 = 'strings -e l "' . $filePath . '"';

        $out1 = shell_exec($cmd1) ?: '';
        $out2 = shell_exec($cmd2) ?: '';

        return $out1 . "\n" . $out2;
    }

    private function parseSpanishDate($dateStr)
    {
        $months = [
            'enero' => '01',
            'febrero' => '02',
            'marzo' => '03',
            'abril' => '04',
            'mayo' => '05',
            'junio' => '06',
            'julio' => '07',
            'agosto' => '08',
            'septiembre' => '09',
            'octubre' => '10',
            'noviembre' => '11',
            'diciembre' => '12'
        ];

        $dateStr = trim(strtolower($dateStr));
        if (preg_match('/(\d{1,2})\s+de\s+([a-z]+)\s+de\s+(\d{4})/i', $dateStr, $m)) {
            $day = str_pad($m[1], 2, '0', STR_PAD_LEFT);
            $month = $months[$m[2]] ?? '01';
            $year = $m[3];
            return "$day/$month/$year";
        }
        return $dateStr;
    }

    private function findLocation($text)
    {
        // Very basic, look for city names or "Ciudad de..."
        if (preg_match('/Rosario/i', $text))
            return 'Rosario';
        if (preg_match('/Buenos Aires/i', $text))
            return 'Buenos Aires';
        if (preg_match('/San Cristóbal/i', $text))
            return 'San Cristóbal';
        if (preg_match('/Huanqueros/i', $text))
            return 'Huanqueros';
        return 'Ciudad';
    }

    private function extractIncreaseFrequency($text)
    {
        // Look for words like cuatrimestral, semestral, trimestral
        if (preg_match('/cuatrimestral/i', $text))
            return 4;
        if (preg_match('/semestral/i', $text))
            return 6;
        if (preg_match('/trimestral/i', $text))
            return 3;
        if (preg_match('/anual/i', $text))
            return 12;
        if (preg_match('/cada\s+([0-9]{1,2})\s+meses/i', $text, $m))
            return (int) $m[1];

        return 6; // Default
    }

    private function findGlobalPhone($text, $type)
    {
        // Search at the end of the document where "Domicilios y notificaciones" usually is
        $end = substr($text, -1500);

        // Pattern: locadora: (3408577336) or locataria: 3408577336
        $pattern = '/' . preg_quote($type, '/') . '[\s\:\(\)]+([\d\-\s]{8,15})/i';
        if (preg_match($pattern, $end, $matches)) {
            return preg_replace('/[^\d]/', '', $matches[1]);
        }

        // Fallback: look for exactly 10 digits near the label
        $pos = stripos($end, $type);
        if ($pos !== false) {
            $sub = substr($end, $pos, 100);
            if (preg_match('/(\d{10})/', $sub, $m)) {
                return $m[1];
            }
        }
        return null;
    }

    private function formatDateForDb($dateStr)
    {
        // Try to convert DD/MM/YYYY to YYYY-MM-DD
        $parts = preg_split('/[\/\-]/', $dateStr);
        if (count($parts) === 3) {
            $day = str_pad($parts[0], 2, '0', STR_PAD_LEFT);
            $month = str_pad($parts[1], 2, '0', STR_PAD_LEFT);
            $year = $parts[2];
            if (strlen($year) === 2)
                $year = '20' . $year;
            return "$year-$month-$day";
        }
        return $dateStr;
    }
}
