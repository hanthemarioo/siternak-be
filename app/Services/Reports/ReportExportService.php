<?php

namespace App\Services\Reports;

use Illuminate\Support\Str;
use ZipArchive;

class ReportExportService
{
    public function xlsx(array $rows, array $columns, string $fileName): string
    {
        abort_if(!class_exists(ZipArchive::class), 500, 'ZipArchive extension is required for XLSX export.');

        $path = storage_path('app/' . Str::uuid() . '-' . $fileName . '.xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheet($rows, $columns));
        $zip->close();

        return $path;
    }

    public function pdf(array $rows, array $columns, string $title): string
    {
        $path = storage_path('app/' . Str::uuid() . '-' . Str::slug($title) . '.pdf');
        file_put_contents($path, $this->pdfContent($rows, $columns, $title));

        return $path;
    }

    private function worksheet(array $rows, array $columns): string
    {
        $sheetRows = [
            $this->row(1, array_values($columns)),
        ];

        foreach ($rows as $index => $row) {
            $sheetRows[] = $this->row($index + 2, array_map(fn ($key) => $row[$key] ?? '', array_keys($columns)));
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>' . implode('', $sheetRows) . '</sheetData>'
            . '</worksheet>';
    }

    private function row(int $index, array $values): string
    {
        $cells = '';

        foreach ($values as $columnIndex => $value) {
            $cell = $this->columnName($columnIndex + 1) . $index;
            $cells .= '<c r="' . $cell . '" t="inlineStr"><is><t>' . $this->xml($value) . '</t></is></c>';
        }

        return '<row r="' . $index . '">' . $cells . '</row>';
    }

    private function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)) . $name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private function pdfContent(array $rows, array $columns, string $title): string
    {
        $lines = [$title, ''];
        $lines[] = implode(' | ', array_values($columns));

        foreach ($rows as $row) {
            $lines[] = implode(' | ', array_map(fn ($key) => (string) ($row[$key] ?? ''), array_keys($columns)));
        }

        $content = implode("\n", array_map(fn ($line) => substr($line, 0, 180), $lines));
        $stream = "BT /F1 8 Tf 30 560 Td 10 TL\n";

        foreach (explode("\n", $content) as $line) {
            $stream .= '(' . $this->pdfText($line) . ") Tj T*\n";
        }

        $stream .= 'ET';
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj',
            '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj',
            '5 0 obj << /Length ' . strlen($stream) . " >> stream\n" . $stream . "\nendstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object . "\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer << /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF";

        return $pdf;
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '</Types>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="Report" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '</Relationships>';
    }

    private function xml(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function pdfText(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }
}
