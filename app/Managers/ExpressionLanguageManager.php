<?php

declare(strict_types=1);

namespace App\Managers;

use Illuminate\Support\Facades\Log;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Throwable;

class ExpressionLanguageManager
{
    private readonly ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage;
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp('data_get'));
    }

    public function evaluate(Expression|array|string $expression, array $values = []): mixed
    {
        if (is_array($expression)) {
            return $this->processArray('evaluate', $expression, $values);
        }

        try {
            return $this->expressionLanguage->evaluate($expression, $values);
        } catch (Throwable $throwable) {
            Log::error('Failed evaluating expression language', [
                'message' => $throwable->getMessage(),
                'values' => $values,
            ]);
        }

        return null;
    }

    public function lint(Expression|array|string $expression, array $values = []): bool|string
    {
        if (is_array($expression)) {
            $this->processArray('lint', $expression, $values);

            return true;
        }

        $this->expressionLanguage->lint($expression, ['event']);

        return true;
    }

    private function processArray(string $operation, array $data, array $values = []): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (is_string($value) && str_starts_with($value, 'expr:')) {
                $expression = substr($value, 5);

                $result[$key] = $operation === 'evaluate' ? $this->evaluate($expression, $values) : $this->lint($expression, $values);

                continue;
            }

            if (is_array($value)) {
                $result[$key] = $this->processArray($operation, $value, $values);

                continue;
            }

            if (is_object($value)) {
                $result[$key] = $this->processArray($operation, (array) $value, $values);

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }
}
