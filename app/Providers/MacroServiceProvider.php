<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use RuntimeException;

class MacroServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Builder::macro('countOffset', function () {
            $offset = $this->getOffset();
            $limit = $this->getLimit();

            if (is_null($limit)) {
                return $this->count();
            }

            $this->offset = null;
            $this->limit = null;

            return (int) $this->selectRaw('LEAST(?, GREATEST(0, COUNT(*) - ?)) as offset_count', [$limit, $offset ?? 0])->value('offset_count');
        });

        Request::macro('fingerprintId', function (): ?string {
            /** @var \Illuminate\Http\Request $request */
            $request = app('request');

            return $request->header('X-Fingerprint-ID')
                ?? $request->cookie('fingerprint_id');
        });

        Str::macro('unique', function (string $string, string $table, string $column = 'id', mixed $fallback = null, ?string $connection = null, bool $throw = true, int $maxAttempts = 5): string {
            $connection = Model::resolveConnection($connection);

            $candidate = $string;
            $attempts = 0;

            while ($connection->table($table)->where($column, $candidate)->exists()) {
                $attempts++;

                if ($throw && $attempts > $maxAttempts) {
                    throw new RuntimeException(sprintf('Unable to generate unique value for [%s.%s.%s] after %d attempts.', $string, $table, $column, $maxAttempts));
                }

                if (! is_null($fallback)) {
                    $candidate = (string) value($fallback, Str::of($string));
                } else {
                    $candidate = $string.'-'.Str::random(6);
                }
            }

            if ($candidate === '' || $candidate === '0') {
                return $string;
            }

            return $candidate;
        });

        /** @phpstan-ignore-next-line */
        Stringable::macro('unique', fn (string $table, string $column = 'id', mixed $fallback = null, ?string $connection = null, bool $throw = true, int $maxAttempts = 5): Stringable => new Stringable(Str::unique($this->value, $table, $column, $fallback, $connection, $throw, $maxAttempts)));
    }
}
