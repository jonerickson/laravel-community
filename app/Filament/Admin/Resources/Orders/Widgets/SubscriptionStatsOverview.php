<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Orders\Widgets;

use App\Models\Subscription;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use Override;

class SubscriptionStatsOverview extends StatsOverviewWidget
{
    #[Override]
    protected function getStats(): array
    {
        $activeSubscriptions = $this->calculateActiveSubscriptions();
        $subscriberChurn = $this->calculateSubscriberChurn();
        $newSubscriptions = $this->calculateNewSubscriptions();
        $trialConversions = $this->calculateTrialConversions();

        return [
            Stat::make('Active Subscriptions', Number::format($activeSubscriptions))
                ->description('Currently active')
                ->icon(Heroicon::OutlinedUsers)
                ->color('success'),

            Stat::make('Subscriber Churn', Number::percentage($subscriberChurn, 1))
                ->description('Over 30 days')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color($subscriberChurn > 5 ? 'danger' : 'success'),

            Stat::make('New Subscriptions', Number::format($newSubscriptions))
                ->description('Last 30 days')
                ->icon(Heroicon::OutlinedUserPlus)
                ->color('primary'),

            Stat::make('Trial Conversions', Number::percentage($trialConversions, 1))
                ->description('Trial to paid conversion')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('info'),
        ];
    }

    protected function calculateActiveSubscriptions(): int
    {
        return Subscription::query()
            ->where('stripe_status', 'active')
            ->orWhere('stripe_status', 'trialing')
            ->count();
    }

    protected function calculateSubscriberChurn(): float
    {
        $startOfPeriod = now()->subMonth()->startOfMonth();
        $endOfPeriod = now()->subMonth()->endOfMonth();

        $activeAtStart = Subscription::where(function ($query) use ($startOfPeriod): void {
            $query->where('created_at', '<=', $startOfPeriod)
                ->where(function ($q) use ($startOfPeriod): void {
                    $q->whereNull('ends_at')
                        ->orWhere('ends_at', '>', $startOfPeriod);
                });
        })->count();

        if ($activeAtStart === 0) {
            return 0;
        }

        $cancelled = Subscription::whereBetween('ends_at', [$startOfPeriod, $endOfPeriod])
            ->whereNotNull('ends_at')
            ->count();

        return ($cancelled / $activeAtStart) * 100;
    }

    protected function calculateNewSubscriptions(): int
    {
        return Subscription::where('created_at', '>=', now()->subMonth())
            ->count();
    }

    protected function calculateTrialConversions(): float
    {
        $trialsStarted = Subscription::whereNotNull('trial_ends_at')
            ->where('created_at', '>=', now()->subMonths(3))
            ->count();

        if ($trialsStarted === 0) {
            return 0;
        }

        $trialsConverted = Subscription::whereNotNull('trial_ends_at')
            ->where('created_at', '>=', now()->subMonths(3))
            ->where('stripe_status', 'active')
            ->where('trial_ends_at', '<', now())
            ->count();

        return ($trialsConverted / $trialsStarted) * 100;
    }
}
