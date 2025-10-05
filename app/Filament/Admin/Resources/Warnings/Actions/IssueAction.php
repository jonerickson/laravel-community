<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Warnings\Actions;

use App\Models\User;
use App\Models\UserWarning;
use App\Models\Warning;
use App\Notifications\Warnings\WarningIssuedNotification;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;

class IssueAction extends Action
{
    protected Closure|User|null $user = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Issue warning');
        $this->color('warning');
        $this->schema([
            Select::make('warning_id')
                ->label('Warning Type')
                ->options(Warning::active()->pluck('name', 'id'))
                ->required()
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, $set) {
                    $warning = Warning::find($state);
                    if ($warning) {
                        $set('points_preview', $warning->points);
                        $set('days_preview', $warning->days_applied);
                    }
                }),
            Textarea::make('reason')
                ->label('Specific Reason')
                ->rows(3)
                ->maxLength(1000)
                ->placeholder('Optional: provide specific details about this warning instance'),
        ]);
        $this->action(function (array $data) {
            $warning = Warning::findOrFail($data['warning_id']);

            $userWarning = UserWarning::create([
                'user_id' => $this->getUser()->id,
                'warning_id' => $warning->id,
                'reason' => $data['reason'] ?? null,
                'points_at_issue' => $this->getUser()->warning_points + $warning->points,
                'expires_at' => now()->addDays($warning->days_applied),
            ]);

            $this->getUser()->notify(new WarningIssuedNotification($userWarning));

            Notification::make()
                ->title('Warning Issued')
                ->success()
                ->body("Warning '$warning->name' has been issued to the user.")
                ->send();
        });
        $this->modalWidth(Width::Medium);
        $this->modalAlignment(Alignment::Center);
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
