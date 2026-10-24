<?php

namespace App\Services\Assistant;

use Illuminate\Support\Str;

class AssistantService
{
    public function __construct(private IntentAnalyzer $analyzer, private ListingTools $listings, private DocumentTools $documents, private IntentGateway $gateway) {}

    public function reply(string $message, array &$context, array $allowed = ['search', 'compare', 'open', 'details', 'documents', 'create', 'calculator', 'help'], bool $useRemote = false, array $history = []): array
    {
        $broadened = false;
        $answer = trim(Str::ascii(Str::lower($message), 'tr'), " .!?\t\n\r");
        if (isset($context['suggested_removal']) && in_array($answer, ['evet', 'olur', 'kabul', 'genislet'], true)) {
            if (!in_array('search', $allowed, true)) return ['message' => 'Bu araç yönetici tarafından devre dışı bırakıldı.', 'listings' => [], 'action' => null];
            foreach ($context['suggested_removal'] as $key) unset($context['filters'][$key]);
            unset($context['suggested_removal'], $context['cheapest']);
            $message = 'İlanları göster';
            $broadened = true;
        } else {
            unset($context['suggested_removal']);
        }
        if (preg_match('/yeni arama|tum\s+(?:aktif\s+)?ilan/', Str::ascii(Str::lower($message), 'tr'))) $context = [];
        $analysis = $this->analyzer->analyze($message, $context['filters'] ?? []);
        $fold = Str::ascii(Str::lower($message), 'tr');
        $detailField = null;
        foreach (['isitma' => 'heating', 'bina yasi' => 'building_age', 'hangi kat' => 'floor', 'kac metrekare' => 'gross_area', 'metrekaresi' => 'gross_area', 'musait' => 'availability', 'eids' => 'eids', 'tapusu' => 'deed'] as $word => $field) {
            if (str_contains($fold, $word) && ! empty($context['ids'])) { $analysis['intent'] = 'details'; $detailField = $field; break; }
        }
        if (! in_array($analysis['intent'], $allowed, true)) return ['message' => 'Bu araç yönetici tarafından devre dışı bırakıldı.', 'listings' => [], 'action' => null];
        if ($useRemote && ! ($analysis['unsupported'] ?? false) && app(GeminiClient::class)->configured() && ! app(GeminiPrivacy::class)->sensitive($message) && ! in_array($analysis['intent'], ['search', 'compare', 'open', 'details', 'calculator'], true)) {
            $remote = app(GeminiAssistant::class)->reply($message, $context, $allowed, $history);
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
                if ($analysis['ambiguous'] ?? false) {
                    $context['filters'] = $analysis['filters'];
                    unset($context['ids'], $context['selected']);
                    $result['message'] = 'Hangi il veya ilçedeki konumu kastediyorsunuz?';
                    break;
                }
                if ($analysis['unsupported'] ?? false) {
                    $context['filters'] = $analysis['filters'];
                    $context['filters']['unsupported_condition'] = true;
                    unset($context['ids'], $context['selected']);
                    $result['message'] = 'İstenen özellik için doğrulanabilir arama alanı bulunmuyor. Bu koşul kaldırılmadan kesin eşleşme gösteremiyorum.';
                    break;
                }
                foreach ([['min_price', 'max_price'], ['min_area', 'max_area']] as [$min, $max]) {
                    if (isset($analysis['filters'][$min], $analysis['filters'][$max]) && $analysis['filters'][$min] > $analysis['filters'][$max]) {
                        return ['message' => 'Alt sınır üst sınırdan büyük. Hangi aralığı kullanayım?', 'listings' => [], 'action' => null];
                    }
                }
                if (str_contains($fold, 'daha ucuz') || str_contains($fold, 'daha uygun fiyat')) {
                    if (isset($context['cheapest'])) $analysis['filters']['price_lt'] = min($analysis['filters']['price_lt'] ?? INF, $context['cheapest']);
                }
                $context['filters'] = $analysis['filters'];
                $result['criteria'] = $analysis['filters'];
                $page = ($analysis['next'] ?? false) ? min(1000, ($context['page'] ?? 0) + 1) : 1;
                try {
                    $search = $this->listings->searchPage($analysis['filters'], $page);
                } catch (\Throwable) {
                    $result['message'] = 'İlan arama hizmetine şu anda ulaşılamıyor. Lütfen tekrar deneyin.';
                    $result['error'] = 'search_unavailable';
                    unset($context['ids'], $context['selected']);
                    break;
                }
                $result['listings'] = $search['items'];
                $result['pagination'] = ['page' => $search['page'], 'has_more' => $search['has_more']];
                if ($result['listings']) {
                    $prices = array_filter(array_column($result['listings'], 'price'), 'is_numeric');
                    if ($prices) $context['cheapest'] = min($prices);
                }
                $context['page'] = $search['page'];
                $context['ids'] = array_column($result['listings'], 'id');
                unset($context['selected']);
                $result['message'] = $result['listings'] ? 'Uygun ilanlar:' : 'Bu koşullarda ilan bulunamadı.';
                if ($broadened && $result['listings']) $result['message'] = 'Genişletilen aramanın sonuçları:';
                if (!$result['listings']) {
                    if (array_intersect(['max_price', 'price_lt'], array_keys($analysis['filters']))) {
                        $context['suggested_removal'] = ['max_price', 'price_lt'];
                        $result['message'] .= ' Fiyat üst sınırını kaldırayım mı?';
                    } elseif (isset($analysis['filters']['district']) || isset($analysis['filters']['neighborhood'])) {
                        $context['suggested_removal'] = ['district', 'neighborhood'];
                        $result['message'] .= ' İlçe ve mahalle koşulunu kaldırayım mı?';
                    }
                }

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
                if ($analysis['intent'] === 'details' && ! isset($context['selected']) && count($context['ids'] ?? []) > 1 && ! preg_match('/birinci|ikinci|ucuncu|\d+\.?\s*ilan/', $fold)) {
                    $result['message'] = 'Hangi ilanı kastediyorsunuz?';
                    break;
                }
                if ($analysis['intent'] === 'details' && preg_match('/birinci|ikinci|ucuncu|\d+\.?\s*ilan/', $fold)) $context['selected'] = $context['ids'][$analysis['ordinal'] - 1] ?? null;
                $id = $analysis['intent'] === 'details' ? ($context['selected'] ?? ($context['ids'][0] ?? null)) : ($context['ids'][$analysis['ordinal'] - 1] ?? null);
                $result['listings'] = $id ? $this->listings->details([$id]) : [];
                if ($result['listings']) {
                    $context['selected'] = $id;
                    $result['action'] = ['type' => $analysis['intent'] === 'open' ? 'navigate' : 'link', 'url' => $result['listings'][0]['url'], 'label' => 'İlanı aç'];
                    $result['message'] = 'İlanın güncel kayıt bilgileri:';
                    if ($detailField) {
                        $value = $result['listings'][0][$detailField] ?? null;
                        $result['message'] = filled($value) ? 'İlandaki bilgi: '.$value.($detailField === 'gross_area' ? ' m² (brüt).' : '.') : 'Bu bilgi ilanda belirtilmemiş';
                    }
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
                    ? 'Ne aradığınızı biraz daha açık yazar mısınız?'
                    : 'Gemini bağlantısı henüz etkin değil. İlan arayabilir, sonuçları karşılaştırabilir veya mevcut belge ekranını açabilirim. Örnek: Osmaniye satılık daire göster.';
        }

        return $result;
    }
}
