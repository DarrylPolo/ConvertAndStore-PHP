<?php

declare(strict_types=1);

namespace ConvertAndStore\Exception;

class ApiException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $statusCode = 0,
        private readonly array $response = []
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function response(): array
    {
        return $this->response;
    }
}
