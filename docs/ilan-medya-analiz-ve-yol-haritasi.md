# İlan ekleme/düzenleme: gereksinim analizi ve güvenli yol haritası

Tarih: 4 Ekim 2026

Bu analizden sonra uygulama başlatıldı. Tamamlanan değişiklikler, testler ve dağıtım gereksinimleri için [uygulama notlarına](ilan-medya-uygulama.md) bakın.

Bu rapor, kullanıcının sağladığı gereksinim dokümanı ile çalışma alanındaki mevcut kodun karşılaştırılmasıdır. Talep analiz ve yol haritası olduğu için uygulama kodu değiştirilmedi, migration çalıştırılmadı, mevcut veriler silinmedi. “Fazlalıklar” aşağıda tespit olarak listelenmiştir; kod silme işlemi yapılmamıştır.

İnceleme yerel kaynak kodu, yüklü Filament/Livewire kaynakları, etkin yerel yapılandırma ve mevcut otomatik testlerle sınırlıdır. Canlı sitenin oturumlu ekranları, production PHP-FPM/web sunucusu ayarları, gerçek cihazlar ve gerçek EİDS bağlantısı doğrulanmadı. Yereldeki kod ile production sürümünün aynı olduğu varsayılmamalıdır.

## 1. Sonuç ve gereksinim karşılaştırması

| Gereksinim | Mevcut durum | Eksik / yapılacak işlem |
|---|---|---|
| Satılık Daire/Ev Ada–Parsel | `block_number` ve `parcel_number` kolonları ve model alanları mevcut. Aktif formda sadece Arsa/Tarla bölümünde görünür. | Mevcut alanların görünürlüğünü Satılık Daire/Müstakil Ev için genişletmek. Yeni kolon gerekmiyor. |
| Ada–Parsel düzenleme ve detay | Mevcut form alanları modelle bağlı. Detayda birleşik “Ada / Parsel” satırı var. | Konut düzenleme ekranında görünürlük; detayda değerleri ayrı etiketlerle, biri boş olsa da doğru göstermek. |
| Fotoğraf yükleme sonrası durum / silme | Filament FileUpload mevcut. Kesintisiz “Yükleniyor” sorunu tarayıcıda yeniden üretilmedi. | Önce gerçek hata isteğini belirlemek; yükleme, önizleme ve silme adımlarını ayrı incelemek. |
| Fotoğraf kayıtlarının korunması | Her kaydetmede ilan görsellerinin tamamı DB’den silinip yeniden oluşturuluyor. | Yalnızca çıkarılan kayıtları silmek; kalan kayıtların kimliklerini korumak; sıra/kapak bilgisini güncellemek. |
| Cephe çoklu seçim | `Select::make('facade')` tekli; DB’de nullable string; array cast yok. Detayda doğrudan string gösteriliyor. | Eski tekli değerleri koruyarak form, saklama ve detay gösterimini birlikte çoklu hale getirmek. |
| İlan videosu dosyası | İlan formu/modeli/tabloda sadece `video_url` var. | İlan için ayrı dosya alanı ve nullable dosya yolu; süre, tür, boyut doğrulaması; gösterim ve silme. |
| En fazla 60 saniye | İlan dosya videosu olmadığı için kontrol yok. | Backend’de güvenilir metadata okuması; frontend’de erken uyarı; süre okunamazsa reddetme. |
| Fotoğraf limitleri | 30 fotoğraf, fotoğraf başına 15360 KB (15 MB). Filament bu kontrolleri frontend ve backend’de uygular. | Korunmalı; yeni ve rastgele sayı belirlenmemeli. Limit aşımı ve mevcut medya ile toplam sayı test edilmeli. |
| Video boyut limiti | Projelerde varsayılan 204800 KB (200 MB) var; ilanlara özel video limiti yok. | Sunucu kapasitesi doğrulanarak ilan için yapılandırılabilir limit. Proje limitini doğrudan ilan limiti kabul etmemek. |
| YouTube | Formda `video_url`, detayda YouTube embed veya bağlantı mevcut. | Alanı, eski kayıtları ve çalışma şeklini korumak. |
| Yeni/düzenleme/admin | CreateListing ve EditListing aynı ListingResource/form şemasını kullanıyor. Admin ve portal aynı kaynak sınıfını keşfediyor. | Ortak aktif şemada düzenlemek; iki paneli ve iki işlemi ayrı test etmek. |
| Güvenli migration | Ada–Parsel zaten var; cephe string; ilan video yolu yok. | Yalnızca gerekli ek alanlar. Eski alan/veri silinmemeli. |
| EİDS | Ada–Parsel mevcut resmî alan kilidinin parçası. | Alanların görünürlüğü artırılırken mevcut kilit, doğrulama ve yayın davranışı aynen kalmalı. |

