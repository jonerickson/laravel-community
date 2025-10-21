<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Pages\Actions;

use App\Models\Page;
use Closure;
use Filament\Actions\Action;
use Override;

class CodeEditorAction extends Action
{
    protected Closure|Page|null $page = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Open code editor');
        $this->color('gray');
        $this->url(fn (): string => route('admin.pages.index', [
            'page' => $this->getPage(),
        ]), shouldOpenInNewTab: true);
    }

    public static function getDefaultName(): ?string
    {
        return 'code_editor';
    }

    public function page(Closure|Page|null $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): ?Page
    {
        return $this->evaluate($this->page);
    }
}
