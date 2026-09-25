<?php

namespace App\Services;

use RuntimeException;
use ZipArchive;

/**
 * Minimal SpreadsheetML writer producing a real .xlsx with no extra package.
 *
 * The project has no spreadsheet dependency and adding one needs sign-off, while the brief requires an
 * Excel export. An .xlsx is a zip of XML parts, so the parts below are written directly and strings are
 * inlined, which keeps the output valid for Excel and LibreOffice without a shared-strings table.
 */
class SimpleXlsxWriter
{
    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function download(string $filename, array $headers, array $rows): never
    {
        $this->write($filename, $headers, $rows);

        exit;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    public function write(string $filename, array $headers, array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'sfp-report-').'.xlsx';

        $zip = new ZipArchive;
        if ($zip->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Could not create the Excel workbook.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->rootRels());
        $zip->addFromString('xl/workbook.xml', $this->workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
        $zip->addFromString('xl/styles.xml', $this->styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($headers, $rows));
        $zip->close();

        $contents = file_get_contents($path);
        unlink($path);

        if ($contents === false) {
            throw new RuntimeException('Could not read the generated Excel workbook.');
        }

        return $contents;
    }

    private function contentTypes(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
              <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
              <Default Extension="xml" ContentType="application/xml"/>
              <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
              <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
              <Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>
            </Types>
            XML;
    }

    private function rootRels(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>
            </Relationships>
            XML;
    }

    private function workbook(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
              <sheets>
                <sheet name="Daily Delivery" sheetId="1" r:id="rId1"/>
              </sheets>
            </workbook>
            XML;
    }

    private function workbookRels(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
              <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
              <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>
            </Relationships>
            XML;
    }

    private function styles(): string
    {
        return <<<'XML'
            <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
            <styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
              <fonts count="2">
                <font><sz val="11"/><name val="Calibri"/></font>
                <font><b/><sz val="11"/><name val="Calibri"/></font>
              </fonts>
              <fills count="3">
                <fill><patternFill patternType="none"/></fill>
                <fill><patternFill patternType="gray125"/></fill>
                <fill><patternFill patternType="solid"><fgColor rgb="FFEEF2FF"/><bgColor indexed="64"/></patternFill></fill>
              </fills>
              <borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>
              <cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>
              <cellXfs count="2">
                <xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>
                <xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>
              </cellXfs>
            </styleSheet>
            XML;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string|int|float|null>>  $rows
     */
    private function sheet(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';

        $xml .= $this->row(1, $headers, 1);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $xml .= $this->row($rowNumber, $row, 0);
            $rowNumber++;
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  list<string|int|float|null>  $values
     */
    private function row(int $rowNumber, array $values, int $style): string
    {
        $xml = '<row r="'.$rowNumber.'">';

        foreach (array_values($values) as $index => $value) {
            $reference = $this->reference($rowNumber, $index);
            $styleAttribute = $style === 1 ? ' s="1"' : '';

            if (is_int($value) || is_float($value)) {
                $xml .= '<c r="'.$reference.'"'.$styleAttribute.'><v>'.$value.'</v></c>';

                continue;
            }

            if ($value === null || $value === '') {
                $xml .= '<c r="'.$reference.'"'.$styleAttribute.'/>';

                continue;
            }

            $xml .= '<c r="'.$reference.'"'.$styleAttribute.' t="inlineStr"><is><t xml:space="preserve">'
                .htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                .'</t></is></c>';
        }

        return $xml.'</row>';
    }

    private function reference(int $rowNumber, int $columnIndex): string
    {
        $column = '';
        $index = $columnIndex;

        do {
            $column = chr(65 + ($index % 26)).$column;
            $index = intdiv($index, 26) - 1;
        } while ($index >= 0);

        return $column.$rowNumber;
    }
}