Aktif kategoride “Ev” karşılığı varsayılan olarak `Müstakil Ev`. Kategoriler yönetim ayarlarından değiştirilebildiği için production seçenekleri de okunmalı; yalnızca “Ev” adında yeni kategori eklemek çözüm değildir.

## 2. Bulguların kod karşılıkları

Dosyalar depo köküne göre verilmiştir.

| Dosya | Rol / bulgu |
|---|---|
| `app/Filament/Portal/Resources/ListingResource.php` | Aktif form `ListingFormSchema::make()` ile kuruluyor. |
| `.../ListingResource/Schemas/ListingFormSchema.php` | Aktif adımlar BasicInfoStep, RealEstateDetailsStep, MediaStep. Eski private adım metotları hâlâ dosyada duruyor. |
| `.../Schemas/Steps/RealEstateDetailsStep.php` | Cephe tekli; Ada–Parsel Arsa/Tarla içinde; doğrulanmış ilanda alanlar disabled. |
| `.../Schemas/Steps/MediaStep.php` | 30/15 MB fotoğraf sınırı; ilişkiden yükleme; toplu silip yeniden oluşturma; yalnızca YouTube linki. |
| `app/Models/Listing.php` | Ada–Parsel, facade ve video_url fillable; facade için çoklu cast yok. Görseller sort_order ile sıralanıyor. |
| `app/Models/ListingImage.php` | Görsel kayıtları için model; fiziksel dosya silme davranışı tanımlı değil. |
| `resources/views/listings/show.blade.php` | Cephe string, Ada–Parsel birleşik, YouTube gösterimi mevcut. |
| `app/Observers/ListingObserver.php` | EİDS doğrulanmış veya yayınlanmış ilanda resmî alanları kilitler. Alternatif request tabanlı görsel job yolu da içerir. |
| `app/Jobs/ProcessListingImage.php` | Alternatif görsel yolunda public disk, WebP dönüşümü ve ham dosya silme. Aktif FileUpload callback’i bu job’u doğrudan çağırmıyor. |
| `config/projects.php` | Proje videosu boyut/tür ayarları; ilan ayarı değil. |
| `app/Providers/AppServiceProvider.php` | Livewire geçici yükleme boyutunu proje video limitinden global olarak alıyor. |
| `app/Providers/Filament/{AdminPanelProvider,PortalPanelProvider}.php` | Her iki panel de Portal ListingResource kaynağını keşfediyor. |

## 3. Fotoğraf sorunu: kesin bulgular ve açık teşhis

**Kesin bulgu:** MediaStep kaydetme callback’i `$record->images()->delete()` ile bütün görsel satırlarını siliyor; sonra mevcut state üzerinden yeniden oluşturuyor. Bu, tek fotoğraf silme işlemini gereksiz şekilde bütün kayıtların yenilenmesine dönüştürüyor. State eksik/boş dönerse daha geniş veri kaybı riski var. Mevcut callback içinde transaction veya fiziksel dosya temizliği tanımlı değil; dış işlem sınırları ayrıca kontrol edilmeli.

