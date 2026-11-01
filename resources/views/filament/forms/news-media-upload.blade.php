<div x-data="{ uploadPhase: '' }"
    x-init="
        $el.addEventListener('FilePond:addfilestart', e => { if (e.detail.file.origin === 1) uploadPhase = 'Dosya seçildi, yükleme hazırlanıyor.' });
        $el.addEventListener('FilePond:processfilestart', () => uploadPhase = 'Dosya yükleniyor…');
        $el.addEventListener('FilePond:processfile', e => uploadPhase = e.detail.error ? 'Geçici yükleme başarısız. Yükleme alanından yeniden deneyin; kayıtlı medya korundu.' : 'Yükleme tamamlandı, kaydetmeniz bekleniyor.');
        $el.addEventListener('FilePond:addfile', e => { if (e.detail.file.origin === 3 && !e.detail.error) uploadPhase = '' });
        $el.addEventListener('FilePond:removefile', () => uploadPhase = '');
    ">
    @include('filament-forms::components.file-upload')
    <p x-show="uploadPhase" x-text="uploadPhase" role="status" aria-live="polite" style="margin-top:.5rem;font-size:.875rem"></p>
</div>
