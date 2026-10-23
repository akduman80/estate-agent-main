<?php

namespace App\Services\Assistant;

use App\Services\TurkeyAddressService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Read-only allowlist. Model tool arguments never become SQL, routes or write operations. */
class GeminiTools
{
    private const ACCESS = ['search_listings' => 'search', 'listing_details' => 'details', 'list_documents' => 'documents', 'document_fields' => 'documents', 'prepare_document' => 'documents', 'site_guide' => 'help', 'prepare_listing' => 'create'];

    public function declarations(array $allowed): array
    {
        $string = ['type' => 'string']; $number = ['type' => 'number'];
        $definitions = [
            'search_listings' => ['Find real published listings. Use only stated filters; unspecified filters stay absent. City and district are Turkish names. area is gross m².', [
                'city' => $string, 'district' => $string, 'neighborhood' => $string,
                'type' => $string + ['enum' => ['Satılık', 'Kiralık']],
                'category' => $string + ['enum' => ['Daire', 'Müstakil Ev', 'Villa', 'Konut', 'Arsa', 'Tarla', 'İşyeri']],
                'rooms' => $string, 'min_price' => $number, 'max_price' => $number, 'min_area' => $number, 'max_area' => $number,
                'sort' => $string + ['enum' => ['price_asc', 'price_desc']], 'heating' => $string, 'floor' => $string, 'building_age' => $string,
            ]],
            'listing_details' => ['Fetch current public listing details. IDs must come from the last search. Never fetch owner/contact/private data.', ['ids' => ['type' => 'array', 'items' => ['type' => 'integer'], 'maxItems' => 12]]],
            'list_documents' => ['List existing published document templates, optionally filtered by lease, workplace-lease, eviction, prepaid, sale-promise, promissory or deposit.', ['kind' => $string]],
            'document_fields' => ['Get the existing field names for a document in the published catalog. index is zero based.', ['index' => ['type' => 'integer']]],
            'prepare_document' => ['Prepare a preview for an existing document only. No signing, saving or sharing. Personal data is extracted locally, never ask for identity or bank data here.', ['kind' => $string]],
            'site_guide' => ['Get verified existing site links and registration steps.', ['page' => $string + ['enum' => ['register', 'login', 'create', 'documents']]]],
            'prepare_listing' => ['Draft a title and plain description using only user-stated facts. Never invent deed, zoning or advantages. Requires user confirmation to transfer. No publishing.', ['title' => $string, 'description' => $string, 'category' => $string + ['enum' => ['Daire', 'Müstakil Ev', 'Villa', 'Konut', 'Arsa', 'Tarla', 'İşyeri']]]],
        ];
        $out = [];
        foreach ($definitions as $name => [$description, $properties]) {
            if (! in_array(self::ACCESS[$name], $allowed, true)) continue;
            $out[] = ['type' => 'function', 'name' => $name, 'description' => $description, 'parameters' => ['type' => 'object', 'properties' => $properties, 'additionalProperties' => false]];
        }
        return $out;
    }

