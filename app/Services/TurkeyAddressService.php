<?php

namespace App\Services;

use Illuminate\Support\Str;

class TurkeyAddressService
{
    protected static ?array $neighborhoodData = null;

    protected static ?array $neighborhoodCityIndex = null;

    protected static array $neighborhoodDistrictIndexes = [];

    protected static array $data = [
        "Adana" => ["Aladağ", "Ceyhan", "Çukurova", "Feke", "İmamoğlu", "Karaisalı", "Karataş", "Kozan", "Pozantı", "Saimbeyli", "Sarıçam", "Seyhan", "Tufanbeyli", "Yumurtalık", "Yüreğir"],
        "Adıyaman" => ["Besni", "Çelikhan", "Gerger", "Gölbaşı", "Kahta", "Merkez", "Samsat", "Sincik", "Tut"],
        "Afyonkarahisar" => ["Başmakçı", "Bayat", "Bolvadin", "Çay", "Çobanlar", "Dazkırı", "Dinar", "Emirdağ", "Evciler", "Hocalar", "İhsaniye", "İscehisar", "Kızılören", "Merkez", "Sandıklı", "Sinanpaşa", "Sultandağı", "Şuhut"],
        "Ağrı" => ["Diyadin", "Doğubayazıt", "Eleşkirt", "Hamur", "Merkez", "Patnos", "Taşlıçay", "Tutak"],
        "Amasya" => ["Göynücek", "Gümüşhacıköy", "Hamamözü", "Merkez", "Merzifon", "Suluova", "Taşova"],
        "Ankara" => ["Akyurt", "Altındağ", "Ayaş", "Bala", "Beypazarı", "Çamlıdere", "Çankaya", "Çubuk", "Elmadağ", "Etimesgut", "Evren", "Gölbaşı", "Güdül", "Haymana", "Kahramankazan", "Kalecik", "Keçiören", "Kızılcahamam", "Mamak", "Nallıhan", "Polatlı", "Pursaklar", "Sincan", "Şereflikoçhisar", "Yenimahalle"],
        "Antalya" => ["Akseki", "Aksu", "Alanya", "Demre", "Döşemealtı", "Elmalı", "Finike", "Gazipaşa", "Gündoğmuş", "İbradı", "Kaş", "Kemer", "Kepez", "Konyaaltı", "Korkuteli", "Kumluca", "Manavgat", "Muratpaşa", "Serik"],
        "Artvin" => ["Ardanuç", "Arhavi", "Borçka", "Hopa", "Kemalpaşa", "Merkez", "Murgul", "Şavşat", "Yusufeli"],
        "Aydın" => ["Bozdoğan", "Buharkent", "Çine", "Didim", "Efeler", "Germencik", "İncirliova", "Karacasu", "Karpuzlu", "Koçarlı", "Köşk", "Kuşadası", "Kuyucak", "Nazilli", "Söke", "Sultanhisar", "Yenipazar"],
        "Balıkesir" => ["Altıeylül", "Ayvalık", "Balya", "Bandırma", "Bigadiç", "Burhaniye", "Dursunbey", "Edremit", "Erdek", "Gömeç", "Gönen", "Havran", "İvrindi", "Karesi", "Kepsut", "Manyas", "Marmara", "Savaştepe", "Sındırgı", "Susurluk"],
        "Bilecik" => ["Bozüyük", "Gölpazarı", "İnhisar", "Merkez", "Osmaneli", "Pazaryeri", "Söğüt", "Yenipazar"],
        "Bingöl" => ["Adaklı", "Genç", "Karlıova", "Kiğı", "Merkez", "Solhan", "Yayladere", "Yedisu"],
        "Bitlis" => ["Adilcevaz", "Ahlat", "Güroymak", "Hizan", "Merkez", "Mutki", "Tatvan"],
        "Bolu" => ["Dörtdivan", "Gerede", "Göynük", "Kıbrıscık", "Mengen", "Merkez", "Mudurnu", "Yeniçağa", "Seben"],
        "Burdur" => ["Ağlasun", "Altınyayla", "Bucak", "Çavdır", "Çeltikçi", "Gölhisar", "Karamanlı", "Kemer", "Merkez", "Tefenni", "Yeşilova"],
        "Bursa" => ["Büyükorhan", "Gemlik", "Gürsu", "Harmancık", "İnegöl", "İznik", "Karacabey", "Keles", "Kestel", "Mudanya", "Mustafakemalpaşa", "Nilüfer", "Orhaneli", "Orhangazi", "Osmangazi", "Yenişehir", "Yıldırım"],
        "Çanakkale" => ["Ayvacık", "Bayramiç", "Biga", "Bozcaada", "Çan", "Eceabat", "Ezine", "Gelibolu", "Gökçeada", "Lapseki", "Merkez", "Yenice"],
        "Çankırı" => ["Atkaracalar", "Bayramören", "Çerkeş", "Eldivan", "Ilgaz", "Kızılırmak", "Korgun", "Kurşunlu", "Merkez", "Orta", "Şabanözü", "Yapraklı"],
        "Çorum" => ["Alaca", "Bayat", "Boğazkale", "Dodurga", "İskilip", "Kargı", "Laçin", "Mecitözü", "Merkez", "Oğuzlar", "Ortaköy", "Osmancık", "Sungurlu", "Uğurludağ"],
        "Denizli" => ["Acıpayam", "Babadağ", "Baklan", "Bekilli", "Beyağaç", "Bozkurt", "Buldan", "Çal", "Çameli", "Çardak", "Çivril", "Güney", "Honaz", "Kale", "Merkezefendi", "Pamukkale", "Sarayköy", "Serinhisar", "Tavas"],
        "Diyarbakır" => ["Bağlar", "Bismil", "Çermik", "Çınar", "Çüngüş", "Dicle", "Eğil", "Ergani", "Hani", "Hazro", "Kayapınar", "Kocaköy", "Kulp", "Lice", "Silvan", "Sur", "Yenişehir"],
        "Edirne" => ["Enez", "Havsa", "İpsala", "Keşan", "Lalapaşa", "Meriç", "Merkez", "Süloğlu", "Uzunköprü"],
        "Elazığ" => ["Ağın", "Alacakaya", "Arıcak", "Baskil", "Karakoçan", "Keban", "Kovancılar", "Maden", "Merkez", "Palu", "Sivrice"],
        "Erzincan" => ["Çayırlı", "İliç", "Kemah", "Kemaliye", "Merkez", "Otlukbeli", "Refahiye", "Tercan", "Üzümlü"],
        "Erzurum" => ["Aşkale", "Aziziye", "Çat", "Hınıs", "Horasan", "İspir", "Karaçoban", "Karayazı", "Köprüköy", "Narman", "Oltu", "Olur", "Palandöken", "Pasinler", "Pazaryolu", "Şenkaya", "Tekman", "Tortum", "Uzundere", "Yakutiye"],
        "Eskişehir" => ["Alpu", "Beylikova", "Çifteler", "Günyüzü", "Han", "İnönü", "Mahmudiye", "Mihalgazi", "Mihalıççık", "Odunpazarı", "Sarıcakaya", "Seyitgazi", "Sivrihisar", "Tepebaşı"],
        "Gaziantep" => ["Araban", "İslahiye", "Karkamış", "Nizip", "Nurdağı", "Oğuzeli", "Şahinbey", "Şehitkamil", "Yavuzeli"],
        "Giresun" => ["Alucra", "Bulancak", "Çamoluk", "Çanakçı", "Dereli", "Doğankent", "Espiye", "Eynesil", "Görele", "Güce", "Keşap", "Merkez", "Piraziz", "Göreli", "Şebinkarahisar", "Tirebolu", "Yağlıdere"],
        "Gümüşhane" => ["Kelkit", "Köse", "Kürtün", "Merkez", "Şiran", "Torul"],
        "Hakkari" => ["Çukurca", "Derecik", "Yüksekova", "Şemdinli", "Merkez"],
        "Hatay" => ["Altınözü", "Antakya", "Arsuz", "Belen", "Defne", "Dörtyol", "Erzin", "Hassa", "İskenderun", "Kırıkhan", "Kumlu", "Payas", "Reyhanlı", "Samandağ", "Yayladağı"],
        "Isparta" => ["Aksu", "Atabey", "Eğirdir", "Gelendost", "Gönen", "Keçiborlu", "Merkez", "Senirkent", "Sütçüler", "Şarkikaraağaç", "Uluborlu", "Yalvaç", "Yenişarbademli"],
        "Mersin" => ["Akdeniz", "Anamur", "Aydıncık", "Bozyazı", "Çamlıyayla", "Erdemli", "Gülnar", "Mezitli", "Mut", "Silifke", "Tarsus", "Toroslar", "Yenişehir"],
        "İstanbul" => ["Adalar", "Arnavutköy", "Ataşehir", "Avcılar", "Bağcılar", "Bahçelievler", "Bakırköy", "Başakşehir", "Bayrampaşa", "Beşiktaş", "Beykoz", "Beylikdüzü", "Beyoğlu", "Büyükçekmece", "Çatalca", "Çekmeköy", "Esenler", "Esenyurt", "Eyüpsultan", "Fatih", "Gaziosmanpaşa", "Güngören", "Kadıköy", "Kağıthane", "Kartal", "Küçükçekmece", "Maltepe", "Pendik", "Sancaktepe", "Sarıyer", "Silivri", "Sultanbeyli", "Sultangazi", "Şile", "Şişli", "Tuzla", "Ümraniye", "Üsküdar", "Zeytinburnu"],
        "İzmir" => ["Aliağa", "Balçova", "Bayındır", "Bayraklı", "Bergama", "Beydağ", "Bornova", "Buca", "Çeşme", "Çiğli", "Dikili", "Foça", "Gaziemir", "Güzelbahçe", "Karabağlar", "Karaburun", "Karşıyaka", "Kemalpaşa", "Kınık", "Kiraz", "Konak", "Menderes", "Menemen", "Narlıdere", "Ödemiş", "Seferihisar", "Selçuk", "Tire", "Torbalı", "Urla"],
        "Kars" => ["Akyaka", "Arpaçay", "Digor", "Kağızman", "Merkez", "Sarıkamış", "Selim", "Susuz"],
        "Kastamonu" => ["Abana", "Ağlı", "Araç", "Azdavay", "Bozkurt", "Cide", "Çatalzeytin", "Daday", "Devrekani", "Doğanyurt", "Hanönü", "İhsangazi", "İnebolu", "Küre", "Merkez", "Pınarbaşı", "Seydiler", "Şenpazar", "Taşköprü", "Tosya"],
        "Kayseri" => ["Akkışla", "Bünyan", "Develi", "Felahiye", "Hacılar", "İncesu", "Kocasinan", "Melikgazi", "Özvatan", "Pınarbaşı", "Sarıoğlan", "Sarız", "Talas", "Tomarza", "Yahyalı", "Yeşilhisar"],
        "Kırklareli" => ["Babaeski", "Demirköy", "Kofçaz", "Lüleburgaz", "Merkez", "Pehlivanköy", "Pınarhisar", "Vize"],
        "Kırşehir" => ["Akçakent", "Akpınar", "Boztepe", "Çiçekdağı", "Kaman", "Merkez", "Mucur"],
        "Kocaeli" => ["Başiskele", "Çayırova", "Darıca", "Derince", "Dilovası", "Gebze", "Gölcük", "İzmit", "Kandıra", "Karamürsel", "Kartepe", "Körfez"],
        "Konya" => ["Ahırlı", "Akören", "Akşehir", "Altınekin", "Beyşehir", "Bozkır", "Cihanbeyli", "Çeltik", "Çumra", "Derbent", "Derebucak", "Doğanhisar", "Emirgazi", "Ereğli", "Güneysınır", "Hadim", "Halkapınar", "Hüyük", "Igın", "Kadınhanı", "Karapınar", "Karatay", "Kulu", "Meram", "Sarayönü", "Selçuklu", "Seydişehir", "Taşkent", "Tuzlukçu", "Yalıhüyük", "Yunak"],
        "Kütahya" => ["Altıntaş", "Aslanapa", "Çavdarhisar", "Domaniç", "Dumlupınar", "Emet", "Gediz", "Hisarcık", "Merkez", "Pazarlar", "Şaphane", "Simav", "Tavşanlı"],
        "Malatya" => ["Akçadağ", "Arapgir", "Arguvan", "Battalgazi", "Darende", "Doğanşehir", "Doğanyol", "Hekimhan", "Kale", "Kuluncak", "Pütürge", "Yazıhan", "Yeşilyurt"],
        "Manisa" => ["Ahmetli", "Akhisar", "Alaşehir", "Demirci", "Gölmarmara", "Gördes", "Kırkağaç", "Köprübaşı", "Kula", "Salihli", "Sarıgöl", "Saruhanlı", "Selendi", "Soma", "Şehzadeler", "Turgutlu", "Yunusemre"],
        "Kahramanmaraş" => ["Afşin", "Andırın", "Çağlayancerit", "Dulkadiroğlu", "Ekinözü", "Elbistan", "Göksun", "Nurhak", "Onikişubat", "Pazarcık", "Türkoğlu"],
        "Mardin" => ["Artuklu", "Dargeçit", "Derik", "Kızıltepe", "Mazıdağı", "Midyat", "Nusaybin", "Ömerli", "Savur", "Yeşilli"],
        "Muğla" => ["Bodrum", "Dalaman", "Datça", "Fethiye", "Kavaklıdere", "Köyceğiz", "Marmaris", "Menteşe", "Milas", "Ortaca", "Seydikemer", "Ula", "Yatağan"],
        "Muş" => ["Bulanık", "Hasköy", "Korkut", "Malazgirt", "Merkez", "Varto"],
        "Nevşehir" => ["Acıgöl", "Avanos", "Derinkuyu", "Gülşehir", "Hacıbektaş", "Kozaklı", "Merkez", "Ürgüp"],
        "Niğde" => ["Altunhisar", "Bor", "Çamardı", "Çiftlik", "Merkez", "Ulukışla"],
        "Ordu" => ["Akkuş", "Altınordu", "Aybastı", "Çamaş", "Çatalpınar", "Çaybaşı", "Fatsa", "Gölköy", "Gülyalı", "Gürgentepe", "İkizce", "Kabadüz", "Kabataş", "Korgan", "Kumru", "Mesaudiye", "Ünye", "Ulubey"],
        "Rize" => ["Ardeşen", "Çamlıhemşin", "Çayeli", "Derepazarı", "Fındıklı", "Güneysu", "Hemşin", "İkizdere", "İyidere", "Kalkandere", "Merkez", "Pazar"],
        "Sakarya" => ["Akyazı", "Arifiye", "Erenler", "Ferizli", "Geyve", "Hendek", "Karapürçek", "Karasu", "Kaynarca", "Kocaali", "Pamukova", "Sapanca", "Serdivan", "Söğütlü", "Taraklı"],
        "Samsun" => ["19 Mayıs", "Alaçam", "Asarcık", "Atakum", "Bafra", "Canik", "Çarşamba", "Havza", "İlkadım", "Kavak", "Ladik", "Salıpazarı", "Tekkeköy", "Terme", "Vezirköprü", "Yakakent"],
        "Siirt" => ["Baykan", "Eruh", "Kurtalan", "Merkez", "Pervari", "Şirvan", "Tillo"],
        "Sinop" => ["Ayancık", "Boyabat", "Dikmen", "Durağan", "Erfelek", "Gerze", "Merkez", "Saraydüzü", "Türkeli"],
        "Sivas" => ["Akıncılar", "Altınyayla", "Divriği", "Doğanşar", "Gemerek", "Gölova", "Gürün", "Hafik", "İmranlı", "Kangal", "Koyulhisar", "Merkez", "Suşehri", "Şarkışla", "Ulaş", "Yıldızeli", "Zara"],
        "Tekirdağ" => ["Çerkezköy", "Çorlu", "Ergene", "Hayrabolu", "Kapaklı", "Malkara", "Marmaraereğlisi", "Muratlı", "Saray", "Süleymanpaşa", "Şarköy"],
        "Tokat" => ["Almus", "Artova", "Başçiftlik", "Erbaa", "Merkez", "Niksar", "Pazar", "Reşadiye", "Sulusaray", "Turhal", "Yeşilyurt", "Zile"],
        "Trabzon" => ["Akçaabat", "Araklı", "Arsin", "Beşikdüzü", "Çarşıbaşı", "Çaykara", "Dernekpazarı", "Düzköy", "Hayrat", "Köprübaşı", "Maçka", "Of", "Ortahisar", "Sürmene", "Şalpazarı", "Tonya", "Vakfıkebir", "Yomra"],
        "Tunceli" => ["Çemişgezek", "Hozat", "Mazgirt", "Nazımiye", "Ovacık", "Pertek", "Pülümür", "Merkez"],
        "Şanlıurfa" => ["Akçakale", "Birecik", "Bozova", "Ceylanpınar", "Eyyübiye", "Halfeti", "Haliliye", "Harran", "Hilvan", "Karaköprü", "Siverek", "Suruç", "Viranşehir"],
        "Uşak" => ["Banaz", "Eşme", "Karahallı", "Merkez", "Sivaslı", "Ulubey"],
        "Van" => ["Bahçesaray", "Başkale", "Çaldıran", "Çatak", "Edremit", "Erciş", "Gevaş", "Gürpınar", "İpekyolu", "Muradiye", "Özalp", "Saray", "Tuşba"],
        "Yozgat" => ["Akdağmadeni", "Aydıncık", "Boğazlıyan", "Çandır", "Çayıralan", "Çekerek", "Kadışehri", "Merkez", "Saraykent", "Sarıkaya", "Sorgun", "Şefaatli", "Yenifakılı", "Yerköy"],
        "Zonguldak" => ["Alaplı", "Çaycuma", "Devrek", "Ereğli", "Gökçebey", "Kilimli", "Kozlu", "Merkez"],
        "Aksaray" => ["Ağaçören", "Eskil", "Gülağaç", "Güzelyurt", "Merkez", "Ortaköy", "Sarıyahşi", "Sultanhanı"],
        "Bayburt" => ["Aydıntepe", "Demirözü", "Merkez"],
        "Karaman" => ["Ayrancı", "Başyayla", "Ermenek", "Kazımkarabekir", "Merkez", "Sarıveliler"],
        "Kırıkkale" => ["Bahşılı", "Balışeyh", "Çelebi", "Delice", "Karakeçili", "Keskin", "Merkez", "Sulakyurt", "Yahşihan"],
        "Batman" => ["Beşiri", "Gercüş", "Hasankeyf", "Kozluk", "Merkez", "Sason"],
        "Şırnak" => ["Beytüşşebap", "Cizre", "Güçlükonak", "İdil", "Silopi", "Şirvan", "Merkez", "Uludere"],
        "Bartın" => ["Amasra", "Kurucaşile", "Merkez", "Ulus"],
        "Ardahan" => ["Çıldır", "Damal", "Göle", "Hanak", "Merkez", "Posof"],
        "Iğdır" => ["Aralık", "Karakoyunlu", "Merkez", "Tuzluca"],
        "Yalova" => ["Altınova", "Armutlu", "Çınarcık", "Çiftlikköy", "Merkez", "Termal"],
        "Karabük" => ["Eflani", "Eskipazar", "Merkez", "Ovacık", "Safranbolu", "Yenice"],
        "Kilis" => ["Elbeyli", "Musabeyli", "Merkez", "Polateli"],
        "Osmaniye" => ["Bahçe", "Düziçi", "Hasanbeyli", "Kadirli", "Merkez", "Sumbas", "Toprakkale"],
        "Düzce" => ["Akçakoca", "Cumayeri", "Çilimli", "Gölyaka", "Gümüşova", "Kaynaşlı", "Merkez", "Yığılca"]
    ];

