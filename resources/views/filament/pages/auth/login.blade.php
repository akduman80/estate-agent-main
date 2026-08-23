<x-filament-panels::page.simple>
    <x-slot name="subheading">
        Hesabınız yok mu?
        <a class="font-semibold text-primary-600 hover:underline" href="{{ route('register') }}">Üye olun</a>
    </x-slot>

    @if(session('status'))
        <div class="rounded-lg bg-success-50 p-3 text-sm text-success-700">{{ session('status') }}</div>
    @endif

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}
        <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
    </x-filament-panels::form>
</x-filament-panels::page.simple>
