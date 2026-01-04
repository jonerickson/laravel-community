<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Data\ReadData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreReadRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class ReadController extends Controller
{
    public function __construct(
        #[CurrentUser]
        private readonly User $user,
    ) {
        //
    }

    public function __invoke(StoreReadRequest $request): ApiResource
    {
        $readable = $request->resolveReadable();

        $result = $readable->markAsRead($this->user);

        $readData = ReadData::from([
            'markedAsRead' => ! is_bool($result),
            'isReadByUser' => (bool) $result,
            'type' => $request->validated('type'),
            'id' => $request->validated('id'),
        ]);

        return new ApiResource(
            resource: $readData,
        );
    }
}