**Kesin bulgu:** Kalıcı dosyalar için özel fiziksel silme callback’i bulunmuyor. Filament’in varsayılan kaldırma davranışı dosyayı form state’inden çıkarır; kalıcı dosyayı diskten otomatik silmez. Böylece DB kaydı kaldırılırken disk üzerinde sahipsiz dosya kalabilir. Kullanıcı formu kaydetmeden iptal edebileceğinden, diskten erken silme de mevcut ilana zarar verebilir.

**Kesin olmayan nokta:** Sonsuza kadar “Yükleniyor” durumunun gerçek nedeni henüz doğrulanmadı. Fotoğrafları toplu yeniden oluşturma, bu göstergenin nedenini tek başına kanıtlamaz.

Yerel etkin Filament diski `public`, varsayılan Laravel diski `local`. MediaStep diskini açıkça belirtmiyor; Filament ayarını kullanıyor. Dolayısıyla yerelde yanlış diske kayıt yapıldığı iddiası desteklenmiyor. `public/storage` bağlantısı mevcut. Buna rağmen production disk/URL/erişim ayarları ayrıca kontrol edilmeli.

Yüklü Filament sürümünde kayıtlı dosya önizlemesi `fetch(source)` ile dosya URL’sinden alınıyor. Bu yolda başarısız fetch için görünür özel hata yönetimi bulunmaması, önizleme isteği başarısız olduğunda incelemeye değer. Vendor kodunu değiştirmek yerine önce uygulama/ortam kaynaklı hata aranmalı.

Teşhis sırası:

1. Yeni ilanda küçük tek görsel, ardından çoklu görsel yükle; düzenlemede mevcut görselin açılmasını ayrı dene.
2. Geçici upload isteği, Livewire tamamlanma çağrısı ve kalıcı önizleme isteğinin sonuçlarını ayrı kaydet. Network/Console ile Laravel logunu eşleştir.
3. HTTP hata kodları, yanlış URL/HTTPS, erişim/CORS, geçici upload süresi, dosya izinleri ve eksik dosyayı kontrol et. Bunlar kontrol başlıklarıdır, doğrulanmış neden değildir.
4. Dosya URL’sinin gerçek görsel döndürdüğünü doğrula; 200 dönen HTML hata sayfasını başarı sayma.
5. FileUpload state anahtarlarının ve silme/sıralama çağrılarının tutarlı olduğunu kontrol et.
6. Bulguyla uyumlu en dar düzeltmeyi yap; önizlemeyi kapatarak veya göstergeyi gizleyerek sorunu örtme.

## 4. Fazlalıklar ve kapsam dışında tutulacak işler

| Tespit | Öneri |
|---|---|
| ListingFormSchema içinde aktif adımların eski private kopyaları | Bu talep için silmek zorunlu değil. Değişikliği aktif Steps dosyalarında yap; kullanılmayan kopyayı düzelterek sorunun çözüldüğünü sanma. Temizlik ayrı tutulmalı. |
| Aynı ilan için bütün fotoğraf satırlarının her kayıtta yenilenmesi | Medya düzeltmesi kapsamında fark bazlı güncellemeye geçmek gerekli. |
| Observer/ProcessListingImage alternatif görsel yolu | Çağrı kanıtı olmadan silme. FileUpload akışıyla eşzamanlı çalışıp mükerrer kayıt/dosya oluşturmadığını kontrol et. |
| Global Livewire geçici limitinin proje video ayarına bağlı olması | Kapsamı ilanla sınırlı düşünerek global limiti düşürme; proje yüklemeleri de etkilenir. Yeni ilan limiti alan düzeyinde uygulanmalı. |
| Proje video alanının dosya ile YouTube arasında karşılıklı yasak uygulaması | Bu davranışı ilanlara taşımamak gerekir. İlan YouTube özelliği korunmalı; yeni alan mevcut linki temizlememeli veya zorunlu olarak dışlamamalı. |
| Yeni Ada–Parsel kolonları veya yeni ilan oluşturma API’si | Gereksiz. Mevcut kolonlar ve Filament kayıt akışı kullanılmalı. Depoda bağımsız ilan yazma API’si tespit edilmedi. |

