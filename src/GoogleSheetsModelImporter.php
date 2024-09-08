<?php

namespace Aigorlaxy\GoogleSheetsModelImporter;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
class GoogleSheetsModelImporter
{
    protected $googleSpreadSheetId;
    protected $googleSheetId;
    protected $columnsToSkip;
    protected $updateColumnIndex;
    protected $model;
    public function __construct(Model $model, string $googleSpreadSheetId, string $googleSheetId, $columnsToSkip = null, $updateColumnIndex = null)
    {
        $this->model = $model;
        $this->googleSpreadSheetId = $googleSpreadSheetId;
        $this->googleSheetId = $googleSheetId;
        $this->columnsToSkip = $columnsToSkip;
        $this->updateColumnIndex = $updateColumnIndex;
    }
    public function getFreshTableFromGoogleSheets()
    {
        $this->model->truncate();
        $this->updateOrCreateFromGoogleSheets();
    }

    public function updateOrCreateFromGoogleSheets()
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
        $headers = array_keys($csvData[0]);

        if ($this->getColumnsToSkip()) {

            $filteredHeaders = array_filter($headers, function ($header) {
                foreach ($this->columnsToSkip as $columnToSkip) {
                    if (strpos($header, $columnToSkip) !== false) {
                        return false;
                    }
                }
                return true;
            });
        }

        $createdRows = [];
        $updateColumnIndex = $this->updateColumnIndex ?? 'id';

        foreach ($csvData as $row) {
            $filteredRow = array_filter($row, function ($key) use ($filteredHeaders) {
                return in_array($key, $filteredHeaders);
            }, ARRAY_FILTER_USE_KEY);

            $createdRows[] = $this->model->updateOrCreate([$updateColumnIndex => $filteredRow[$updateColumnIndex]], $filteredRow);
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
