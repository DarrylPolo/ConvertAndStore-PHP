# Convert and Store PHP SDK

Lightweight PHP wrapper for the [Convert and Store API](https://convertandstore.com/api-docs).

It supports:

- account lookups
- tool catalog discovery
- file and folder management
- private shared folders between signed-in members
- batch file rename workflows
- file moves and folder cleanup flows
- share-link creation
- direct file downloads
- single-file conversion tools
- multi-upload tools such as archive creation and PDF merge
- URL-input tools such as website screenshots
- watch-folder recipe management
- team workspace and invitation controls
- webhook endpoint management

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

### Capture a website screenshot from a URL

```php
$capture = $client->convertUrlTool(
    'website-screenshot',
    [
        'url' => 'https://example.com',
        'viewport_width' => 1440,
        'viewport_height' => 1024,
        'delay_ms' => 1200,
    ]
);

echo $capture['download_url'] . PHP_EOL;
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

### Share a folder privately with another signed-in member

```php
$result = $client->shareFolderWithMember(18, 'teammate@example.com');

print_r($result);
```

### Move a file into a folder

```php
$moved = $client->moveFile(121, 14);

echo $moved['folder_name'] . PHP_EOL;
```

### Batch rename files with numeric succession

```php
$renamed = $client->batchRenameFiles(
    [121, 122, 123],
    sequenceType: 'numeric',
    prefix: 'project-',
    suffix: '-final',
    startAt: 1,
    padding: 3
);

echo $renamed[0]['name'] . PHP_EOL; // project-001-final.png
```

### Batch rename files with alphabet succession

```php
$renamed = $client->batchRenameFiles(
    [121, 122, 123],
    sequenceType: 'alphabet',
    prefix: 'set-',
    suffix: '',
    startAt: 'A'
);

echo $renamed[1]['name'] . PHP_EOL; // set-B.png
```

### Delete a folder and move its files elsewhere

```php
$result = $client->deleteFolder(9, 'move', 14);

echo $result['files_affected'] . PHP_EOL;
```

### Create a personal watch-folder recipe

```php
$recipe = $client->createWatchFolder([
    'name' => 'Website review captures',
    'tool_slug' => 'website-screenshot',
    'store_original' => false,
    'is_active' => true,
    'options_json' => json_encode([
        'viewport_width' => 1440,
        'viewport_height' => 1024,
        'delay_ms' => 1200,
    ], JSON_THROW_ON_ERROR),
]);

print_r($recipe);
```

### Invite a teammate to an Enterprise workspace

```php
$invite = $client->inviteTeamMember('teammate@example.com', 'member');

print_r($invite);
```

### Create a webhook endpoint

```php
$webhook = $client->createWebhook(
    'Production Automations',
    'https://example.com/webhooks/convert-and-store',
    [
        'conversion.completed',
        'folder.shared',
    ]
);

print_r($webhook);
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
- `listSharedFolders(): array`
- `getSharedFolder(int|string $shareId): array`
- `createFolder(string $name): array`
- `deleteFolder(int|string $folderId, string $mode = 'root', int|string|null $targetFolderId = null): array`
- `shareFolderWithMember(int|string $folderId, string $memberIdentifier): array`
- `revokeFolderMemberShare(int|string $folderId, int|string $shareId): array`
- `moveFile(int|string $fileId, int|string|null $folderId = null): array`
- `batchRenameFiles(array $fileIds, string $sequenceType = 'numeric', string $prefix = '', string $suffix = '', int|string $startAt = 1, int $padding = 0): array`
- `shareFile(int|string $fileId, bool $public = true): array`
- `deleteFile(int|string $fileId): array`
- `downloadFile(int|string $fileId, string $destinationPath): string`
- `downloadSharedFolderFile(int|string $shareId, int|string $fileId, string $destinationPath): string`
- `getTeam(): array`
- `updateTeam(array $attributes): array`
- `inviteTeamMember(string $email, string $role = 'member'): array`
- `updateTeamMemberRole(int|string $membershipId, string $role): array`
- `removeTeamMember(int|string $membershipId): array`
- `revokeTeamInvitation(int|string $invitationId): array`
- `getWatchFolders(): array`
- `createWatchFolder(array $attributes): array`
- `updateWatchFolder(int|string $ruleId, array $attributes): array`
- `deleteWatchFolder(int|string $ruleId): array`
- `createTeamWatchFolder(array $attributes): array`
- `updateTeamWatchFolder(int|string $ruleId, array $attributes): array`
- `deleteTeamWatchFolder(int|string $ruleId): array`
- `getWebhooks(): array`
- `createWebhook(string $name, string $endpointUrl, array $events): array`
- `deleteWebhook(int|string $endpointId): array`
- `convert(string $toolSlug, string $filePath, array $options = []): array`
- `convertMany(string $toolSlug, array $filePaths, array $options = []): array`
- `convertUrlTool(string $toolSlug, array $fields): array`

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
- URL-input tools such as `website-screenshot` use `convertUrlTool()` instead of file uploads.
- Conversion responses include `preview_url`, `download_url`, and `metrics` when available.
- File downloads are saved to the destination path you provide.
- Folder deletion supports `root`, `move`, and `delete` strategies.
- Batch rename supports `numeric` and `alphabet` succession, plus optional prefix, suffix, and numeric padding.
- Team billing, team watch folders, and webhook management are Enterprise features on the live API.
