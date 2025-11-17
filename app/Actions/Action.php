<?php

declare(strict_types=1);

namespace App\Actions;

use App\Contracts\Actionable;
use Throwable;

abstract class Action implements Actionable
{
    /**
     * @throws Throwable
     */
    public static function execute(...$arguments)
    {
        $class = static::class;

        $action = new $class(...$arguments);

        return $action->handle();
    }

    /**
     * @throws Throwable
     */
    protected function handle()
    {
        try {
            $response = $this();
        } catch (Throwable $throwable) {
            return $this->handleFailure($throwable);
        }

        return $response;
    }

    /**
     * @throws Throwable
     */
    protected function handleFailure(Throwable $exception)
    {
        if (method_exists($this, 'failed')) {
            return $this->failed($exception);
        }

        throw $exception;
    }
}
