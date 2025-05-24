# Google Sheets Model Importer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aigorlaxy/google-sheets-model-importer.svg?style=flat-square)](https://packagist.org/packages/aigorlaxy/google-sheets-model-importer)
[![Total Downloads](https://img.shields.io/packagist/dt/aigorlaxy/google-sheets-model-importer.svg?style=flat-square)](https://packagist.org/packages/aigorlaxy/google-sheets-model-importer)

A simple Laravel support class to insert or update data to models from Google Sheets with just the link of the published spreadsheet. No need of Google API.

## Installation
You can install the package via composer:

```bash
composer require aigorlaxy/google-sheets-model-importer
```

## Using

1. Create a Google Spreadsheet.

2. Publish it to internet and set general access to anyone with the link can be a viewer.

3. Include the trait in your model class.

4. To use that use importFromGoogleSheets:
   ```php
   Use the trait GoogleSheetsImportable.
   Model::importFromGoogleSheets(string $spreadsheetId, array|string $sheetIds, array $columnsToSkip = [], ?string $updateColumnIndex = 'id')
   ```
5. $spreadsheetId: is the id of your google sheets. You will use your published Google Sheets link to get it.
  The link will look something like that: https://docs.google.com/spreadsheets/d/e/YOU_GOOGLE_SHEET_SPREADSHEET_ID/something_else
6. $sheetIds: If you have just one tab for that model, use the gid as a string. If you have more than one tab for the same model, use array. All models must match the same column schema to work.
  The link will look something like that: 
  https://docs.google.com/spreadsheets/d/YOUR_SPREADSHEET_ID/edit?gid=YOUR_SHEET_ID

7. $updateColumnIndex: Optionaly you can set a different primary key to check for updates. if your primary key is not default id or you want to track updates based on another column. If not set, it will assume that id column is your primary key.
8. $colunsToSkip: You can also optionaly set columns to be skipped. The app will search for columns that contains any of that strings and will skip the import for that ones. You can set a single string or an array.

9. Example of usage:
```php
$model = User::class;
$googleSpreadSheetId = '1gaLFuSnh20kggxEaasr511s15vt3olKqp9o12HenDLI3vA7pg';
$sheetId = '15144122';
```php
   Model::importFromGoogleSheets($spreadsheetId, $sheetIds);
```
  Model::updateOrCreateFromGoogleSheets($spreadsheetId, $sheetIds); // Updating and inserting new data.
  Model::getFreshTableFromGoogleSheets($spreadsheetId, $sheetIds); // Truncate the current table and inserting the new data.
```
10. Any issue or suggestions, please send me an e-mail: igor1523@gmail.com
