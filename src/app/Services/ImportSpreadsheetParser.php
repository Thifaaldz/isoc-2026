<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use ZipArchive;

class ImportSpreadsheetParser
{
    public function participants(mixed $file): array
    {
        return $this->normalizeRows($this->rows($file), [
            'name' => ['name', 'nama', 'nama_lengkap', 'full_name'],
            'nis' => ['nis', 'nisn', 'nomor_induk'],
            'grade' => ['grade', 'kelas'],
            'organization' => ['organization', 'organisasi', 'instansi', 'lembaga'],
            'position' => ['position', 'jabatan', 'peran'],
            'gender' => ['gender', 'jenis_kelamin', 'jk'],
            'birth_date' => ['birth_date', 'tanggal_lahir', 'tgl_lahir'],
            'phone' => ['phone', 'telepon', 'kontak', 'no_hp', 'nomor_hp', 'whatsapp'],
            'email' => ['email', 'alamat_email'],
        ]);
    }

    public function tutors(mixed $file): array
    {
        return $this->normalizeRows($this->rows($file), [
            'name' => ['name', 'nama', 'nama_lengkap', 'full_name'],
            'phone' => ['phone', 'telepon', 'kontak', 'no_hp', 'nomor_hp', 'whatsapp'],
            'email' => ['email', 'alamat_email'],
            'institution' => ['institution', 'institusi', 'lembaga', 'asal_lembaga', 'sekolah'],
            'notes' => ['notes', 'catatan', 'keterangan'],
        ]);
    }

    private function rows(mixed $file): array
    {
        $path = $this->path($file);

        if (! $path || ! is_file($path)) {
            return [];
        }

        $extension = strtolower(pathinfo($this->originalName($file) ?: $path, PATHINFO_EXTENSION));

        return match ($extension) {
            'csv', 'txt' => $this->csvRows($path),
            'xlsx' => $this->xlsxRows($path),
            default => [],
        };
    }

    private function normalizeRows(array $rows, array $fieldAliases): array
    {
        if (count($rows) < 2) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rows[0]);

        $indexes = collect($fieldAliases)
            ->mapWithKeys(function (array $aliases, string $field) use ($headers): array {
                $aliases = array_map(fn (string $alias) => $this->normalizeHeader($alias), $aliases);
                $index = collect($headers)->search(fn (string $header) => in_array($header, $aliases, true));

                return [$field => $index === false ? null : $index];
            })
            ->all();

        return collect(array_slice($rows, 1))
            ->map(function (array $row) use ($indexes): array {
                $item = [];

                foreach ($indexes as $field => $index) {
                    $item[$field] = $index === null ? null : $this->cleanValue($row[$index] ?? null, $field);
                }

                return $item;
            })
            ->filter(fn (array $row) => filled($row['name'] ?? null))
            ->values()
            ->all();
    }

    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'r');

        if (! $handle) {
            return [];
        }

        $rows = [];

        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function xlsxRows(string $path): array
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return [];
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');

        if ($sheetXml === false) {
            $zip->close();

            return [];
        }

        $sharedStrings = $this->sharedStrings($zip);
        $zip->close();

        $xml = simplexml_load_string($sheetXml);

        if (! $xml) {
            return [];
        }

        $rows = [];

        foreach ($xml->sheetData->row as $rowXml) {
            $row = [];

            foreach ($rowXml->c as $cellXml) {
                $reference = (string) ($cellXml['r'] ?? '');
                $column = $this->columnIndex($reference);

                if ($column === null) {
                    $column = count($row);
                }

                $row[$column] = $this->cellValue($cellXml, $sharedStrings);
            }

            if ($row !== []) {
                ksort($row);
                $normalizedRow = [];

                for ($index = 0; $index <= max(array_keys($row)); $index++) {
                    $normalizedRow[] = $row[$index] ?? null;
                }

                $rows[] = $normalizedRow;
            }
        }

        return $rows;
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $strings = simplexml_load_string($xml);

        if (! $strings) {
            return [];
        }

        $items = [];

        foreach ($strings->si as $item) {
            if (isset($item->t)) {
                $items[] = (string) $item->t;

                continue;
            }

            $items[] = collect($item->r ?? [])->map(fn ($run) => (string) $run->t)->implode('');
        }

        return $items;
    }

    private function cellValue(\SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $type = (string) ($cell['t'] ?? '');

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        $value = (string) ($cell->v ?? '');

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }

        return $value === '' ? null : $value;
    }

    private function path(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = collect($file)->first();
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getRealPath();
        }

        if (is_string($file) && Storage::disk('public')->exists($file)) {
            return Storage::disk('public')->path($file);
        }

        return is_string($file) && is_file($file) ? $file : null;
    }

    private function originalName(mixed $file): ?string
    {
        if (is_array($file)) {
            $file = collect($file)->first();
        }

        if ($file instanceof TemporaryUploadedFile) {
            return $file->getClientOriginalName();
        }

        return is_string($file) ? basename($file) : null;
    }

    private function cleanValue(mixed $value, string $field): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if ($field === 'gender') {
            $value = strtoupper($value);

            return str_starts_with($value, 'P') ? 'P' : (str_starts_with($value, 'L') ? 'L' : $value);
        }

        if ($field === 'birth_date' && is_numeric($value)) {
            return now()->setDate(1899, 12, 30)->startOfDay()->addDays((int) $value)->toDateString();
        }

        return $value;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)->trim()->lower()->replace([' ', '-', '.'], '_')->squish()->toString();
    }

    private function columnIndex(string $reference): ?int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return null;
        }

        $index = 0;

        foreach (str_split(strtoupper($matches[1])) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
