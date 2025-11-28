<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Actions\Users\RefreshUserIntegrationAction;
use App\Models\UserIntegration;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class IntegrationsRelationManager extends RelationManager
{
    protected static string $relationship = 'integrations';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Select::make('provider')
                    ->required()
                    ->options([
                        'discord' => 'Discord',
                        'roblox' => 'Roblox',
                    ]),
                TextInput::make('provider_id')
                    ->label('Provider ID')
                    ->required()
                    ->helperText("The user's account ID for the provider."),
                TextInput::make('provider_name')
                    ->label('Provider Name')
                    ->required()
                    ->helperText("The user's account name for the provider."),
                TextInput::make('provider_email')
                    ->label('Provider Email')
                    ->default(fn () => $this->getOwnerRecord()?->email)
                    ->readOnly()
                    ->nullable()
                    ->helperText("The user's account email for the provider. This cannot be edited because it is required to be the same email as the user account that is currently being updated."),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Integrations')
            ->description("The user's connected accounts.")
            ->emptyStateHeading('No integrations')
            ->emptyStateDescription('This user has no connected accounts.')
            ->emptyStateIcon('heroicon-o-link')
            ->recordTitleAttribute('provider')
            ->emptyStateHeading('No connected accounts')
            ->columns([
                TextColumn::make('provider')
                    ->badge()
                    ->copyable()
                    ->formatStateUsing(fn ($state) => Str::ucfirst($state)),
                TextColumn::make('provider_id')
                    ->copyable()
                    ->label('ID'),
                TextColumn::make('provider_name')
                    ->copyable()
                    ->label('Name'),
                TextColumn::make('provider_email')
                    ->placeholder('No Email')
                    ->copyable()
                    ->label('Email'),
                ImageColumn::make('provider_avatar')
                    ->placeholder('No Avatar')
                    ->label('Avatar')
                    ->circular(),
                TextColumn::make('last_synced_at')
                    ->label('Last Synced')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip(),
                TextColumn::make('expires_at')
                    ->placeholder('No Expiration')
                    ->label('Expires')
                    ->dateTime()
                    ->since()
                    ->dateTimeTooltip(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('New integration')
                    ->modalHeading('New integration')
                    ->modalDescription('Add a new connected account for this user.'),
            ])
            ->recordActions([
                Action::make('refresh')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('gray')
                    ->successNotificationTitle('The integration has been successfully refreshed.')
                    ->failureNotificationTitle('We were unable to refresh the integration. Please try again later.')
                    ->visible(fn (UserIntegration $record): bool => filled($record->refresh_token) && filled($record->expires_at) && $record->expires_at->isFuture())
                    ->action(function (Action $action, UserIntegration $record): void {
                        if (RefreshUserIntegrationAction::execute($record)) {
                            $action->success();
                        } else {
                            $action->failure();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
