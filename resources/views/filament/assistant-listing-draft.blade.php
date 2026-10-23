@if(request()->is('portal/listings/create') && config('assistant.enabled') && \App\Models\Setting::get('assistant_enabled', true) && in_array('create', \App\Models\Setting::get('assistant_tools', ['search','compare','open','details','documents','create','calculator','help']), true))
<script type="module" src="{{ asset('js/assistant-listing-draft.js') }}?v=1"></script>
@endif