İnceleme başlangıcında çalışma alanında EidsService, EidsAndLocationStep, EidsServiceTest ve çeşitli ilan dosyalarında önceden yapılmış değişiklikler vardı. Bunlar bu analizde üretilmedi ve geri alınmadı. Sonraki uygulama patch’i bu başlangıca göre karşılaştırılmalı; eski değişikliklerle karıştırılmamalı.

## 5. Sistemi koruyarak uygulama sırası

### Aşama 0 — Başlangıç ve geri dönüş hazırlığı

- Production sürümü ile incelenen sürümü eşleştir; mevcut değişikliklerin ayrı snapshot’ını al.
- DB ve medya dosyası yedeği al, geri yükleme prosedürünü doğrula; testleri staging’de çalıştır.
- EİDS controller/service/route/middleware/token ve doğrulama kodunu değişiklik dışı sınır olarak belirle. Ortak ilan model/formunda değişiklikler mevcut kilit davranışını aşmamalı.
- Production PHP-FPM, web sunucusu/proxy, storage kapasitesi, upload timeout ve metadata okuyucusunu kontrol et.

### Aşama 1 — Fotoğraf hatasını teşhis et ve kayıt yönetimini düzelt

- Önce yukarıdaki adımlarla takılan isteği ve nedenini doğrula.
- Mevcut public disk kullanımını uygulama seviyesinde açık hale getirmek değerlendirilebilir; doğru URL/erişim sorununu gider.
- Eski/yeni medya yollarını karşılaştır; kalan satırların ID’lerini koru, yeni yollar için kayıt ekle, sadece çıkarılanları sil. Kapak ve sıralamayı güncelle.
- Güncelleme atomik olmalı. Fiziksel dosyayı yalnızca başarılı kayıt sonrasında, başka kayıt kullanmıyorsa ve ilanın yetkili medya alanındaysa temizle. Paylaşılan seed/default dosyalarını silme.
- Kaydetmeden iptal durumunda mevcut ilan medyası korunmalı. Boş state ile kasıtlı “tümünü kaldır” işlemi ile yükleme/hydration hatası ayrılmalı.
- Yeni ilandaki geçici dosyayı kaldırma, mevcut dosyayı kaldırma, son fotoğrafı kaldırma ve tekrar ekleme ayrı kabul kriterleri olmalı.

### Aşama 2 — Ada–Parsel görünürlüğünü genişlet

- Arsa/Tarla alanlarını koruyarak aynı state alanlarını Satılık Daire/Müstakil Ev için ortak görünür bölüme taşı; aynı state adına sahip iki aktif input oluşturma.
- Pafta/Kaks/Gabari/İmar alanlarının mevcut kategori davranışı korunmalı.
- String alanları kullan; max uzunluk DB ile uyumlu olmalı. Özel bir sayısal dönüşüm ekleyerek eski değerleri değiştirme.
- EİDS doğrulanmış ilanlarda disabled davranışı ve backend kilitleri aynı kalmalı. Eksik resmî alanın kilit nedeniyle doldurulamaması halinde EİDS kuralını gevşetme.
- Detayda “Ada” ve “Parsel” değerlerini ayrı göster; `0` gibi değerleri boş sayma.

### Aşama 3 — Cepheyi geriye uyumlu çoklu seçime dönüştür

Tercih edilen korumacı yaklaşım: eski `facade` kolonunu ve içeriğini koruyup nullable JSON `facades` alanı eklemek. Yeni alan doluysa onu, null ise eski tekli değeri tek elemanlı dizi olarak okumak. Bu alanlar tek bir uyumluluk katmanı üzerinden yönetilmeli; iki bağımsız cephe sistemi oluşmamalı.

