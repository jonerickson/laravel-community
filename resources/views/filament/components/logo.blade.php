@props(['dark' => false])

<div class="flex flex-row items-center gap-2">
    <img src="{{ asset('images/logo.svg') }}" alt="Mountain Interactive" @class(['h-8 w-8', 'brightness-0 invert' => $dark])/>
    <span class="font-sans text-lg font-bold">Mountain Interactive</span>
</div>
