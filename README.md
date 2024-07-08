# Google Sheets Model Importer

[![Latest Version on Packagist](https://img.shields.io/packagist/v/aigorlaxy/google-sheets-model-importer.svg?style=flat-square)](https://packagist.org/packages/aigorlaxy/google-sheets-model-importer)
[![Total Downloads](https://img.shields.io/packagist/dt/aigorlaxy/google-sheets-model-importer.svg?style=flat-square)](https://packagist.org/packages/aigorlaxy/google-sheets-model-importer)

A simple Laravel support trait to insert or update data to models from Google Sheets with just the link of the published spreadsheet. No need of Google API.

## Installation
You can install the package via composer:

```bash
composer require aigorlaxy/google-sheets-model-importer
```

## Using

1. Create a Google Spreadsheet.

2. Publish it to internet.

3. Include the trait in your model class and use it.

```php
use Aigorlaxy\GoogleSheetsModelImporter\GoogleSheetsModelImporterTrait;

class YourModel extends Model
{
    use GoogleSheetsModelImporterTrait;
}
```

4. Set your antire spreadsheet id using your published Google Sheets link
The link will look something like that: https://docs.google.com/spreadsheets/d/e/YOU_GOOGLE_SHEET_SPREADSHEET_ID/something_else

```php
protected string $googleSpreadSheetId = 'your_google_spreadsheet_id';
```

5. Set your inside tab gid. If you have just one tab for that model, use the gid as a string. If you have more than one tab for the same model, use array. All models must match the same column schema to work.
The link will look something like that: 
https://docs.google.com/spreadsheets/d/YOUR_SPREADSHEET_ID/edit?gid=YOUR_SHEET_ID


```php
protected string|array $googleSheetId = 'your_google_sheet_id'; // or ['sheet1_gid', 'sheet2_gid']

```

6. Optionaly you can set a different primary key to check for updates. if your primary key is not default id or you want to track updates based on another column. If not set, it will assume that id column is your primary key.

```php
protected string $updateColumnIndex = 'your_column_index';

```

7. You can also optionaly set columns to be skipped. The app will search for columns that contains any of that strings and will skip the import for that ones. You can set a single string or an array.

```php
protected string|array $columnsToSkip = 'column_1'; // or ['column_1', 'column_2']

```

8. Create a new object of the model and run any of the available methods.

```php
$model = new YourModel();
$model->updateOrCreateFromGoogleSheets(); // Updating and inserting new data.
$model->getFreshTableFromGoogleSheets(); // Truncating the current table and inserting the new data.
```

9. Any issue or suggestions, please send me an e-mail: igor1523@gmail.com