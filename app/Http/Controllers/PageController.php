<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Data\PageData;
use App\Models\Page;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    use AuthorizesRequests;

    public function show(Page $page): Response
    {
        $this->authorize('view', $page);

        $page->load(['author.groups']);

        return Inertia::render('pages/show', [
            'page' => PageData::from($page),
        ]);
    }
}
