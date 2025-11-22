<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Frontend\StoreFileRequest;
use App\Http\Resources\ApiResource;
use App\Models\File;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    use AuthorizesRequests;

    public function store(StoreFileRequest $request): ApiResource
    {
        $this->authorize('create', File::class);

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->validated('file');
        $visibility = $request->validated('visibility');

        $function = $visibility === 'public' ? 'storePublicly' : 'store';
        $path = $uploadedFile->$function('files');

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'The file could not be uploaded. Please try again.',
            ]);
        }

        $file = File::query()->create([
            'name' => $uploadedFile->getClientOriginalName(),
            'filename' => $uploadedFile->getClientOriginalName(),
            'path' => $path,
            'visibility' => $visibility,
        ]);

        return ApiResource::success(
            resource: $file,
            message: 'The file was successfully uploaded.',
        );
    }

    public function destroy(File $file): ApiResource
    {
        $this->authorize('delete', $file);

        $file->delete();

        return ApiResource::success(
            resource: $file,
            message: 'The file was deleted successfully.',
        );
    }
}
