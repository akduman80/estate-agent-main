<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class AssistantService
{
    public function __construct(private IntentAnalyzer $analyzer, private ListingTools $listings, private DocumentTools $documents, private IntentGateway $gateway) {}

    public function reply(string $message, array &$context, array $allowed = ['search', 'compare', 'open', 'details', 'documents', 'create', 'calculator', 'help'], bool $useRemote = false, array $history = []): array
    {
        if (preg_match('/yeni arama|tum\s+(?:aktif\s+)?ilan/', Str::ascii(Str::lower($message), 'tr'))) $context = [];
        $analysis = $this->analyzer->analyze($message, $context['filters'] ?? []);
        if ($useRemote && ! ($analysis['unsupported'] ?? false) && app(GeminiClient::class)->configured() && ! app(GeminiPrivacy::class)->sensitive($message) && ! in_array($analysis['intent'], ['compare', 'open', 'details', 'calculator'], true)) {
            $remote = app(GeminiAssistant::class)->reply($message, $context, $allowed, $history);
            if ($analysis['intent'] === 'search' && empty($remote['tool_used']) && ! isset($remote['pagination']) && ! isset($remote['draft']) && ! isset($remote['documents'])) {
                $local = $this->reply($message, $context, $allowed, false);
                if (! isset($remote['provider'])) $local['message'] = $remote['message']."\n\nYerel arama kullanıldı. Yalnız tanınan il/ilçe, tür, kategori, oda ve üst fiyat filtreleri uygulanmış olabilir; diğer kriterleri ilanlardan kontrol edin.\n".$local['message'];
                return $local;
            }
            return $remote;
        }
        if ($useRemote && $analysis['intent'] === 'help') {
            try {
                $remote = $this->gateway->analyze($message);
                if ($remote) {
                    $remote['filters'] = array_merge($context['filters'] ?? [], $remote['filters']);
                    $analysis = $remote;
                }
            } catch (\Throwable) {
                // Local tools stay available during provider outages; no message or credential is logged.
            }
        }
        if (! in_array($analysis['intent'], $allowed, true)) {
            return ['message' => 'Bu araç yönetici tarafından devre dışı bırakıldı.', 'listings' => [], 'action' => null];
        }
        $result = ['message' => '', 'listings' => [], 'action' => null];
        switch ($analysis['intent']) {
            case 'search':
                if ($analysis['unsupported'] ?? false) {
                    $result['message'] = 'İstediğiniz bahçe, havuz, otopark veya manzara özelliği için kayıtlı bir filtre alanı bulunmuyor. Bu özelliği doğrulanmış gibi süzmeden önce ilan açıklamasından kontrol etmelisiniz.';
                    break;
                }
                $context['filters'] = $analysis['filters'];
                $page = ($analysis['next'] ?? false) ? min(1000, ($context['page'] ?? 0) + 1) : 1;
                $search = $this->listings->searchPage($analysis['filters'], $page);
                $result['listings'] = $search['items'];
                $result['pagination'] = ['page' => $search['page'], 'has_more' => $search['has_more']];
                $context['page'] = $search['page'];
                $context['ids'] = array_column($result['listings'], 'id');
                unset($context['selected']);
                $result['message'] = $result['listings'] ? 'Kayıtlı kriterlere uygun ilanlar (en fazla 12 sonuç). İl, ilçe, satılık/kiralık veya bütçe belirterek sonuçları daraltabilirsiniz:' : 'Belirttiğiniz kriterlere uygun aktif ilan bulunamadı. Dilerseniz fiyat aralığını genişletebilir veya yakın bölgelerde arama yapabiliriz.';
                break;
            case 'compare':
                $result['listings'] = $this->listings->details(array_slice($context['ids'] ?? [], 0, $analysis['count'] ?? 3));
                $result['comparison'] = true;
                if (count($result['listings']) >= 2) {
                    $items = collect($result['listings']);
                    $comparable = $items->every(fn ($item) => $item['gross_unit_price'] !== null && filled($item['currency']) && filled($item['category']) && filled($item['type'])) && $items->pluck('currency')->unique()->count() === 1 && $items->pluck('category')->unique()->count() === 1 && $items->pluck('type')->unique()->count() === 1;
                    if ($comparable) {
                        $result['insight'] = 'Karşılaştırılan kayıtlar içinde brüt metrekare birim fiyatı en düşük ilan: #'.$items->sortBy('gross_unit_price')->first()['id'].'. Bu sonuç yalnız kayıtlı fiyat/alan oranıdır; yatırım veya değerleme garantisi değildir.';
                    }
                }
                $result['message'] = count($result['listings']) >= 2 ? 'Son aramadaki ilk ilanların güncel bilgileri. Brüt ve net birim fiyatları ayrı hesaplanır; para birimleri farklıysa doğrudan karşılaştırmayın.' : 'Karşılaştırmak için önce en az iki sonuç içeren bir ilan araması yapın.';
                break;
            case 'open':
            case 'details':
                $id = $analysis['intent'] === 'details' ? ($context['selected'] ?? null) : ($context['ids'][$analysis['ordinal'] - 1] ?? null);
                $result['listings'] = $id ? $this->listings->details([$id]) : [];
                if ($result['listings']) {
                    $context['selected'] = $id;
                    $result['action'] = ['type' => $analysis['intent'] === 'open' ? 'navigate' : 'link', 'url' => $result['listings'][0]['url'], 'label' => 'İlanı aç'];
                    $result['message'] = 'İlanın güncel kayıt bilgileri:';
                } else {
                    $result['message'] = 'İlan bulunamadı veya artık erişime açık değil. Önce ilan arayabilirsiniz.';
                }
                break;
            case 'documents':
                $result['message'] = 'Mevcut belge listesini açıp uygun şablonu seçebilirsiniz. Belgeyi editörde kontrol ederek doldurun.';
                $result['action'] = ['type' => 'documents', 'label' => 'Dokümanları aç'];
                $kind = $this->documents->kind($message) ?? ($context['document_kind'] ?? null);
                if ($kind) {
                    $context['document_kind'] = $kind;
                }
                $result['documents'] = $this->documents->matching($this->documents->catalog(), $kind);
                if (! $result['documents']) {
                    $result['message'] = 'Bu türde yayımlanmış belge bulunamadı. Mevcut Dokümanlar listesinden diğer şablonları inceleyebilirsiniz.';
                }
                $result['fields'] = $this->documents->prepare($message);
                if ($result['fields']) {
                    $result['message'] = 'Çıkarılan bilgileri kontrol edin. Seçeceğiniz belgede yalnız eşleşen alanlar, Aktar düğmesine bastığınızda doldurulur. Eksik alanlar değiştirilmez.';
                }
                break;
            case 'create':
                $result['draft'] = app(ListingDraftTool::class)->prepare($message, $analysis['filters']);
                $result['message'] = 'İlanınızı mevcut güvenli üye panelinden hazırlayın. Giriş, OTP, EİDS ve yayın onayı adımları uygulanır.';
                $result['action'] = ['type' => 'link', 'url' => url('/portal/listings/create'), 'label' => 'İlan oluşturma ekranı'];
                break;
            case 'calculator':
                $amount = $this->analyzer->analyze($message)['filters']['max_price'] ?? null;
                if ($simple = app(CalculatorTool::class)->simple($message, $amount)) {
                    $result = array_merge($result, $simple);
                    break;
                }
                if (str_contains(Str::ascii(Str::lower($message), 'tr'), 'tapu')) {
                    $amount = $this->analyzer->analyze($message)['filters']['max_price'] ?? null;
                    $result = array_merge($result, app(CalculatorTool::class)->titleFee($amount));
                    break;
                }
                $result['message'] = 'Bu hesaplama için güncel oran doğrulanmadan tutar üretmiyorum. Ana sayfadaki mevcut hesaplama aracını kullanabilirsiniz.';
                $result['action'] = ['type' => 'link', 'url' => route('home').'#piyasalar', 'label' => 'Mevcut araçlara git'];
                break;
            default:
                $fold = Str::ascii(Str::lower($message), 'tr');
                if (preg_match('/uyelik|uye ol|kayit|giris yap/', $fold)) {
                    $result = array_merge($result, app(GeminiTools::class)->execute('site_guide', ['page' => str_contains($fold, 'giris') ? 'login' : 'register'], $context, $allowed, $message));
                    break;
                }
                $result['sources'] = app(KnowledgeTool::class)->sources($message);
                $result['message'] = app(GeminiClient::class)->configured()
                    ? 'Bu soruyu Gemini ile yanıtlamak için “Gemini ile yanıtla” seçeneğini açın. İlan arama ve belge araçları bu seçenek kapalıyken de kullanılabilir.'
                    : 'Gemini bağlantısı henüz etkin değil. İlan arayabilir, sonuçları karşılaştırabilir veya mevcut belge ekranını açabilirim. Örnek: Osmaniye satılık daire göster.';
        }

        return $result;
    }
}
