<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Pages;

use App\Data\PageData;
use App\Filament\Admin\Resources\Pages\PageResource;
use App\Http\Requests\Admin\Pages\StorePageRequest;
use App\Models\Page;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class EditorController
{
    public function index(Page $page): Response
    {
        return Inertia::render('admin/pages/editor', [
            'page' => PageData::from($page),
            'defaultHtml' => PageResource::defaultHtml(),
            'defaultCss' => PageResource::defaultCss(),
            'defaultJavascript' => PageResource::defaultJavascript(),
        ]);
    }

    public function store(StorePageRequest $request, Page $page): RedirectResponse
    {
        $files = $request->collect('files');

        $page->update([
            'html_content' => data_get($files->firstWhere('language', 'html'), 'content'),
            'js_content' => data_get($files->firstWhere('language', 'javascript'), 'content'),
            'css_content' => data_get($files->firstWhere('language', 'css'), 'content'),
        ]);

        return to_route('admin.pages.index', $page)
            ->with('message', 'The page was updated successfully.');
    }
}