    public static function getCities(): array
    {
        $cities = array_keys(static::$data);
        static::sortNames($cities);

        return array_combine($cities, $cities);
    }

    public static function getDistricts(?string $city): array
    {
        if (!$city) {
            return [];
        }

        $cityKey = static::getNeighborhoodCityIndex()[Str::slug($city)] ?? null;

        if ($cityKey === null) {
            return [];
        }

        $districts = array_keys(static::getNeighborhoodData()[$cityKey]);
        static::sortNames($districts);

        return array_combine($districts, $districts);
    }

    public static function getNeighborhoods(?string $city, ?string $district): array
    {
        if (!$city || !$district) {
            return [];
        }

        $data = static::getNeighborhoodData();
        $cityKey = static::getNeighborhoodCityIndex()[$citySlug = Str::slug($city)] ?? null;

        if ($cityKey === null) {
            return [];
        }

        $districtIndex = static::$neighborhoodDistrictIndexes[$cityKey] ??= collect(array_keys($data[$cityKey]))
            ->mapWithKeys(fn (string $name): array => [Str::slug($name) => $name])
            ->all();

        $districtKey = $districtIndex[Str::slug($district)] ?? null;

        // Older project records stored e.g. "Osmaniye Merkez". Resolve this
        // unambiguous parent alias for reading without rewriting the stored value.
        if ($districtKey === null && Str::slug($district) === $citySlug.'-merkez') {
            $districtKey = $districtIndex['merkez'] ?? $districtIndex[$citySlug] ?? null;
        }

        // Bazı veri kaynaklarında merkez ilçeler il adıyla tutulabildiği için geriye dönük destek.
        if ($districtKey === null && Str::slug($district) === 'merkez') {
            $districtKey = $districtIndex[$citySlug] ?? null;
        }

        if ($districtKey === null) {
            return [];
        }

        $neighborhoods = array_values(array_unique($data[$cityKey][$districtKey]));
        static::sortNames($neighborhoods);

        return array_combine($neighborhoods, $neighborhoods);
    }

