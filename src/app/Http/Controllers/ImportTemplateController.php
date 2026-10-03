<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ImportTemplateController
{
    public function participants(): BinaryFileResponse
    {
        return $this->download(
            'template-import-peserta.xlsx',
            'Template Peserta',
            [
                ['name', 'nisn', 'nik', 'grade', 'organization', 'position', 'gender', 'birth_date', 'phone', 'email'],
                ['Peserta Sekolah', '0012345678', '3171011402100001', 'X', 'SMK Negeri 2 Jakarta', 'Siswa', 'L', '2010-02-14', '081322220000', 'peserta.sekolah@isoc.id'],
                ['Peserta Umum', '', '3171010101900001', '', 'Komunitas Digital', 'Relawan', 'P', '2000-05-02', '081322220001', 'peserta.umum@isoc.id'],
            ],
        );
    }

    public function tutors(): BinaryFileResponse
    {
        return $this->download(
            'template-import-tutor.xlsx',
            'Template Tutor',
            [
                ['name', 'phone', 'email', 'institution', 'nik', 'npwp', 'bank_name', 'bank_account_number', 'notes'],
                ['Tutor ISOC Utama', '081311110000', 'tutor.utama@isoc.id', 'RTIK Jakarta', '3171010101850001', '12.345.678.9-012.000', 'BRI', '0123456789', 'Fasilitator utama'],
                ['Fasilitator Sekolah', '081311110001', 'fasilitator.sekolah@isoc.id', 'SMK Negeri 2 Jakarta', '3171010101900002', '', 'BNI', '9876543210', 'Pendamping kelas'],
            ],
        );
    }

    public function rab(): BinaryFileResponse
    {
        return $this->download(
            'template-rab.xlsx',
            'Template RAB',
            [
                ['category', 'description', 'quantity', 'unit', 'unit_price', 'amount', 'vendor', 'receipt_number', 'notes'],
                ['konsumsi', 'Konsumsi peserta dan tutor', '110', 'paket', '25000', '2750000', 'Kantin Sekolah', 'INV-KON-001', 'Snack dan air mineral'],
                ['banner_publikasi', 'Banner kegiatan', '2', 'pcs', '250000', '500000', 'Digital Print', 'INV-BNR-001', 'Banner ruang pelatihan'],
                ['transportasi', 'Transport tutor/fasilitator', '3', 'orang', '150000', '450000', 'RTIK Local', 'TRP-001', 'Transport lokal'],
                ['dokumentasi', 'Dokumentasi foto dan video', '1', 'paket', '750000', '750000', 'Tim Dokumentasi', 'DOC-001', 'Foto sesi dan video slogan'],
            ],
        );
    }

    private function download(string $filename, string $sheetName, array $rows): BinaryFileResponse
    {
        abort_unless(class_exists(ZipArchive::class), Response::HTTP_INTERNAL_SERVER_ERROR, 'Ekstensi ZIP PHP belum aktif.');

        $path = tempnam(sys_get_temp_dir(), 'isoc-template-');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Gagal membuat template Excel.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('docProps/app.xml', $this->appXml($sheetName));
        $zip->addFromString('docProps/core.xml', $this->coreXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $this->worksheetXml($rows));
        $zip->close();

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    private function worksheetXml(array $rows): string
    {
        $sheetData = collect($rows)
            ->values()
            ->map(function (array $row, int $rowIndex): string {
                $cells = collect($row)
                    ->values()
                    ->map(function ($value, int $columnIndex) use ($rowIndex): string {
                        $cell = $this->columnName($columnIndex + 1) . ($rowIndex + 1);
                        $style = $rowIndex === 0 ? ' s="1"' : '';

                        return '<c r="' . $cell . '" t="inlineStr"' . $style . '><is><t>' . $this->escape((string) $value) . '</t></is></c>';
                    })
                    ->implode('');

                return '<row r="' . ($rowIndex + 1) . '">' . $cells . '</row>';
            })
            ->implode('');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols><col min="1" max="10" width="24" customWidth="1"/></cols>'
            . '<sheetData>' . $sheetData . '</sheetData>'
            . '</worksheet>';
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            . '<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            . '<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escape($sheetName) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            . '</styleSheet>';
    }

    private function appXml(string $sheetName): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            . '<Application>sena</Application><TitlesOfParts><vt:vector size="1" baseType="lpstr"><vt:lpstr>' . $this->escape($sheetName) . '</vt:lpstr></vt:vector></TitlesOfParts>'
            . '</Properties>';
    }

    private function coreXml(): string
    {
        $now = now()->toIso8601String();

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            . '<dc:creator>sena</dc:creator><cp:lastModifiedBy>sena</cp:lastModifiedBy>'
            . '<dcterms:created xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:created><dcterms:modified xsi:type="dcterms:W3CDTF">' . $now . '</dcterms:modified>'
            . '</cp:coreProperties>';
    }

    private function columnName(int $number): string
    {
        $name = '';

        while ($number > 0) {
            $number--;
            $name = chr(65 + ($number % 26)) . $name;
            $number = intdiv($number, 26);
        }

        return $name;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
