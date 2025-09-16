<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Data\ApiData;
use App\Data\ApiMetaData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiResource extends JsonResource
{
    public function __construct($resource, protected string $message = 'Success.', protected int $status = 200, protected array $meta = [], protected ?array $errors = null)
    {
        parent::__construct($resource);
    }

    public static function success($resource = null, string $message = 'Success.', array $meta = []): self
    {
        return new self($resource, $message, 200, $meta);
    }

    public static function error(string $message = 'An error occurred. Please try again.', array $errors = [], int $status = 500, $resource = null, array $meta = []): self
    {
        return new self($resource, $message, $status, $meta, $errors);
    }

    public static function created($resource = null, string $message = 'Resource created successfully.', array $meta = []): self
    {
        return new self($resource, $message, 201, $meta);
    }

    public static function updated($resource = null, string $message = 'Resource updated successfully.', array $meta = []): self
    {
        return new self($resource, $message, 200, $meta);
    }

    public static function deleted(string $message = 'Resource deleted successfully.', array $meta = []): self
    {
        return new self(null, $message, 200, $meta);
    }

    public function toArray(Request $request): array
    {
        $metaData = ApiMetaData::from([
            'timestamp' => now(),
            'version' => '1.0',
            'additional' => $this->meta,
        ]);

        $apiData = ApiData::from([
            'success' => is_null($this->errors),
            'message' => $this->message,
            'data' => $this->resource,
            'meta' => $metaData,
            'errors' => $this->errors,
        ]);

        return $apiData->toArray();
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode($this->status);
    }
}
