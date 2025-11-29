@props(['dark' => false])

<div class="flex flex-row items-center gap-2">
    <img src="{{ asset('images/logo.svg') }}" alt="{{ config('app.name') }}" @class(['size-12', 'brightness-0 invert' => $dark]) />
    <span class="font-sans text-lg font-bold">{{ config('app.name') }}</span>
</div>
