<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings\Actions;

use App\Actions\Warnings\IssueWarningAction;
use App\Models\User;
use App\Models\Warning;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Override;

class IssueAction extends Action
{
    protected Closure|User|null $user = null;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Issue warning');
        $this->color('warning');
        $this->modalDescription('Issue a warning to this user.');
        $this->successNotificationTitle('The warning has been successfully issued.');
        $this->schema([
            Select::make('warning_id')
                ->label('Warning Type')
                ->options(Warning::active()->pluck('name', 'id'))
                ->required()
                ->searchable(),
            Textarea::make('reason')
                ->label('Specific Reason')
                ->rows(3)
                ->maxLength(1000)
                ->placeholder('Optional: provide specific details about this warning instance'),
        ]);
        $this->action(function (array $data): void {
            $warning = Warning::findOrFail($data['warning_id']);

            IssueWarningAction::execute(
                $this->getUser(),
                $warning,
                $data['reason'] ?? null
            );
        });
    }

    public static function getDefaultName(): ?string
    {
        return 'issue';
    }

    public function user(Closure|User|null $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->evaluate($this->user);
    }
}
