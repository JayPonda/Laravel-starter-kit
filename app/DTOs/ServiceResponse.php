<?php

namespace App\DTOs;

use Symfony\Component\HttpFoundation\Response;

class ServiceResponse
{
    private function __construct(
        private readonly array $data = [],
        private readonly ?string $error = null,
        private readonly int $statusCode = Response::HTTP_OK
    ) {}

    /**
     * Create a successful service response.
     */
    public static function ReturnData(array $data, int $statusCode = Response::HTTP_OK): self
    {
        return new self(data: $data, statusCode: $statusCode);
    }

    /**
     * Create an error service response.
     */
    public static function ReturnError(string $error, int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR, array $data = []): self
    {
        return new self(data: $data, error: $error, statusCode: $statusCode);
    }

    /**
     * Check if the response is successful (2xx status code).
     */
    public function ok(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if the response is not successful.
     */
    public function nok(): bool
    {
        return !$this->ok();
    }

    /**
     * Get the response data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the error message.
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * Get the HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