    public function execute(string $name, array $args, array &$context, array $allowed, string $message): array
    {
        if (! isset(self::ACCESS[$name]) || ! in_array(self::ACCESS[$name], $allowed, true)) return ['message' => 'Bu araca izin verilmedi.', 'denied' => true];
        if ($name === 'search_listings') {
            $rules = [
                'city' => ['sometimes', 'string', Rule::in(array_values(TurkeyAddressService::getCities()))],
                'district' => ['sometimes', 'string', 'max:80'], 'neighborhood' => ['sometimes', 'string', 'max:120'],
                'type' => ['sometimes', Rule::in(['Satılık', 'Kiralık'])],
                'category' => ['sometimes', Rule::in(['Daire', 'Müstakil Ev', 'Villa', 'Konut', 'Arsa', 'Tarla', 'İşyeri'])],
                'rooms' => ['sometimes', 'string', 'max:20'], 'sort' => ['sometimes', Rule::in(['price_asc', 'price_desc'])],
            ];
            foreach (['min_price', 'max_price', 'min_area', 'max_area'] as $key) $rules[$key] = ['sometimes', 'numeric', 'min:0', 'max:1000000000000'];
            foreach (['heating', 'floor', 'building_age'] as $key) $rules[$key] = ['sometimes', 'string', 'max:80'];
            $filters = Validator::make($args, $rules)->validate();
            $previous = $context['filters'] ?? [];
            if (isset($filters['city']) && $filters['city'] !== ($previous['city'] ?? null)) unset($previous['district'], $previous['neighborhood']);
            if (isset($filters['district']) && $filters['district'] !== ($previous['district'] ?? null)) unset($previous['neighborhood']);
            if (isset($filters['category'])) unset($previous['categories']);
            $filters = array_merge($previous, $filters);
            if (isset($filters['district']) && (! isset($filters['city']) || ! in_array($filters['district'], array_values(TurkeyAddressService::getDistricts($filters['city'])), true))) return ['message' => 'İlçenin bağlı olduğu ili belirtin veya ilçe adını kontrol edin.'];
            if (isset($filters['neighborhood']) && (! isset($filters['city'], $filters['district']) || ! in_array($filters['neighborhood'], array_values(TurkeyAddressService::getNeighborhoods($filters['city'], $filters['district'])), true))) return ['message' => 'Mahalle adı için il ve ilçeyi belirtin; mevcut konum listesindeki adı kullanın.'];
            foreach ([['min_price','max_price'], ['min_area','max_area']] as [$min,$max]) if (isset($filters[$min],$filters[$max]) && $filters[$min] > $filters[$max]) return ['message' => 'Alt sınır üst sınırdan büyük olamaz. Aralığı kontrol edin.'];
            $search = app(ListingTools::class)->searchPage($filters);
            $context['filters'] = $filters; $context['ids'] = array_column($search['items'], 'id'); $context['page'] = 1; unset($context['selected']);
            return ['message' => $search['items'] ? 'İsteğinize uygun yayındaki gerçek ilanlar:' : 'Bu kriterlere uygun yayındaki ilan bulunamadı. Bütçeyi veya bölgeyi genişleterek tekrar arayabilirsiniz.', 'listings' => $search['items'], 'pagination' => ['page' => 1, 'has_more' => $search['has_more']]];
        }
        if ($name === 'listing_details') {
            $ids = Validator::make($args, ['ids' => ['required', 'array', 'max:12'], 'ids.*' => ['integer', 'min:1']])->validate()['ids'];
            $ids = array_values(array_intersect($ids, $context['ids'] ?? []));
            return ['message' => $ids ? 'İlanların güncel kayıt bilgileri:' : 'Önce ilan arayın; yalnız son aramadaki ilanlar okunabilir.', 'listings' => app(ListingTools::class)->details($ids)];
        }
        if (in_array($name, ['list_documents','document_fields','prepare_document'], true)) {
            $documents = app(DocumentTools::class); $catalog = $documents->catalog();
            if ($name === 'document_fields') {
                $index = Validator::make($args, ['index' => ['required','integer','min:0','max:200']])->validate()['index'];
                return ['message' => 'Belgenin mevcut doldurulabilir alanları:', 'documents' => isset($catalog[$index]) ? [$catalog[$index]] : []];
            }
            $kind = Validator::make($args, ['kind' => ['sometimes', Rule::in(['lease','workplace-lease','eviction','prepaid','sale-promise','promissory','deposit'])]])->validate()['kind'] ?? null;
            if ($kind) $context['document_kind'] = $kind;
            return ['message' => 'Mevcut şablonu seçin. Eksik alanları editörde tamamlayabilirsiniz; aktarım için ayrıca onayınız istenir.', 'documents' => $documents->matching($catalog, $kind), 'fields' => $documents->prepare($message), 'action' => ['type' => 'documents', 'label' => 'Dokümanları aç']];
        }
        if ($name === 'site_guide') {
            $page = Validator::make($args, ['page' => ['required',Rule::in(['register','login','create','documents'])]])->validate()['page'];
            return match ($page) {
                'register' => ['message' => 'Kayıt sayfasında bireysel veya işletme hesabını seçin. Formu ve gerekli onayları tamamlayın; sistemin gösterdiği doğrulama adımlarını izleyin. Kimlik, şifre veya doğrulama kodunu sohbete yazmayın.', 'action' => ['type' => 'link','url' => route('register'),'label' => 'Üyelik oluştur']],
                'login' => ['message' => 'Mevcut giriş ekranından hesabınıza giriş yapabilirsiniz.', 'action' => ['type' => 'link','url' => route('login'),'label' => 'Giriş yap']],
                'create' => ['message' => 'İlanınızı mevcut üye panelinde hazırlayın. Giriş, OTP, EİDS ve yayın onayı kuralları geçerlidir.', 'action' => ['type' => 'link','url' => url('/portal/listings/create'),'label' => 'İlan oluştur']],
                default => ['message' => 'Dokümanlar bölümünden mevcut belgeyi seçerek doldurabilir, kısmen dolu belgeyi de mevcut araçlarla yazdırabilirsiniz.', 'action' => ['type' => 'documents','label' => 'Dokümanları aç']],
            };
        }
        $draft = Validator::make($args, ['title' => ['required','string','max:180'], 'description' => ['required','string','max:3000'], 'category' => ['sometimes',Rule::in(['Daire','Müstakil Ev','Villa','Konut','Arsa','Tarla','İşyeri'])]])->validate();
        $filters = app(IntentAnalyzer::class)->analyze($message)['filters'];
        $prepared = app(ListingDraftTool::class)->prepare($message, $filters);
        $candidate = strip_tags($draft['title'].' '.$draft['description']);
        $fold = \Illuminate\Support\Str::ascii(\Illuminate\Support\Str::lower($message), 'tr');
        $candidateFold = \Illuminate\Support\Str::ascii(\Illuminate\Support\Str::lower($candidate), 'tr');
        $unsupported = false;
        foreach (['imar','tapulu','tek tapu','ifraz','hastane','okul','deniz','kredi','garanti'] as $claim) if (str_contains($candidateFold,$claim) && ! str_contains($fold,$claim)) $unsupported = true;
        preg_match_all('/\d[\d.,]*/', $candidate, $numbers);
        $providedNumbers = preg_replace('/[.,]/','',$message);
        foreach ($numbers[0] as $number) if (! str_contains($providedNumbers,preg_replace('/[.,]/','',$number))) $unsupported = true;
        if (! $unsupported) {
            $prepared['title'] = strip_tags($draft['title']);
            $prepared['description'] = '<p>'.htmlspecialchars($draft['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</p>';
        }
        if (isset($draft['category'])) $prepared['category'] = $draft['category'];
        return ['message' => ($unsupported ? 'Üretilen metinde doğrulanmayan ek bilgiler bulunduğu için özgün beyanınız korundu. ' : '').'İlan taslağı hazırlandı; kaydedilmedi veya yayımlanmadı. Özellikleri ve varsa hisseli mülkiyet açıklamasını kontrol edin, yalnız verdiğiniz bilgileri koruyun.', 'draft' => $prepared];
    }
}
