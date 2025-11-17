<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\Level\TypeDeclarationDocblocksLevel;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withSetProviders(RectorLaravel\Set\LaravelSetProvider::class)
    ->withComposerBased(laravel: true)
    ->withCache(
        cacheDirectory: '/tmp/rector',
        cacheClass: FileCacheStorage::class
    )
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: true,
        typeDeclarations: true,
        typeDeclarationDocblocks: true,
        privatization: true,
        earlyReturn: true,
        carbon: true,
    )
    ->withSkip(Arr::mapWithKeys(TypeDeclarationDocblocksLevel::RULES, fn (string $rule) => [$rule => [__DIR__.'/app/Filament/**/*']]))
    ->withPhpSets();
