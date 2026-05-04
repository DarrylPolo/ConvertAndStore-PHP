<?php

declare(strict_types=1);

namespace ConvertAndStore;

use ConvertAndStore\Exception\ApiException;
use ConvertAndStore\Exception\AuthenticationException;
use ConvertAndStore\Exception\NetworkException;

class Client
{
    private const MULTI_UPLOAD_TOOLS = [
        'zip-create',
        'tar-create',
        'tar-gz-create',
        'seven-z-create',
        'rar-create',
        'merge-pdf',
    ];

    private string $apiKey;
    private string $baseUrl;
    private int $timeout;
    private string $userAgent;

    public function __construct(
        string $apiKey,
        string $baseUrl = 'https://convertandstore.com',
        int $timeout = 180,
        ?string $userAgent = null
    ) {
        $apiKey = trim($apiKey);
        if ($apiKey === '') {
            throw new \InvalidArgumentException('A Convert and Store API key is required.');
        }

        $this->apiKey = $apiKey;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->timeout = max(10, $timeout);
        $this->userAgent = $userAgent !== null && trim($userAgent) !== ''
            ? trim($userAgent)
            : 'convertandstore-php/1.0';
    }

    public function getStatus(): array
    {
        return $this->requestJson('GET', '/api/v1/status', false);
    }

    public function listTools(): array
    {
        $response = $this->requestJson('GET', '/api/v1/tools', false);
        return $response['data'] ?? [];
    }

    public function getTool(string $slug): array
    {
        $response = $this->requestJson('GET', '/api/v1/tools/' . rawurlencode($slug), false);
        return $response['data'] ?? $response;
    }

    public function getMe(): array
    {
        $response = $this->requestJson('GET', '/api/v1/me');
        return $response['data'] ?? $response;
    }

    public function listFiles(array $filters = []): array
    {
        $response = $this->requestJson('GET', '/api/v1/files', true, [
            'query' => $this->sanitizeQuery($filters),
        ]);

        return $response['data'] ?? [];
    }

    public function getFile(int|string $fileId): array
    {
        $response = $this->requestJson('GET', '/api/v1/files/' . rawurlencode((string) $fileId));
        return $response['data'] ?? $response;
    }

    public function listFolders(): array
    {
        $response = $this->requestJson('GET', '/api/v1/folders');
        return $response['data'] ?? [];
    }

