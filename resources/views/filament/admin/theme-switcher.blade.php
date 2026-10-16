{{-- Filament's native theme event persists the selection and updates the dark class. --}}
<div x-data class="zy-theme-switcher" role="group" aria-label="Görünüm modu">
    <button type="button" x-on:click="$dispatch('theme-changed', 'light')"
        x-bind:aria-pressed="$store.theme === 'light'" title="Gündüz modu" aria-label="Gündüz modu">
        <x-filament::icon icon="heroicon-o-sun" class="h-5 w-5" />
        <span>Gündüz</span>
    </button>
    <button type="button" x-on:click="$dispatch('theme-changed', 'dark')"
        x-bind:aria-pressed="$store.theme === 'dark'" title="Gece modu" aria-label="Gece modu">
        <x-filament::icon icon="heroicon-o-moon" class="h-5 w-5" />
        <span>Gece</span>
    </button>
</div>
