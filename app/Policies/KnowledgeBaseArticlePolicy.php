<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\KnowledgeBaseArticle;
use App\Models\User;

class KnowledgeBaseArticlePolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, KnowledgeBaseArticle $knowledgeBaseArticle): bool
    {
        return $knowledgeBaseArticle->is_published;
    }
}
