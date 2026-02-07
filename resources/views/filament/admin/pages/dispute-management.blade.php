<x-filament-panels::page>
    {{ $this->searchForm }}

    @if ($this->search)
        {{ $this->table }}
    @endif
</x-filament-panels::page>
