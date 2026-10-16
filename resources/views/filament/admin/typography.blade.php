{{-- Loaded exclusively by AdminPanelProvider, including its modal/teleported views. --}}
<style>
    html.fi {
        font-size: 100%;
        --zy-bg: #f0f5fb;
        --zy-surface: #fff;
        --zy-subtle: #eaf2fc;
        --zy-text: #14243e;
        --zy-muted: #52647d;
        --zy-blue: #2583eb;
        --zy-line: #b8d4f5;
        --zy-green: #15803d;
        --zy-selected: #dcfce7;
        color-scheme: light;
    }
    html.fi.dark {
        --zy-bg: #0c1728;
        --zy-surface: #16263a;
        --zy-subtle: #20344c;
        --zy-text: #f1f5f9;
        --zy-muted: #b9c9dd;
        --zy-blue: #389bff;
        --zy-line: #3e6186;
        --zy-selected: #164c35;
        color-scheme: dark;
    }
    .fi-body { background: var(--zy-bg) !important; color: var(--zy-text); }
    .fi-main { padding-top: 1.5rem; padding-bottom: 2rem; }
    .fi-page { gap: 1.5rem; }
    .fi-header {
        padding: 1.25rem;
        background: var(--zy-surface);
        border: 1px solid var(--zy-blue);
        border-radius: .875rem;
    }
    .fi-header-heading, .fi-section-header-heading, .fi-wi-stats-overview-stat-value {
        color: var(--zy-text) !important; letter-spacing: -.025em;
    }
    .fi-header-subheading, .fi-section-header-description, .fi-wi-stats-overview-stat-label,
    .fi-fo-field-wrp-helper-text, .fi-ta-empty-state-description { color: var(--zy-muted) !important; }
    .fi-topbar > nav, .fi-sidebar-header {
        background: var(--zy-surface) !important;
        border-bottom: 1px solid var(--zy-blue);
        box-shadow: none !important;
    }
    .fi-sidebar {
        background: var(--zy-surface) !important;
        border-inline-end: 1px solid var(--zy-blue);
    }
    .fi-sidebar-nav { padding: 1rem .75rem; }
    .fi-sidebar-nav > ul { row-gap: 1.5rem; }
    .fi-sidebar-group-button {
        min-height: 2.75rem; padding: .6rem .75rem;
        border: 2px solid var(--zy-blue); border-radius: .6rem;
        background: var(--zy-subtle);
        transition: background-color .15s, border-color .15s;
    }
    .fi-sidebar-group-label {
        color: var(--zy-text) !important; font-weight: 750 !important;
        text-transform: uppercase; letter-spacing: .035em;
    }
    .fi-sidebar-group-button .fi-icon-btn-icon { color: var(--zy-muted) !important; }
    .fi-sidebar-group.fi-active > .fi-sidebar-group-button,
    .fi-sidebar-group-button:active, .fi-sidebar-group-button:focus-within {
        background: var(--zy-green); border-color: var(--zy-green);
    }
    .fi-sidebar-group.fi-active > .fi-sidebar-group-button .fi-sidebar-group-label,
    .fi-sidebar-group.fi-active > .fi-sidebar-group-button .fi-icon-btn-icon,
    .fi-sidebar-group-button:active .fi-sidebar-group-label,
    .fi-sidebar-group-button:focus-within .fi-sidebar-group-label,
    .fi-sidebar-group-button:focus-within .fi-icon-btn-icon { color: #fff !important; }
    .fi-sidebar-group-items { margin-top: .35rem; }
    .fi-sidebar-item-button { min-height: 2.65rem; border-radius: .55rem; }
    .fi-sidebar-item-label, .fi-sidebar-item-icon { color: var(--zy-muted) !important; }
    .fi-sidebar-item-button:hover { background: var(--zy-subtle) !important; }
    .fi-sidebar-item.fi-active > .fi-sidebar-item-button { background: var(--zy-selected) !important; }
    .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .fi-sidebar-item.fi-active .fi-sidebar-item-icon { color: #166534 !important; }
    .dark .fi-sidebar-item.fi-active .fi-sidebar-item-label,
    .dark .fi-sidebar-item.fi-active .fi-sidebar-item-icon { color: #dcfce7 !important; }
    .fi-section, .fi-ta-ctn, .fi-wi-stats-overview-stat, .fi-tabs:not(.fi-tabs-contained),
    .fi-modal-window, .fi-dropdown-panel, .fi-global-search-results-ctn {
        background: var(--zy-surface) !important;
        border: 1px solid var(--zy-blue) !important; border-radius: .875rem;
        box-shadow: 0 3px 12px rgb(15 35 65 / 4%) !important;
        --tw-ring-color: transparent;
    }
    .fi-section-header { border-bottom: 1px solid var(--zy-line); }
    .fi-section-header, .fi-section-content, .fi-wi-stats-overview-stat { padding: 1.25rem; }
    .fi-wi-stats-overview-stat-value { font-size: 1.9rem; line-height: 1.2; }
    .fi-ta-header, .fi-ta-header-toolbar, .fi-ta-pagination {
        background: var(--zy-surface) !important; color: var(--zy-text);
    }
    .fi-ta-table thead, .fi-ta-table thead tr { background: var(--zy-subtle) !important; }
    .fi-ta-header-cell-label { color: var(--zy-text) !important; font-weight: 700; }
    .fi-ta-row { border-bottom: 1px solid var(--zy-line); }
    .fi-ta-row:hover { background: var(--zy-subtle) !important; }
    .fi-ta-cell { padding-top: .3rem; padding-bottom: .3rem; }
    .fi-ta-content { overflow-x: auto; overscroll-behavior-inline: contain; }
    .fi-input-wrp {
        background: var(--zy-surface) !important;
        border: 1px solid var(--zy-line); border-radius: .55rem;
    }
    .fi-input-wrp:focus-within { outline: 2px solid var(--zy-blue); outline-offset: 2px; }
    .fi-input, .fi-select-input, .fi-fo-field-wrp-label, .fi-fo-field-wrp-label span,
    .fi-fo-rich-editor .tiptap, .fi-fo-markdown-editor textarea { color: var(--zy-text); }
    .fi-input, .fi-select-input { background-color: transparent; }
    .fi-input::placeholder, .fi-global-search-field input::placeholder { color: var(--zy-muted); opacity: 1; }
    .fi-fo-rich-editor, .fi-fo-markdown-editor, .fi-fo-repeater-item {
        border: 1px solid var(--zy-line); border-radius: .65rem; background: var(--zy-surface);
    }
    .fi-fo-rich-editor-toolbar, .fi-fo-repeater-item-header { background: var(--zy-subtle); }
    .fi-tabs { max-width: 100%; overflow-x: auto; }
    .fi-tabs-item { flex-shrink: 0; }
    .fi-tabs-item.fi-active { background: var(--zy-selected) !important; }
    .fi-tabs-item.fi-active .fi-tabs-item-label { color: #166534 !important; }
    .dark .fi-tabs-item.fi-active .fi-tabs-item-label { color: #dcfce7 !important; }
    .fi-btn { border-radius: .55rem; min-height: 2.5rem; }
    .zy-theme-switcher {
        display: flex; flex-shrink: 0; gap: .2rem; padding: .2rem;
        border: 1px solid var(--zy-blue); border-radius: .6rem;
        background: var(--zy-surface); margin-inline-start: .5rem;
    }
    .zy-theme-switcher button {
        display: inline-flex; align-items: center; justify-content: center; gap: .35rem;
        min-height: 2.25rem; padding: .3rem .6rem; border-radius: .4rem;
        font-size: .8rem; font-weight: 600; color: var(--zy-muted);
    }
    .zy-theme-switcher button[aria-pressed="true"] { background: var(--zy-selected); color: var(--zy-text); }
    .zy-theme-switcher button:focus-visible, .fi-sidebar-group-button:focus-visible {
        outline: 3px solid var(--zy-blue); outline-offset: 3px;
    }
    @media (max-width: 767px) {
        .fi-main { padding-inline: .75rem; padding-top: 1rem; }
        .fi-header { padding: 1rem; gap: 1rem; }
        .fi-header-heading { font-size: 1.5rem; }
        .fi-section-header, .fi-section-content, .fi-wi-stats-overview-stat { padding: 1rem; }
        .fi-wi-stats-overview-stat-value { font-size: 1.65rem; }
        .fi-ta-content { -webkit-overflow-scrolling: touch; }
        .zy-theme-switcher button { padding-inline: .45rem; min-width: 2.25rem; }
        .zy-theme-switcher button span { display: none; }
    }
    @media (prefers-reduced-motion: reduce) { .fi-sidebar-group-button { transition: none; } }
</style>
