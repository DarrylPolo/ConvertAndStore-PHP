# Convert and Store PHP SDK

Lightweight PHP wrapper for the [Convert and Store API](https://convertandstore.com/api-docs).

It supports:

- account lookups
- tool catalog discovery
- file and folder management
- share-link creation
- direct file downloads
- single-file conversion tools
- multi-upload tools such as archive creation and PDF merge

## Requirements

- PHP 8.1+
- `ext-curl`
- `ext-json`

## Installation

```bash
composer require convertandstore/convertandstore-php
```

## Quick Start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use ConvertAndStore\Client;

$client = new Client('cas_your_api_key_here');

$me = $client->getMe();
$tools = $client->listTools();

print_r($me);
print_r($tools[0] ?? []);
```

## Base Client

```php
use ConvertAndStore\Client;

$client = new Client(
    apiKey: 'cas_your_api_key_here',
    baseUrl: 'https://convertandstore.com',
    timeout: 180
);
```

## Common Examples

### Convert one file

```php
$result = $client->convert(
    'jpg-to-png',
    __DIR__ . '/example.jpg',
    [
        'quality' => 90,
        'store_original' => false,
    ]
);

echo $result['download_url'] . PHP_EOL;
```

### Use the all-in-one image editor

```php
$result = $client->convert(
    'image-editor',
    __DIR__ . '/hero.png',
    [
        'width' => 1600,
        'height' => 900,
        'fit' => 'cover',
        'crop_enabled' => true,
        'crop_x' => 120,
        'crop_y' => 40,
        'crop_width' => 900,
        'crop_height' => 520,
        'rotate_angle' => 0,
        'grayscale' => false,
        'watermark_text' => 'Convert and Store',
        'output_format' => 'webp',
        'quality' => 86,
    ]
);
```

### Create a ZIP from multiple files

```php
$result = $client->convertMany(
    'zip-create',
    [
        __DIR__ . '/quote.pdf',
        __DIR__ . '/logo.png',
        __DIR__ . '/brief.docx',
    ],
    [
        'archive_name' => 'client-deliverables',
    ]
);
```

### List files

```php
$files = $client->listFiles([
    'visibility' => 'public',
    'sort' => 'date_desc',
]);
```

### Create a share link

```php
$share = $client->shareFile(121, true);

echo $share['share']['url'] . PHP_EOL;
```

### Download a stored file

```php
$savedTo = $client->downloadFile(121, __DIR__ . '/downloads/source.png');

echo "Saved to {$savedTo}" . PHP_EOL;
```

## Methods

### Public endpoints

- `getStatus(): array`
- `listTools(): array`
- `getTool(string $slug): array`

### Authenticated endpoints

- `getMe(): array`
- `listFiles(array $filters = []): array`
- `getFile(int|string $fileId): array`
- `listFolders(): array`
- `createFolder(string $name): array`
- `shareFile(int|string $fileId, bool $public = true): array`
- `deleteFile(int|string $fileId): array`
- `downloadFile(int|string $fileId, string $destinationPath): string`
- `convert(string $toolSlug, string $filePath, array $options = []): array`
- `convertMany(string $toolSlug, array $filePaths, array $options = []): array`

## Exceptions

- `ConvertAndStore\Exception\ApiException`
- `ConvertAndStore\Exception\AuthenticationException`
- `ConvertAndStore\Exception\NetworkException`

```php
use ConvertAndStore\Exception\ApiException;
use ConvertAndStore\Exception\AuthenticationException;
use ConvertAndStore\Exception\NetworkException;

try {
    $client->getMe();
} catch (AuthenticationException $exception) {
    // bad key, missing access, or plan restriction
} catch (NetworkException $exception) {
    // connection or timeout problem
} catch (ApiException $exception) {
    // any other API-level error
}
```

## Notes

- Single-upload tools use the API field `upload`.
- Multi-upload tools such as `zip-create`, `rar-create`, `seven-z-create`, `tar-create`, `tar-gz-create`, and `merge-pdf` use `uploads[]`.
- Conversion responses include `preview_url`, `download_url`, and `metrics` when available.
- File downloads are saved to the destination path you provide.