- Formda multiple seçim ve backend’de dizi/eleman/tanınan seçenek doğrulaması uygula.
- Yeni çoklu değerleri `facades` içinde sakla. Eski string doğrudan array cast’e geçirilmemeli.
- Eski farklı/custom cephe değerlerini görünür tut; yönetim seçeneklerinden çıkarılan değerleri otomatik silme.
- `null` eski değere geri dönüş anlamına gelmeli; `[]` kullanıcının bütün cepheleri kaldırdığını ifade etmeli. Bu ayrım olmazsa silinen cephe geri görünebilir.
- Detay tüm değerleri okunabilir şekilde birleştirmeli.
- Sadece `facade` tüketen diğer okuyucular varsa tespit et ve uyumluluk stratejisini birlikte doğrula.
- İlk dağıtımda zorunlu toplu backfill yapma; gerekirse ayrıca kontrollü/idempotent veri aktarımı planla. Rollback sırasında yeni çoklu veriyi kaybetmemek için kolonu düşürmek yerine uygulama geri dönüşü tercih et.

### Aşama 4 — İlan dosya videosunu ekle

- Nullable `video_path` alanını ekle; `video_url` aynı kalmalı. Fotoğraf FileUpload bileşeninden ayrı bir dosya alanı kullan.
- Backend metadata okuyucusuyla gerçek süreyi doğrula: 60 saniye kabul, 60 saniyeyi aşan değer ret. Süre bilinmiyor, dosya bozuk veya araç yoksa kabul etme.
- Yerelde `ffprobe` mevcut; production’da varlığı ve çalışabilirliği henüz bilinmiyor. Dosya yolu shell’e güvenli argüman olarak verilmeli, metadata okuması timeout ile sınırlandırılmalı.
- Başlangıçta tarayıcı uyumluluğu yüksek MP4/H.264 ve ihtiyaç doğrulanırsa WebM değerlendir. Uzantı/MIME tek başına codec uyumluluğunu garanti etmez. Telefon MOV/HEVC dosyaları için altyapı desteklemiyorsa anlaşılır uyarı ver; kapsam dışı dönüştürme sistemi ekleme.
- Frontend süre/boyut uyarısı erken geri bildirim sağlamalı; backend kontrolleri atlanamamalı.
- Tek ilan videosu; silme/değiştirme başarılı kayıt sonrası temizlenmeli. Yeni yükleme başarısızsa eski video korunmalı.
- Detayda controls, playsinline ve metadata preload ile mevcut tasarım içinde göster. YouTube alanını ve gösterimini koru; linki otomatik silme.

### Aşama 5 — Limitleri doğrula ve tek kaynaktan yönet

- Mevcut 30 fotoğraf/15 MB limitini koru; toplam sınır mevcut + yeni dosyalar üzerinden test edilmeli.
- İlan video boyutunu mevcut 200 MB proje limiti, production upload zinciri ve depolama kapasitesine göre belirle. Bu rapor production için yeni kesin MB sınırı atamaz.
- Yerel CLI PHP: upload_max_filesize 512M, post_max_size 512M, max_file_uploads 20. Bunlar PHP-FPM/production kanıtı değildir; FileUpload’ın paralel/ayrı istek davranışı nedeniyle 20 değeri ilan toplam sınırı anlamına gelmez.
- Yerel Livewire geçici kuralı `required`, `file`, `max:204800`; varsayılan geçici upload süresi 5 dakika. Süre ve proxy timeout’ları büyük mobil upload için ayrıca kontrol edilmeli.
- Yeni medya ayarlarını alan bazlı ortak konfigürasyondan kullan; projelerin global limitini ve davranışını bozma.

### Aşama 6 — Kabul testleri ve dağıtım

- Önce nullable alan migration’ları, ardından bunları kullanan kod dağıtılmalı. DROP/recreate, eski cepheyi silme veya Ada–Parsel için yeni kolon açma yapılmamalı.
- Yeni alanları ilk dağıtımda tutmak, kod rollback’i sırasında yeni medyayı/veriyi korumayı kolaylaştırır.
- Yeni/düzenleme/admin/portal testleri bitmeden production’a geçilmemeli. Production sonrası aynı ID, eski ilan ve medya erişimi kontrol edilmeli.

## 6. Dokümandaki 20 senaryonun kabul matrisi

