<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum KnowledgeBaseArticleType: string implements HasDescription, HasIcon, HasLabel
{
    case Guide = 'guide';
    case Faq = 'faq';
    case Changelog = 'changelog';
    case Troubleshooting = 'troubleshooting';
    case Announcement = 'announcement';
    case Other = 'other';

    public function getLabel(): string
    {
        return match ($this) {
            KnowledgeBaseArticleType::Guide => 'Guide',
            KnowledgeBaseArticleType::Faq => 'FAQ',
            KnowledgeBaseArticleType::Changelog => 'Changelog',
            KnowledgeBaseArticleType::Troubleshooting => 'Troubleshooting',
            KnowledgeBaseArticleType::Announcement => 'Announcement',
            KnowledgeBaseArticleType::Other => 'Other',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            KnowledgeBaseArticleType::Guide => 'Long-form instructional content',
            KnowledgeBaseArticleType::Faq => 'Frequently asked questions',
            KnowledgeBaseArticleType::Changelog => 'Version updates and release notes',
            KnowledgeBaseArticleType::Troubleshooting => 'Problem-solving guides for common issues',
            KnowledgeBaseArticleType::Announcement => 'Important community updates',
            KnowledgeBaseArticleType::Other => 'Miscellaneous articles',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            KnowledgeBaseArticleType::Guide => Heroicon::OutlinedBookOpen,
            KnowledgeBaseArticleType::Faq => Heroicon::OutlinedInformationCircle,
            KnowledgeBaseArticleType::Changelog => Heroicon::OutlinedListBullet,
            KnowledgeBaseArticleType::Troubleshooting => Heroicon::OutlinedWrench,
            KnowledgeBaseArticleType::Announcement => Heroicon::OutlinedMegaphone,
            KnowledgeBaseArticleType::Other => Heroicon::OutlinedDocumentText,
        };
    }
}
