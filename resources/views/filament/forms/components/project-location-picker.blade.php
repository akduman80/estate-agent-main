@php
    $statePath = $getStatePath();
    $parentStatePath = \Illuminate\Support\Str::beforeLast($statePath, '.');
    $latitudeStatePath = $parentStatePath.'.latitude';
    $longitudeStatePath = $parentStatePath.'.longitude';
    $record = $getRecord();
    $latitude = $record?->latitude;
    $longitude = $record?->longitude;
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        wire:ignore
        x-data="projectLocationPicker({
            initialLatitude: @js(filled($latitude) ? (float) $latitude : null),
            initialLongitude: @js(filled($longitude) ? (float) $longitude : null),
            latitudeStatePath: @js($latitudeStatePath),
            longitudeStatePath: @js($longitudeStatePath),
            defaultLatitude: 37.0742,
            defaultLongitude: 36.2478,
        })"
        x-bind:data-expanded="expanded ? 'true' : 'false'"
        x-on:keydown.escape.window="expanded = false; window.setTimeout(() => map?.invalidateSize(), 150)"
        class="space-y-3"
        data-project-location-picker
    >
        <div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-gray-950 dark:text-white">Haritadan konum seçin</p>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Haritaya dokunun veya seçili pini sürükleyin. Koordinatlar otomatik kaydedilir.</p>
            </div>
            <button
                type="button"
                x-on:click="useCurrentLocation()"
                x-bind:disabled="locating"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-primary-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-500 disabled:cursor-wait disabled:opacity-60"
            >
                <x-heroicon-o-map-pin class="h-5 w-5" />
                <span x-text="locating ? 'Konum alınıyor…' : 'Konumumu kullan'"></span>
            </button>
        </div>

        <button
            type="button"
            x-on:click="toggleExpanded()"
            data-map-expand-button
            class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-primary-200 bg-primary-50 px-4 py-3 text-sm font-bold text-primary-700 shadow-sm transition hover:bg-primary-100 dark:border-primary-500/30 dark:bg-primary-500/10 dark:text-primary-300"
        >
            <x-heroicon-o-arrows-pointing-out x-show="! expanded" class="h-5 w-5" />
            <x-heroicon-o-arrows-pointing-in x-show="expanded" class="h-5 w-5" />
            <span x-text="expanded ? 'Haritayı küçült' : 'Haritayı tam ekran yap'"></span>
        </button>

        <div
            x-ref="map"
            class="w-full overflow-hidden rounded-xl border border-gray-300 bg-gray-100 shadow-sm dark:border-white/10"
            style="height: clamp(20rem, 45vw, 28rem); min-height: 20rem; width: 100%;"
            role="application"
            aria-label="Proje konumu seçme haritası"
        ></div>

        <div class="flex min-h-6 flex-wrap items-center gap-x-4 gap-y-1 text-xs">
            <p x-show="latitude !== null && longitude !== null" class="font-medium text-success-600 dark:text-success-400">
                Seçilen konum:
                <span x-text="`${Number(latitude).toFixed(7)}, ${Number(longitude).toFixed(7)}`"></span>
            </p>
            <p x-show="latitude === null || longitude === null" class="font-medium text-danger-600 dark:text-danger-400">Kaydetmeden önce haritadan bir konum seçin.</p>
            <p x-show="locationError" x-text="locationError" class="text-danger-600 dark:text-danger-400"></p>
        </div>
    </div>
</x-dynamic-component>
