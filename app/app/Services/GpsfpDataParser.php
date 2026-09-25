<?php

namespace App\Services;

use RuntimeException;

class GpsfpDataParser
{
    private const SCHOOL_FILE = 'GPSFP_School_List_Anwara_Upazila.md';

    private const ITEM_FILE = 'GPSFP_Item_List_with_Value_1.md';

    private string $dataPath;

    public function __construct()
    {
        $this->dataPath = rtrim((string) config('sfp.data_path'), DIRECTORY_SEPARATOR);
    }

    public function schoolRecords(): array
    {
        $records = [];
        $serials = [];

        foreach ($this->lines(self::SCHOOL_FILE) as $line) {
            $cells = $this->cells($line, 10);
            if ($cells === null || ! $this->isNumeric($cells[0])) {
                continue;
            }

            $serial = $this->integerValue($cells[0]);
            if (isset($serials[$serial])) {
                throw new RuntimeException("Duplicate school serial {$serial} in the GPSFP source.");
            }

            $phone = $this->normalizePhone($cells[3]);
            $flags = $this->schoolFlags($cells, $serial, $phone);
            $serials[$serial] = true;
            $records[] = [
                'serial' => $serial,
                'school_name' => $cells[1],
                'teacher_name' => $cells[2],
                'teacher_phone' => $phone,
                'pupil_count' => $this->integerValue($cells[4]),
                'target_pupil_count' => $this->decimalValue($cells[5]),
                'daily_demand' => $this->integerValue($cells[6]),
                'bread_quantity' => $this->integerValue($cells[7]),
                'egg_quantity' => $this->integerValue($cells[8]),
                'banana_quantity' => $this->integerValue($cells[9]),
                'source_flags' => $flags,
                'source_payload' => ['raw_row' => $cells],
                'source_file' => self::SCHOOL_FILE,
            ];
        }

        if (count($records) !== 110) {
            throw new RuntimeException('The GPSFP school source must contain exactly 110 records.');
        }

        ksort($serials);
        if (array_keys($serials) !== range(1, 110)) {
            throw new RuntimeException('The GPSFP school source must contain serials 1 through 110.');
        }

        return $records;
    }

    public function itemRecords(): array
    {
        $records = [];
        $keys = [];

        foreach ($this->lines(self::ITEM_FILE) as $line) {
            $cells = $this->cells($line, 8);
            if ($cells === null || ! $this->isNumeric($cells[0])) {
                continue;
            }

            $itemKey = match (true) {
                str_contains($cells[1], 'বনরুটি') => 'banana_bread',
                str_contains($cells[1], 'ডিম') => 'boiled_egg',
                str_contains($cells[1], 'কলা') => 'banana',
                default => null,
            };
            if ($itemKey === null || isset($keys[$itemKey])) {
                continue;
            }

            $weight = match ($itemKey) {
                'banana_bread' => 120,
                'boiled_egg' => 60,
                'banana' => 100,
            };
            $unit = match ($itemKey) {
                'banana_bread' => 'packet',
                default => 'piece',
            };
            $keys[$itemKey] = true;
            $records[] = [
                'item_key' => $itemKey,
                'name' => $cells[1],
                'unit' => $unit,
                'weight_grams' => $weight,
                'daily_quantity' => $this->integerValue($cells[2]),
                'supply_days' => $this->integerValue($cells[3]),
                'total_quantity' => $this->integerValue($cells[4]),
                'unit_price' => $this->decimalValue($cells[5]),
                'total_value' => $this->decimalValue($cells[7]),
                'sort_order' => count($records) + 1,
                'source_file' => self::ITEM_FILE,
            ];
        }

        foreach ($this->lines(self::ITEM_FILE) as $line) {
            $cells = $this->cells($line, 5);
            if ($cells === null || ! str_contains($cells[1], 'রিলেটেড সার্ভিস')) {
                continue;
            }

            $keys['related_service'] = true;
            $records[] = [
                'item_key' => 'related_service',
                'name' => $cells[1],
                'unit' => 'service',
                'weight_grams' => null,
                'daily_quantity' => null,
                'supply_days' => null,
                'total_quantity' => $this->integerValue($cells[2]),
                'unit_price' => $this->decimalValue($cells[3]),
                'total_value' => $this->decimalValue($cells[4]),
                'sort_order' => count($records) + 1,
                'source_file' => self::ITEM_FILE,
            ];
        }

        if (count($records) !== 4 || count($keys) !== 4) {
            throw new RuntimeException('The GPSFP item source must contain three food items and related service.');
        }

        return $records;
    }

    private function schoolFlags(array $cells, int $serial, ?string $phone): array
    {
        $flags = [];
        if (str_contains($cells[1], '[কাটা]')) {
            $flags['school_name'] = 'The source marks the school name as cut off.';
        }
        if (str_contains($cells[1], '[অস্পষ্ট]')) {
            $flags['school_name'] = 'The source marks the school name as unclear.';
        }
        if (str_contains($cells[2], '[কাটা]')) {
            $flags['teacher_name'] = 'The source marks the teacher name as cut off.';
        }
        if ($phone === null) {
            $flags['teacher_phone'] = 'The source phone is not safely normalized.';
        }
        if ($serial === 85) {
            $flags['target_pupil_count'] = 'The source value differs from a recalculated 90 percent value.';
        }

        return $flags;
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = $this->digitsOnly($value);
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '88'.$digits;
        }
        if (strlen($digits) === 13 && str_starts_with($digits, '880')) {
            return $digits;
        }

        return null;
    }

    private function lines(string $filename): array
    {
        return preg_split('/\R/u', $this->read($filename)) ?: [];
    }

    private function cells(string $line, int $expectedCount): ?array
    {
        $line = trim($line);
        if ($line === '' || ! str_starts_with($line, '|')) {
            return null;
        }

        $cells = array_map('trim', explode('|', trim($line, " \t|")));

        return count($cells) === $expectedCount ? $cells : null;
    }

    private function isNumeric(string $value): bool
    {
        return preg_match('/^\d+(?:\.\d+)?$/', $this->asciiDigits(trim($value))) === 1;
    }

    private function integerValue(string $value): int
    {
        return (int) $this->numericValue($value);
    }

    private function decimalValue(string $value): string
    {
        return $this->numericValue($value);
    }

    private function numericValue(string $value): string
    {
        $normalized = preg_replace('/[^0-9.]/', '', $this->asciiDigits($value));
        if ($normalized === null || $normalized === '' || substr_count($normalized, '.') > 1 || ! is_numeric($normalized)) {
            throw new RuntimeException("Invalid numeric value in GPSFP source: {$value}");
        }

        return $normalized;
    }

    private function digitsOnly(string $value): string
    {
        return preg_replace('/\D+/', '', $this->asciiDigits($value)) ?? '';
    }

    private function asciiDigits(string $value): string
    {
        return strtr($value, [
            '০' => '0',
            '১' => '1',
            '২' => '2',
            '৩' => '3',
            '৪' => '4',
            '৫' => '5',
            '৬' => '6',
            '৭' => '7',
            '৮' => '8',
            '৯' => '9',
        ]);
    }

    private function read(string $filename): string
    {
        if ($this->dataPath === '') {
            throw new RuntimeException('The GPSFP source data path is empty.');
        }

        $path = $this->dataPath.DIRECTORY_SEPARATOR.$filename;
        if (! is_file($path)) {
            throw new RuntimeException("GPSFP source file not found: {$path}");
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Unable to read GPSFP source file: {$path}");
        }

        return $contents;
    }
}
