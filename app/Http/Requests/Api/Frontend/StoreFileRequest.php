<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\Frontend;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class StoreFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, string[]>
     */
    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'max:10240'],
            'visibility' => ['required', 'in:public,private'],
        ];
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function messages(): array
    {
        return [
            'file.required' => 'Please select a file to upload.',
            'file.file' => 'The uploaded file is invalid.',
            'file.image' => 'The file must be an image.',
            'file.max' => 'The image size must not exceed 10MB.',
        ];
    }
}