    /** A stable ceiling also covers children loaded after the initial empty form. */
    public static function getOptionsLimit(): int
    {
        static $limit;

        if ($limit !== null) {
            return $limit;
        }

        $limit = count(static::$data);
        foreach (static::getNeighborhoodData() as $districts) {
            $limit = max($limit, count($districts));
            foreach ($districts as $names) {
                $limit = max($limit, count($names));
            }
        }

        return ++$limit; // Include the placeholder without truncating the last record.
    }

    /** Sort without depending on the host process locale; preserve stored names and keys. */
    public static function sortNames(array &$names): void
    {
        if (class_exists(\Collator::class)) {
            $collator = new \Collator('tr_TR');
            $collator->sort($names, \Collator::SORT_STRING);
            return;
        }

        // Deterministic Turkish alphabet fallback when ext-intl is unavailable.
        $alphabet = preg_split('//u', 'abcçdefgğhıijklmnoöprsştuüvyz', -1, PREG_SPLIT_NO_EMPTY);
        $weights = array_flip($alphabet);
        $key = static function (string $name) use ($weights): string {
            $name = mb_strtolower(strtr($name, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');
            return implode('', array_map(static fn (string $letter): string => isset($weights[$letter])
                ? sprintf('%03d', 100 + $weights[$letter]) : '0'.$letter,
                preg_split('//u', $name, -1, PREG_SPLIT_NO_EMPTY)));
        };
        usort($names, static fn (string $a, string $b): int => strcmp($key($a), $key($b)) ?: strcmp($a, $b));
    }

    protected static function getNeighborhoodData(): array
    {
        return static::$neighborhoodData ??= json_decode(
            file_get_contents(resource_path('data/turkey-neighborhoods.json')),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    protected static function getNeighborhoodCityIndex(): array
    {
        return static::$neighborhoodCityIndex ??= collect(array_keys(static::getNeighborhoodData()))
            ->mapWithKeys(fn (string $name): array => [Str::slug($name) => $name])
            ->all();
    }
}
