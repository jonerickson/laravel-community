<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Exception;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NoProfanity implements ValidationRule
{
    use NormalizeStringHelpers;

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($value) || ! is_string($value)) {
            return;
        }

        if (! App::isProduction() || ! App::environment('staging')) {
            return;
        }

        $value = $this->normalize($value);

        try {
            $response = Http::timeout(5)
                ->get('https://www.purgomalum.com/service/containsprofanity', [
                    'text' => $value,
                ]);

            if ($response->successful()) {
                $containsProfanity = filter_var($response->body(), FILTER_VALIDATE_BOOLEAN);

                if ($containsProfanity) {
                    $fail('The :attribute contains inappropriate language.');
                }
            }
        } catch (Exception $exception) {
            Log::warning('Profanity check failed', [
                'attribute' => $attribute,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
