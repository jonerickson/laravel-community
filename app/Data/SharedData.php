<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class SharedData extends Data
{
    public AuthData $auth;

    /** @var AnnouncementData[] */
    public array $announcements;

    /** @var NavigationPageData[] */
    public array $navigationPages;

    public string $name;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $address = null;

    public ?string $slogan = null;

    public ?int $cartCount = null;

    public ?int $memberCount = null;

    public ?FlashData $flash = null;

    public bool $sidebarOpen;

    #[LiteralTypeScriptType('Config & { location: string }')]
    public mixed $ziggy;
}