    public function createFolder(string $name): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new \InvalidArgumentException('Folder name cannot be empty.');
        }

        $response = $this->requestJson('POST', '/api/v1/folders', true, [
            'form' => ['name' => $name],
        ]);

        return $response['data'] ?? $response;
    }

    public function deleteFolder(int|string $folderId, string $mode = 'root', int|string|null $targetFolderId = null): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['root', 'move', 'delete'], true)) {
            throw new \InvalidArgumentException('Folder delete mode must be root, move, or delete.');
        }

        $form = ['mode' => $mode];
        if ($mode === 'move') {
            if ($targetFolderId === null || trim((string) $targetFolderId) === '') {
                throw new \InvalidArgumentException('A target folder id is required when mode is move.');
            }

            $form['target_folder_id'] = (string) $targetFolderId;
        }

        $response = $this->requestJson('POST', '/api/v1/folders/' . rawurlencode((string) $folderId) . '/delete', true, [
            'form' => $form,
        ]);

        return $response['data'] ?? $response;
    }

    public function shareFile(int|string $fileId, bool $public = true): array
    {
        $response = $this->requestJson('POST', '/api/v1/files/' . rawurlencode((string) $fileId) . '/share', true, [
            'form' => ['is_public' => $public ? '1' : '0'],
        ]);

        return $response['data'] ?? $response;
    }

    public function deleteFile(int|string $fileId): array
    {
        $response = $this->requestJson('POST', '/api/v1/files/' . rawurlencode((string) $fileId) . '/delete');
        return $response['data'] ?? $response;
    }

    public function moveFile(int|string $fileId, int|string|null $folderId = null): array
    {
        $form = [];
        if ($folderId !== null && trim((string) $folderId) !== '') {
            $form['folder_id'] = (string) $folderId;
        }

        $response = $this->requestJson('POST', '/api/v1/files/' . rawurlencode((string) $fileId) . '/move', true, [
            'form' => $form,
        ]);

        return $response['data'] ?? $response;
    }

    public function downloadFile(int|string $fileId, string $destinationPath): string
    {
        $destinationPath = trim($destinationPath);
        if ($destinationPath === '') {
            throw new \InvalidArgumentException('A destination path is required.');
        }

        $directory = dirname($destinationPath);
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Could not create the destination directory.');
        }

        $handle = fopen($destinationPath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Could not open the destination path for writing.');
        }

        $url = $this->buildUrl('/api/v1/files/' . rawurlencode((string) $fileId) . '/download');
        $curl = curl_init($url);
        if ($curl === false) {
            fclose($handle);
            throw new NetworkException('Could not initialize cURL.');
        }

        $headers = [];
        curl_setopt_array($curl, [
            CURLOPT_FILE => $handle,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_HTTPHEADER => $this->defaultHeaders(true),
            CURLOPT_HEADERFUNCTION => static function ($curlHandle, string $headerLine) use (&$headers): int {
                $length = strlen($headerLine);
                $parts = explode(':', $headerLine, 2);
                if (count($parts) === 2) {
                    $headers[strtolower(trim($parts[0]))] = trim($parts[1]);
                }
                return $length;
            },
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT => $this->userAgent,
        ]);

        $ok = curl_exec($curl);
        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        fclose($handle);

        if ($ok === false) {
            @unlink($destinationPath);
            throw new NetworkException($error !== '' ? $error : 'The download request failed.');
        }

        if ($statusCode >= 400) {
            $body = (string) @file_get_contents($destinationPath);
            @unlink($destinationPath);
            $this->throwApiException($statusCode, $body);
        }

        return $destinationPath;
    }

    public function convert(string $toolSlug, string $filePath, array $options = []): array
    {
        $this->assertReadableFile($filePath);

        $fields = $this->normalizeFields($options);
        $files = [
            'upload' => $this->curlFile($filePath),
        ];

        $response = $this->requestJson('POST', '/api/v1/tools/' . rawurlencode($toolSlug) . '/convert', true, [
            'multipart' => array_merge($fields, $files),
        ]);

        return $response['data'] ?? $response;
    }

    public function convertMany(string $toolSlug, array $filePaths, array $options = []): array
    {
        if ($filePaths === []) {
            throw new \InvalidArgumentException('At least one file path is required.');
        }

        $uploadField = in_array($toolSlug, self::MULTI_UPLOAD_TOOLS, true) ? 'uploads[]' : 'upload';
        $multipart = $this->normalizeFields($options);

        foreach (array_values($filePaths) as $index => $filePath) {
            $this->assertReadableFile((string) $filePath);
            $fieldName = $uploadField === 'uploads[]' ? 'uploads[' . $index . ']' : 'upload';
            $multipart[$fieldName] = $this->curlFile((string) $filePath);
        }

        $response = $this->requestJson('POST', '/api/v1/tools/' . rawurlencode($toolSlug) . '/convert', true, [
            'multipart' => $multipart,
        ]);

        return $response['data'] ?? $response;
    }

    private function requestJson(string $method, string $path, bool $auth = true, array $options = []): array
    {
        $response = $this->send($method, $path, $auth, $options);
        $body = trim($response['body']);

        if ($body === '') {
            throw new ApiException('The API returned an empty response.', $response['status']);
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ApiException('The API returned invalid JSON: ' . $exception->getMessage(), $response['status']);
        }

        if ($response['status'] >= 400) {
            $message = (string) ($decoded['error'] ?? $decoded['message'] ?? 'The API request failed.');
            if (in_array($response['status'], [401, 403], true)) {
                throw new AuthenticationException($message, $response['status'], $decoded);
            }

            throw new ApiException($message, $response['status'], $decoded);
        }

        return $decoded;
    }

    /**
     * @return array{status:int,body:string}
     */
    private function send(string $method, string $path, bool $auth = true, array $options = []): array
    {
        $query = $options['query'] ?? [];
        $url = $this->buildUrl($path, is_array($query) ? $query : []);
        $curl = curl_init($url);
        if ($curl === false) {
            throw new NetworkException('Could not initialize cURL.');
        }

        $headers = $this->defaultHeaders($auth);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => $this->userAgent,
        ];

        if (isset($options['form']) && is_array($options['form'])) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $curlOptions[CURLOPT_HTTPHEADER] = $headers;
            $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($options['form']);
        } elseif (isset($options['multipart']) && is_array($options['multipart'])) {
            $curlOptions[CURLOPT_POSTFIELDS] = $options['multipart'];
        }

        curl_setopt_array($curl, $curlOptions);
        $body = curl_exec($curl);
        if ($body === false) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new NetworkException($error !== '' ? $error : 'The API request failed.');
        }

        $statusCode = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return [
            'status' => $statusCode,
            'body' => (string) $body,
        ];
    }

    /**
     * @return list<string>
     */
    private function defaultHeaders(bool $auth): array
    {
        $headers = [
            'Accept: application/json',
        ];

        if ($auth) {
            $headers[] = 'Authorization: Bearer ' . $this->apiKey;
        }

        return $headers;
    }

    private function buildUrl(string $path, array $query = []): string
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');

        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    private function normalizeFields(array $values): array
    {
        $fields = [];

        foreach ($values as $key => $value) {
            if (!is_string($key) || trim($key) === '' || $value === null) {
                continue;
            }

            if (is_bool($value)) {
                $fields[$key] = $value ? '1' : '0';
                continue;
            }

            if (is_int($value) || is_float($value)) {
                $fields[$key] = (string) $value;
                continue;
            }

            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed === '') {
                    continue;
                }
                $fields[$key] = $trimmed;
            }
        }

        return $fields;
    }

    private function sanitizeQuery(array $filters): array
    {
        return $this->normalizeFields($filters);
    }

    private function assertReadableFile(string $filePath): void
    {
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \InvalidArgumentException('File not found or not readable: ' . $filePath);
        }
    }

    private function curlFile(string $filePath): \CURLFile
    {
        $mimeType = $this->detectMimeType($filePath);
        return curl_file_create($filePath, $mimeType, basename($filePath));
    }

    private function detectMimeType(string $filePath): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo !== false) {
                $mimeType = finfo_file($finfo, $filePath);
                finfo_close($finfo);
                if (is_string($mimeType) && $mimeType !== '') {
                    return $mimeType;
                }
            }
        }

        return 'application/octet-stream';
    }

    private function throwApiException(int $statusCode, string $body): never
    {
        $decoded = [];
        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                $decoded = [];
            }
        }

        $message = (string) ($decoded['error'] ?? $decoded['message'] ?? ('The API request failed with status ' . $statusCode . '.'));

        if (in_array($statusCode, [401, 403], true)) {
            throw new AuthenticationException($message, $statusCode, $decoded);
        }

        throw new ApiException($message, $statusCode, $decoded);
    }
}
