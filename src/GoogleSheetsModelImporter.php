<?php

namespace Aigorlaxy\GoogleSheetsModelImporter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
class GoogleSheetsModelImporter
{
    protected $model;
    protected $googleSpreadSheetId;
    protected $googleSheetId;
    public $columnsToSkip;
    public $updateColumnIndex;
    public function __construct(Model $model, string $googleSpreadSheetId, string $googleSheetId, array $columnsToSkip, string $updateColumnIndex = null)
    {
        $this->model = $model;
        $this->googleSpreadSheetId = $googleSpreadSheetId;
        $this->googleSheetId = $googleSheetId;
        $this->updateColumnIndex = $updateColumnIndex;
        $this->columnsToSkip = $columnsToSkip;
    }
    public function getFreshTable()
    {
        $this->model::truncate();
        $this->updateOrCreate();
    }

    public function updateOrCreate()
    {
        $googleSpreadSheetCsvLinks = $this->getGoogleSpreadSheetCsvLinks();
        foreach ($googleSpreadSheetCsvLinks as $googleSpreadSheetCsvLink) {
            $googleSheetsRequestBody = $this->httpGetRequest($googleSpreadSheetCsvLink);
            $googleSheetsCsvPath = $this->saveGoogleSheetsCsv($googleSheetsRequestBody);
            $csvData = $this->parseCsv($googleSheetsCsvPath);
            $this->createModel($csvData);
        }
    }
    private function getGoogleSpreadSheetCsvLinks()
    {
        $spreadSheetCsvLinks = [];
        $spreadSheetId = $this->getSpreadSheetId();
        $sheetIds = $this->getSheetId();

        foreach ($sheetIds as $sheetId) {
            $spreadSheetCsvLinks[] = 'https://docs.google.com/spreadsheets/d/' . $spreadSheetId . '/export?format=csv&gid=' . $sheetId;
        }
        return $spreadSheetCsvLinks;
    }

    private function httpGetRequest($googleSpreadSheetCsvLink)
    {
        $response = Http::get($googleSpreadSheetCsvLink);
        return $response->body();
    }

    private function saveGoogleSheetsCsv($googleSheetsRequestBody)
    {
        $filename = Str::slug(class_basename(static::class)) . '-' . now()->format('Y-m-d-H-i-s') . '.csv';

        $tempFilePath = 'temp/' . $filename;
        \Storage::put($tempFilePath, $googleSheetsRequestBody);

        return storage_path('app/' . $tempFilePath);
    }

    private function parseCsv($googleSheetsCsvPath)
    {
        $csvData = [];
        if (($handle = fopen($googleSheetsCsvPath, 'r')) !== false) {
            $headers = fgetcsv($handle, 1000, ',');
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $csvData[] = array_combine($headers, $data);
            }
            fclose($handle);
        }
        \Storage::delete('temp/' . basename($googleSheetsCsvPath));
        return $csvData;
    }

    private function createModel($csvData, $update = true)
    {
        // Get the headers from the first row of CSV data
        $headers = array_keys($csvData[0]);

        // Check if there are columns to skip, if not, use all headers
        if ($this->getColumnsToSkip()) {
            $filteredHeaders = array_filter($headers, function ($header) {
                foreach ($this->columnsToSkip as $columnToSkip) {
                    if (Str::contains($columnToSkip, '*')) {
                        return !Str::contains($header, Str::replace('*', '', $columnToSkip));
                    } else {
                        return $header != $columnToSkip;
                    }
                }
                return true;
            });
        } else {
            // No columns to skip, use all headers
            $filteredHeaders = $headers;
        }

        $createdRows = [];
        $updateColumnIndex = $this->updateColumnIndex ?? 'id';

        foreach ($csvData as $row) {
            // If filtered headers exist, filter the row by those headers
            if (count($filteredHeaders)) {
                $filteredRow = array_filter($row, function ($key) use ($filteredHeaders) {
                    return in_array($key, $filteredHeaders);
                }, ARRAY_FILTER_USE_KEY);
            } else {
                // If no headers are filtered, use the entire row
                $filteredRow = $row;
            }

            // Convert empty strings to null
            foreach ($filteredRow as $key => $value) {
                if ($value === '') {
                    $filteredRow[$key] = null;
                }
            }

            // Create or update the model instance
            $createdRows[] = $this->model::updateOrCreate([$updateColumnIndex => $filteredRow[$updateColumnIndex]], $filteredRow);
        }

        return $createdRows;
    }


    private function getSpreadSheetId()
    {
        return $this->googleSpreadSheetId;
    }
    private function getColumnsToSkip()
    {
        return $this->columnsToSkip ? (is_array($this->columnsToSkip) ? $this->columnsToSkip : [$this->columnsToSkip]) : false;
    }

    private function getSheetId()
    {
        return is_array($this->googleSheetId) ? $this->googleSheetId : [$this->googleSheetId];
    }

}
