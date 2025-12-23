<?php

declare(strict_types=1);

namespace App\Filament\Marketplace\Widgets;

use App\Facades\PayoutProcessor;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PayoutSetupWidget extends Widget
{
    public bool $isOnboarded = false;

    public bool $hasAccount = false;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.marketplace.widgets.payout-setup-widget';

    protected static bool $isLazy = true;

    public function mount(): void
    {
        $user = Auth::user();

        PayoutProcessor::isAccountOnboardingComplete($user);

        $this->hasAccount = $user->hasPayoutAccount();
        $this->isOnboarded = $user->isPayoutAccountOnboardingComplete();
    }

    public function startSetup(): void
    {
        $user = Auth::user();

        $onboardingUrl = PayoutProcessor::getAccountOnboardingUrl(
            $user,
            url('/marketplace'),
            url('/marketplace')
        );

        if ($onboardingUrl) {
            redirect($onboardingUrl);
        } else {
            Notification::make()
                ->title('Failed to generate onboarding link')
                ->body('Please try again or contact support.')
                ->danger()
                ->send();
        }
    }

    public function refreshStatus(): void
    {
        $user = Auth::user();

        $isComplete = PayoutProcessor::isAccountOnboardingComplete($user);

        if ($isComplete) {
            $this->isOnboarded = true;

            Notification::make()
                ->title('Payout Account Setup Complete')
                ->body('Your onboarding is successfully complete and you can now receive payouts.')
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('Onboarding Not Yet Complete')
                ->body('Please complete the payout onboarding process before you can start receiving payouts.')
                ->warning()
                ->send();
        }
    }

    public function deactivateAccount(): void
    {
        $user = Auth::user();

        if ($user->hasPayoutAccount()) {
            PayoutProcessor::deleteConnectedAccount($user);
        }

        Notification::make()
            ->title('Payout Account Disconnected')
            ->body('Your payout account has been successfully disconnected.')
            ->success()
            ->send();
    }

    public function openDashboard(): void
    {
        $user = Auth::user();

        $dashboardUrl = PayoutProcessor::getAccountDashboardUrl($user);

        if ($dashboardUrl) {
            $this->dispatch('open-url-in-new-tab', url: $dashboardUrl);
        } else {
            Notification::make()
                ->title('Unable to Access Dashboard')
                ->body('Please ensure your payout account is set up correctly.')
                ->danger()
                ->send();
        }
    }
}
