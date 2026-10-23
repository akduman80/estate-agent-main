@if(config('assistant.enabled') && \App\Models\Setting::get('assistant_enabled', true) && in_array('create', \App\Models\Setting::get('assistant_tools', ['create']), true) && request()->is('portal/listings/create'))
<script type="module" src="{{ asset('js/assistant-listing-draft.js') }}?v=1"></script>
@endif
