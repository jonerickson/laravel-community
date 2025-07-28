<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResource extends JsonResource
{
    protected string $message;

    protected array $meta;

    protected ?array $errors;

    protected int $status;

    public function __construct($resource, string $message = 'Success', int $status = 200, array $meta = [], ?array $errors = null)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->status = $status;
        $this->meta = $meta;
        $this->errors = $errors;
    }

    public static function success($resource = null, string $message = 'Success', array $meta = []): self
    {
        return new self($resource, $message, 200, $meta);
    }

    public static function error(string $message = 'An error occurred', array $errors = [], int $status = 500, $resource = null, array $meta = []): self
    {
        return new self($resource, $message, $status, $meta, $errors);
    }

    public static function created($resource = null, string $message = 'Resource created successfully', array $meta = []): self
    {
        return new self($resource, $message, 201, $meta);
    }

    public static function updated($resource = null, string $message = 'Resource updated successfully', array $meta = []): self
    {
        return new self($resource, $message, 200, $meta);
    }

    public static function deleted(string $message = 'Resource deleted successfully', array $meta = []): self
    {
        return new self(null, $message, 200, $meta);
    }

    public function toArray(Request $request): array
    {
        return [
            'success' => is_null($this->errors),
            'message' => $this->message,
            'data' => $this->resource,
            'meta' => array_merge([
                'timestamp' => now()->toISOString(),
                'version' => '1.0',
            ], $this->meta),
            'errors' => $this->errors,
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode($this->status);
    }
}
