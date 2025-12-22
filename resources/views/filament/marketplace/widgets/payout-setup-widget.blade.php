<x-filament-widgets::widget>
    <x-filament::section>
        @if ($isOnboarded)
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div class="flex-1">
                    <div class="flex items-center gap-2">
                        <x-filament::icon icon="heroicon-o-check-circle" class="sie-4 text-success-500" />
                        <div class="text-xl font-bold">Payout Account Connected</div>
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">Your Stripe Connect account is set up and ready to receive payouts!</div>
                </div>
                @if ($hasAccount)
                    <div class="flex gap-2">
                        <x-filament::button color="danger" wire:click="discountAccount">Disconnect Account</x-filament::button>
                    </div>
                @endif
            </div>
        @else
            <div class="flex items-center justify-between gap-4">
                <div class="flex-1">
                    <div class="text-xl font-bold">Setup Your Payout Account</div>
                    <div class="text-sm text-gray-600 dark:text-gray-400">
                        {{ config('app.name') }} partners with Stripe Connect to deliver instant payouts for your product sales. Complete the setup
                        to start receiving your earnings directly to your bank account!
                    </div>
                </div>
                <div class="flex gap-2">
                    @if ($hasAccount)
                        <x-filament::button color="gray" outlined wire:click="refreshStatus">Refresh Status</x-filament::button>
                    @endif

                    <x-filament::button color="primary" wire:click="startSetup">
                        {{ $hasAccount ? 'Continue Setup' : 'Start Setup' }}
                    </x-filament::button>
                </div>
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
