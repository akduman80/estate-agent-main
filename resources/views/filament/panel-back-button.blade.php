@php
    $currentPath = trim(request()->path(), '/');
    $panelPath = trim($panelPath, '/');
    $isDashboard = $currentPath === $panelPath;
@endphp

@unless($isDashboard)
    <div class="mb-4">
        <a href="{{ $dashboardUrl }}" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:border-primary-300 hover:text-primary-600 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200">
            {{ $label }}
        </a>
    </div>
@endunless