| No | Senaryo | Başarı ölçütü |
|---|---|---|
| 1 | Satılık Daire oluştur | Mevcut taslak/izin akışı ile tek ilan oluşur. |
| 2 | Ada–Parsel gir/kaydet | DB, yeniden açılan form ve detay aynı değerleri gösterir. |
| 3 | Birden fazla cephe | Tüm seçimler kaydedilir. |
| 4 | Çoklu fotoğraf | Mevcut 30/15 MB kurallarıyla yüklenir. |
| 5 | Yükleme tamamlanması | Gösterge kapanır; hata varsa sonlanır ve anlaşılır hata görünür. |
| 6 | Bir fotoğraf sil | State/önizlemeden kalkar; kayıt sonrası sadece ilgili medya kaldırılır. |
| 7 | Diğer fotoğraflar | İçerik ve kayıt kimlikleri korunur; kapak/sıra tutarlıdır. |
| 8 | Video ≤60 saniye | 59 ve 60 saniyelik geçerli dosyalar kabul edilir. |
| 9 | Video >60 saniye | 60 saniyeyi küçük farkla aşan ve 61 saniyelik dosyalar backend’de reddedilir. |
| 10 | YouTube | Eski link özelliği aynı şekilde çalışır; dosya eklemek linki silmez. |
| 11 | Kaydet | Doğru ilan, medya ve cepheler saklanır. |
| 12 | Detay | Ada/Parsel, tüm cepheler, fotoğraf/video ve link doğru görünür. |
| 13 | Tekrar düzenle | Saklanan değerler ve medya eksiksiz yüklenir. |
| 14 | Cephe ekle/çıkar | Son seçimler saklanır; tümünü kaldırma eski cepheyi geri getirmez. |
| 15 | Düzenlemede fotoğraf | Ekle/sil/sırala, son fotoğrafı sil ve yeniden ekle çalışır. |
| 16 | Aynı ilan güncellenir | ID aynı kalır; ilan sayısı artmaz. |
| 17 | Mobil | Android/iOS gerçek cihazda dosya seçme, video, silme ve uyarılar çalışır. |
| 18 | Admin | Admin ve portalda oluşturma/düzenleme/medya erişimi doğru olur. |
| 19 | Eski ilanlar | Tek cephe/null/özel değer; videosuz/YouTube’lu; konut/arsa örnekleri bozulmaz. |
| 20 | EİDS | Kod farkı kontrolü, mevcut otomatik regresyonlar ve yetkili staging doğrulaması geçer; resmî alan kilitleri korunur. |

Ek sınır testleri: 30/31 fotoğraf, tam boyut sınırı/sınır+1, yanlış MIME/codec, okunamayan süre, upload iptali, bağlantı kesilmesi, video değiştirme hatası, formu kaydetmeden çıkma, yetkisiz başka ilan medyasını silme denemesi.

## 7. Bu incelemede gerçekleştirilen doğrulama

Çalıştırılan mevcut test grupları: EidsCallbackTest, EidsServiceTest, ListingApprovalWorkflowTest, ListingPropertyIdTest, ListingPublicationTest ve PlatformListingOwnerTest.

**Sonuç: 24 test geçti, 196 assertion; yaklaşık 7,72 saniye.** Testler phpunit.xml ile SQLite bellek veritabanında çalışır. EİDS servis testleri sahte HTTP yanıtları kullanır; sonuç gerçek EİDS/production bağlantısı için garanti değildir.

Bu sonuç mevcut ilan oluşturma/düzenleme, taşınmaz ID korunması, yayın görünürlüğü, sahiplik ve EİDS kilit davranışı için başlangıç kanıtıdır. Fotoğrafın tarayıcıdaki tamamlanması/silinmesi, çoklu cephe, ilan dosya videosu/60 saniye ve mobil kabul senaryoları bu testlerle doğrulanmış değildir. Uygulama aşamasında bu alanlara yönelik testler eklenmelidir.

Öncelik sırası: **fotoğraf kök nedenini doğrula ve medya kayıtlarını koru → Ada–Parsel görünürlüğü → geriye uyumlu çoklu cephe → 60 saniyelik dosya videosu → bütün kabul testleri → kontrollü dağıtım.**
