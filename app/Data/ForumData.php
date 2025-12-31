<?php

declare(strict_types=1);

namespace App\Data;

use App\Models\Forum;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
#[MapInputName(SnakeCaseMapper::class)]
class ForumData extends Data
{
    public int $id;

    public string $name;

    public string $slug;

    public ?string $description = null;

    public ?int $categoryId = null;

    public ?int $parentId = null;

    public ?string $rules = null;

    public ?string $icon = null;

    public string $color;

    public int $order;

    public bool $isActive;

    public ?int $topicsCount = null;

    public ?int $postsCount = null;

    public ?bool $isFollowedByUser = null;

    public ?int $followersCount = null;

    /** @var TopicData[] */
    public ?array $latestTopics = null;

    public ?TopicData $latestTopic = null;

    public ?ForumCategoryData $category = null;

    public ?ForumData $parent = null;

    /** @var ForumData[] */
    public ?array $children = null;

    /** @var GroupData[] */
    public ?array $groups = null;

    public ?GroupPermissionsData $groupPermissions = null;

    public ?CarbonImmutable $createdAt = null;

    public ?CarbonImmutable $updatedAt = null;

    /**
     * @return GroupPermissionsData[]
     */
    public function with(): array
    {
        return [
            'groupPermissions' => GroupPermissionsData::from(
                Forum::find($this->id)->getGroupPermissions(),
            ),
        ];
    }
}
