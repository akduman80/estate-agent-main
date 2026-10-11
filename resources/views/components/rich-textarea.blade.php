@props(['name', 'label', 'value' => '', 'required' => false, 'plainSubmit' => false])
@php($editorId = 'rich-textarea-'.\Illuminate\Support\Str::uuid())
<div {{ $attributes->class(['block']) }}>
    <label for="{{ $editorId }}" class="text-xs font-bold text-slate-700">{{ $label }} @if($required)<b class="text-red-500">*</b>@endif</label>
    <div class="fi-fo-rich-editor mt-1.5 rounded-xl border border-slate-300 bg-white p-2">
        <input id="{{ $editorId }}-value" type="hidden" name="{{ $name }}" value="{{ $value }}">
        <trix-editor id="{{ $editorId }}" input="{{ $editorId }}-value" @required($required) data-plain-submit="{{ $plainSubmit ? 'true' : 'false' }}" aria-label="{{ $label }}" class="min-h-32 text-sm"></trix-editor>
    </div>
</div>
@once
    @push('head')
        <link rel="stylesheet" href="{{ asset('css/filament/forms/forms.css') }}">
    @endpush
    @push('scripts')
        @include('filament.listing-rich-editor-formatting')
        <script type="module">
            import '{{ asset('js/filament/forms/components/rich-editor.js') }}';
            document.addEventListener('trix-initialize', (event) => {
                event.target.toolbarElement.querySelector('[data-trix-button-group="file-tools"]')?.remove();
            });
            document.addEventListener('trix-file-accept', (event) => event.preventDefault());
            document.addEventListener('submit', (event) => {
                const editors = event.target.querySelectorAll('trix-editor');
                for (const editor of editors) {
                    if (editor.hasAttribute('required') && !editor.editor.getDocument().toString().trim()) {
                        event.preventDefault();
                        editor.focus();
                        alert('Lütfen ' + (editor.getAttribute('aria-label') || 'metin') + ' alanını doldurun.');
                        return;
                    }
                }
                // The contact form uses mailto with a plain text body.
                editors.forEach((editor) => {
                    if (editor.dataset.plainSubmit === 'true') {
                        document.getElementById(editor.getAttribute('input')).value = editor.editor.getDocument().toString().trim();
                    }
                });
            });
        </script>
    @endpush
@endonce
