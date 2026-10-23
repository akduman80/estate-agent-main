@if(config('assistant.enabled') && \App\Models\Setting::get('assistant_enabled', true))
<link rel="stylesheet" href="{{ asset('css/assistant.css') }}?v=gemini-20261023">
<button id="zyd-assistant-launch" type="button" aria-haspopup="dialog" aria-controls="zyd-assistant" aria-expanded="false">ZYD Asistan</button>
<dialog id="zyd-assistant" aria-labelledby="zyd-assistant-title" data-welcome="{{ \App\Models\Setting::get('assistant_welcome', "ZYDEMLAK'a hoş geldiniz. Ben ZYD Emlak Asistanı. Satılık ve kiralık gayrimenkul arayabilir, ilanları karşılaştırabilir, sözleşme hazırlamanıza yardımcı olabilir ve gayrimenkul işlemlerinizle ilgili sorularınıza yönlendirme sağlayabilirim. Size nasıl yardımcı olabilirim?") }}" data-endpoint="{{ route('assistant.message') }}" data-feedback="{{ route('assistant.feedback') }}" data-clear="{{ route('assistant.clear') }}" data-token="{{ csrf_token() }}">
<header><strong id="zyd-assistant-title">ZYD EMLAK ASİSTANI</strong><button type="button" data-expand aria-label="Pencere boyutunu değiştir">↔</button><button type="button" data-close aria-label="Sohbeti kapat">✕</button></header>
<div class="zyd-messages" role="log" aria-live="polite" aria-relevant="additions"></div>
<div class="zyd-quick">
@foreach(\App\Models\Setting::get('assistant_quick_actions', ['Satılık ev ara', 'Kiralık ev ara', 'Arsa ara', 'İlanları karşılaştır', 'Sözleşme hazırla', 'Tapu masrafı hesapla', 'Kira artışı hesapla', 'Emlak hakkında soru sor']) as $prompt)
<button type="button" data-prompt="{{ $prompt }}">{{ $prompt }}</button>
@endforeach
</div>
@if(app(\App\Services\Assistant\GeminiClient::class)->configured())
<label class="zyd-remember"><input type="checkbox" data-remote> Gemini ile yanıtla — mesaj ve kısa sohbet bağlamı Google’a gönderilir.</label>
@else
<p class="zyd-status">Gemini henüz bağlı değil. İlan arama ve belge araçları kullanılabilir.</p>
@endif
<label class="zyd-remember"><input type="checkbox" data-remember> Bu sekmede sohbeti hatırla</label>
<label class="zyd-remember"><input type="checkbox" data-speak> Yanıtları sesli oku <button type="button" data-stop-speech>Seslendirmeyi durdur</button></label>
<details class="zyd-privacy"><summary>Gizlilik ve veri kullanımı</summary><p>Mesajlar sunucuda konuşma kaydı olarak tutulmaz. Arama filtreleri ve açık ilan numaraları oturumda tutulur. Sohbeti hatırla seçeneği son 20 metni yalnız bu sekmede saklar; Sohbeti sil bunları temizler. Gemini seçildiğinde son birkaç mesaj ve yalnız yayındaki ilan/boş şablon bilgileri Google’a gönderilebilir. API saklama özelliği kapalıdır; Google’ın hizmet kapsamında veri işlemesi devam edebilir. Kimlik, banka, şifre ve doğrulama bilgilerini sohbete yazmayın. Kişisel belge alanlarını mevcut editörde doldurun. Mikrofon için tarayıcınızın ses hizmeti kullanılabilir.</p></details>
<p class="zyd-status" role="status"></p>
<form><label for="zyd-message">Mesajınız</label><textarea id="zyd-message" rows="2" maxlength="2000" required placeholder="Nasıl yardımcı olabilirim?"></textarea><div class="zyd-controls"><button type="button" data-mic aria-pressed="false">Mikrofon</button><button type="submit">Gönder</button><button type="button" data-erase>Sohbeti sil</button></div></form>
</dialog>
<script>document.getElementById('zyd-assistant-launch').addEventListener('click',function(){if(!this.dataset.loaded){this.dataset.loaded='1';import(@js(asset('js/assistant.js') . '?v=gemini-20261023'));}});</script>
@endif
