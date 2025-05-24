<?php

namespace Aigorlaxy\GoogleSheetsModelImporter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

trait GoogleSheetsImportable
{
    public static function importFromGoogleSheets(
        string $spreadsheetId,
        array|string $sheetIds,
        array $columnsToSkip = [],
        ?string $updateColumnIndex = 'id'
    ): void {
        static::updateOrCreateFromGoogleSheets($spreadsheetId, $sheetIds, $columnsToSkip, $updateColumnIndex);
    }

    public static function updateOrCreateFromGoogleSheets(
        string $spreadsheetId,
        array|string $sheetIds,
        array $columnsToSkip = [],
        ?string $updateColumnIndex = 'id'
    ): void {
        $model = new static;
        $sheetIds = is_array($sheetIds) ? $sheetIds : [$sheetIds];

        foreach ($sheetIds as $sheetId) {
            $url = "https://docs.google.com/spreadsheets/d/{$spreadsheetId}/export?format=csv&gid={$sheetId}";
            $response = Http::get($url)->body();

            $filename = Str::slug(class_basename(static::class)) . '-' . now()->format('Y-m-d-H-i-s') . '.csv';
            $path = storage_path('app/temp/' . $filename);
            Storage::put("temp/{$filename}", $response);

            $rows = self::parseCsv($path, $columnsToSkip);

            foreach ($rows as $row) {
                $model::updateOrCreate(
                    [$updateColumnIndex => $row[$updateColumnIndex] ?? null],
                    self::normalizeNulls($row)
                );
            }

            Storage::delete("temp/{$filename}");
        }
    }

    public static function getFreshTableFromGoogleSheets(
        string $spreadsheetId,
        array|string $sheetIds,
        array $columnsToSkip = [],
        ?string $updateColumnIndex = 'id'
    ): void {
        static::truncate();
        static::updateOrCreateFromGoogleSheets($spreadsheetId, $sheetIds, $columnsToSkip, $updateColumnIndex);
    }

    protected static function parseCsv(string $path, array $columnsToSkip): array
    {
        $data = [];
        if (($handle = fopen($path, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $assoc = array_combine($headers, $row);

                // Remove skipped columns
                foreach ($columnsToSkip as $skip) {
                    unset($assoc[$skip]);
                }

                $data[] = $assoc;
            }

            fclose($handle);
        }

        return $data;
    }

    protected static function normalizeNulls(array $row): array
    {
        foreach ($row as $key => $value) {
            if ($value === '') {
                $row[$key] = null;
            }
        }

        return $row;
    }
}

