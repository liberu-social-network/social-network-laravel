<x-filament-panels::page>
    <div class="space-y-6">
        <div>
            <x-filament::input.wrapper>
                <x-filament::input
                    type="text"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Search users by name or email..."
                />
            </x-filament::input.wrapper>
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
