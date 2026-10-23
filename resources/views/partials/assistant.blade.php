@if(config('assistant.enabled') && \App\Models\Setting::get('assistant_enabled', true))
<link rel="stylesheet" href="{{ asset('css/assistant.css') }}?v=1">
<button id="zyd-assistant-launch" type="button" aria-haspopup="dialog" aria-controls="zyd-assistant" aria-expanded="false">ZYD Asistan</button>
<dialog id="zyd-assistant" aria-labelledby="zyd-assistant-title" data-welcome="{{ \App\Models\Setting::get('assistant_welcome', "ZYDEMLAK'a hoş geldiniz. Ben ZYD Emlak Asistanı. Satılık ve kiralık gayrimenkul arayabilir, ilanları karşılaştırabilir, sözleşme hazırlamanıza yardımcı olabilir ve gayrimenkul işlemlerinizle ilgili sorularınıza yönlendirme sağlayabilirim. Size nasıl yardımcı olabilirim?") }}" data-endpoint="{{ route('assistant.message') }}" data-feedback="{{ route('assistant.feedback') }}" data-clear="{{ route('assistant.clear') }}" data-token="{{ csrf_token() }}">
<header><strong id="zyd-assistant-title">ZYD EMLAK ASİSTANI</strong><button type="button" data-expand aria-label="Pencere boyutunu değiştir">↔</button><button type="button" data-close aria-label="Sohbeti kapat">✕</button></header>
<div class="zyd-messages" role="log" aria-live="polite" aria-relevant="additions"></div>
<div class="zyd-quick">
@foreach(\App\Models\Setting::get('assistant_quick_actions', ['Satılık ev ara', 'Kiralık ev ara', 'Arsa ara', 'İlanları karşılaştır', 'Sözleşme hazırla', 'Tapu masrafı hesapla', 'Kira artışı hesapla', 'Emlak hakkında soru sor']) as $prompt)
<button type="button" data-prompt="{{ $prompt }}">{{ $prompt }}</button>
@endforeach
</div>
@if(config('assistant.endpoint'))
<label style="padding:0 14px;font-size:12px"><input type="checkbox" data-remote> Bu mesajın niyet analizi için yapılandırılmış yapay zekâ servisine gönderilmesini kabul ediyorum.</label>
@endif
<label class="zyd-remember"><input type="checkbox" data-remember> Bu sekmede sohbeti hatırla</label>
<details class="zyd-privacy"><summary>Gizlilik ve veri kullanımı</summary><p>Mesajlar site sunucusunda işlenir. Bu özellik sohbet metnini sunucuda kaydetmez; arama filtreleri ve ilan numaraları oturumda tutulur. Sohbeti hatırla seçeneği metinleri yalnız bu sekmede saklar. Haricî niyet servisi için ayrıca seçim yapmanız gerekir.</p></details>
<p class="zyd-status" role="status"></p>
<form><label for="zyd-message">Mesajınız</label><textarea id="zyd-message" rows="2" maxlength="2000" required placeholder="Nasıl yardımcı olabilirim?"></textarea><div class="zyd-controls"><button type="button" data-mic aria-pressed="false">Mikrofon</button><button type="submit">Gönder</button><button type="button" data-erase>Sohbeti sil</button></div></form>
</dialog>
<script>document.getElementById('zyd-assistant-launch').addEventListener('click',function(){if(!this.dataset.loaded){this.dataset.loaded='1';import(@js(asset('js/assistant.js') . '?v=1'));}});</script>
@endif
