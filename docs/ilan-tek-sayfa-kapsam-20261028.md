# Tek sayfa ilan girişi — 28 Ekim 2026 çalışma kaydı

**Güncel durum:** Tek sayfa akışı yerelde uygulandı ve aşağıdaki testlerle doğrulandı. Aşama 1'in bütün kabul maddeleri tamamlanmış sayılmıyor; açık bağımlılıklar ve kapsam farkları en altta listelenmiştir. Canlı dağıtım yapılmadı. Kaynak kök dizindir; `.live-source` ayrı ve farklı bir kopyadır. Kök dosyalar Git açısından izlenmiyor; toplu commit yapılmadı.

**Açık kullanıcı onayı:** Kullanıcı, somut taslak/EİDS durum alanları/onaya gönderme bağlantısı değişikliği için “tamam onay veriyorum” dedi. Aşağıdaki ilk inceleme ve izin bekleme bölümleri tarihçedir. EİDS servis protokolü, SSO/callback, servis ayarları, OTP ve resmî alanların sunucu kilitleri değiştirilmedi. EİDS durum gösterimi kayıt üzerinden okunuyor; taşınmaz alanı doğrulanmış kayıtta arayüzde de kilitli.

**Yerel inceleme:** `http://127.0.0.1:8792/__local-form-review` yalnız test veritabanındaki inceleme hesabını açar. Üretim hesabı veya canlı ilan değildir. Sunucu bu çalışma oturumunda başlatıldı. Normal uygulama adresleri `/portal/listings/create` ve `/admin/listings/create`; bunlar geliştirme ortamında yeni migration uygulandıktan sonra yeni formu kullanır.

Talep metni ve sohbet içindeki 15 görüntü incelendi. Aşağıdaki dosya adlarının ortak öneki `Ekran görüntüsü 2026-10-27 `, uzantısı `.png` şeklindedir. Kapalı listelerin seçenekleri ve kapalı özellik gruplarının içeriği görüntüden doğrulanmış sayılmamıştır. Görsellerdeki kişisel/örnek değerler uygulamaya aktarılmadı.

## Mevcut mimari ve sınırlar

- Laravel 12, Filament 3, Livewire; Vite/Tailwind. `ListingFormSchema::make()` üç Wizard adımı kullanıyor: BasicInfoStep, RealEstateDetailsStep, MediaStep. Aynı kaynak admin/portal yüzeylerinde kullanılıyor.
- `CreateListing` sahip/danışman ilişkisini ve kotayı denetleyerek pasif taslak oluşturuyor. `EditListing` son girdileri kaydedip `ListingPublicationService::submit()` çağırıyor. EİDS taşınmaz sorgusu bu serviste yapılıyor. Başarılı gönderim `pending_approval`; yalnız yetkili yönetici onayı `approved` ve aktif durum oluşturuyor.
- Resmî alan kilitleri hem formda hem `ListingObserver` içinde mevcut. Yayından sonra kategori/işlem/sahip/kaynak ve resmî alanlar; doğrulama sonrasında resmî adres/ada/parsel korunuyor. Mevcut 48 saat kuralı yalnız son alt kategoride uygulanıyor; bu görevde eklenmedi.
- Kategoriler ayrı kimlikli bir ağaç yerine `Listing.category`, `subcategory`, `type` metinleri ve `Setting` seçenek listeleriyle çalışıyor. Yeni ağaç eski değerleri korumalı; görüntüdeki ana gruplar doğrudan mevcut kategori kimliği kabul edilemez.
- Konum: `TurkeyAddressService` bağlı il/ilçe/mahalle listeleri. İlan modelinde koordinat ve konum gizleme alanı yok; detay sayfasında konum metniyle Google Maps arama bağlantısı var. Proje modelinin harita altyapısı ilan modeliyle aynı kabul edilmedi.
- Medya: public disk, kullanıcıya ait yükleme yolu, `ListingMediaService` ile sıralama/kapak ve ilişkisel kayıt; kullanılan dosyayı başka ilandan alma denetimi ve commit sonrası temizlik mevcut. `ListingVideo` ffprobe ile süre/içerik kontrol ediyor. Mevcut video MP4/WebM, 60 saniye; MOV/3GP dönüşümü yok.
- Açıklama `ListingDescriptionHtml` ile halka açık gösterimde temizleniyor. Mevcut araçlar renk, yazı biçimi ve hizalama desteği içeriyor; yeni kapsam için ek güvenlik testleri gerekecek.
- Form giriş/kota/rol politikaları korunmalı. EİDS SSO/callback rotaları ve mevcut OTP/giriş mekanizması değişmedi.

## İlk incelemedeki görsel kapsam tablosu (uygulama öncesi)

| Görsel | Bölüm | Alan/seçenek | Zorunluluk/koşul | Mevcut karşılığı | Yapılacak işlem | Test sonucu |
|---|---|---|---|---|---|---|
| 164007 | Kategori başlangıcı | Konut, İş Yeri, Arsa, Bina, Devre Mülk, Turistik Tesis; beş hızlı seçim | Referanstaki adım göstergesi ZYD'de zorunlu adım olmayacak | category/type Setting listeleri | Eski değerleri koruyan ağaç, hızlı seçim ve tek sayfa | Henüz uygulanmadı |
| 164113 | Konut/Satılık | Daire, Rezidans, Müstakil Ev, Villa, Çiftlik Evi, Köşk & Konak, Yalı, Yalı Dairesi, Yazlık, Kooperatif; tamamlandı kartı | Alt kategori seçimi; referanstaki Devam düğmesi alınmayacak | category/subcategory | Geçerli tür eşleştirmesi ve seçili yol | Henüz uygulanmadı |
| 164138 | Konut/Kiralık | Daire, Rezidans, Müstakil Ev, Villa, Çiftlik Evi, Köşk & Konak, Yalı, Yalı Dairesi | Kiralık seçimine bağlı; henüz alt tür seçilmemiş | type ve kategori koşulları | Kiralık alt türleri, ortak girdileri koruma | Henüz uygulanmadı |
| 164200 | Devren Satılık Konut | Daire, Villa | Devren işlemine bağlı | Varsayılan type Satılık/Kiralık | Eski kategorileri bozmadan yeni geçerli eşleştirme | Henüz uygulanmadı |
| 164251 | Tamamlanan kategori | Konut > Kiralık > Daire | Seçim tamamlanmış | category/type | Aynı sayfada yol ve değiştirme | Henüz uygulanmadı |
| 164325 | Hızlı seçim/arama | Satılık Arsa, Kiralık Daire, Satılık Daire, Satılık Müstakil Ev, Kiralık Dükkân & Mağaza; kelimeyle arama ve örnek | Arama veya hiyerarşik seçim alternatifi | Select araması mevcut; kategori yolu araması yok | Aynı şemaya bağlanan arama/hızlı seçim | Henüz uygulanmadı |
| 164538 | Başlık/açıklama/fiyat | Kategori yolu, Değiştir, başlık*, açıklama*, zengin editör; fiyat* ve TL; yardım bağlantıları | Yıldızlı alanlar referansta zorunlu | title, description, price, currency | Bölüm sırası, kategori kuralları, sayı girişi; mevcut tarihli ceza metni yeni tasarıma taşınmayacak | Mevcut açıklama testleri başlangıç paketinde |
| 164602 | Taşınmaz | Brüt m²*, net m²*, açık alan m², oda sayısı, bina yaşı*, ısıtma*, banyo sayısı*; açık alan/oda kilidi ve iki gün uyarısı | Yalnız görünen seçili değerler biliniyor, listeler kapalı | square_meters/net_square_meters/room_count/building_age/heating_type | Açık alan ve banyo alanı eksik; mevcut kilitler korunacak, referansa özgü kilit eklenmeyecek | Henüz uygulanmadı |
| 164624 | Taşınmaz devamı | Banyo*, mutfak*, balkon*, eşyalı, kullanım durumu*, aidat TL, krediye uygun, enerji kimlik belgesi, gayrimenkul sahibi kilitli | Listeler kapalı; seçenek kümesi belirsiz | furniture_status/usage_status/dues_price/credit_eligibility/owner_nationality | Mutfak/balkon/enerji eksik; aidat yalnız kiralıkta görünüyor, koşulu değerlendirilecek | Henüz uygulanmadı |
| 164650 | Tapu/adres | Gayrimenkul sahibi kilidi, tapu*, taşınmaz no kilitli, kimden kilitli, takas*; il/ilçe/mahalle kilitli; kelime araması; site içerisinde mi | Resmî/hesap alanlarını yetkisiz açma | deed_status/eids_document_no/from_who/swap_option ve adres alanları | EİDS kilitleri korunarak tapu/adres bölümleri; site tercihi eklenecek | Onay gerektiren bağlantılı kapsam |
| 164710 | Harita/özellik başlangıcı | Harita/Uydu, tam ekran, konuma git, +/-; konumu gizle; Cephe* | Referanstaki harita sağlayıcısı mevcut ilan altyapısı sayılmaz | Metinsel konum/harita arama bağlantısı; facades | Koordinat ve gizliliğin tüm halka açık çıktılarda uygulanması | Henüz uygulanmadı |
| 164732 | Özellik grupları/fotoğraf | Cephe*: Batı, Doğu, Güney, Kuzey; seçim yapmak istemiyorum. İç/Dış Özellikler*, Muhit*, Ulaşım*, Manzara*, Konut Tipi*, Engelliye ve Yaşlıya Uygun* kapalı. Fotoğraf iyileştirme tercihi ve bilgisayar/telefon yükleme | Kapalı grupların seçenekleri görünmüyor; boş ile vazgeçildi farklı | facades çoklu seçim; diğer gruplar yok | Gerçek proje seçeneklerinden şema; açık vazgeçme durumu; servis yoksa iyileştirme düğmesi ekleme | Henüz uygulanmadı |
| 164829 | Fotoğraf galerisi | Çoklu yükleme, 35/35, kapasite çubuğu, iyileştirme bildirimi/Tümünü iyileştir, Vitrin etiketi ve küçük resimler | Referans kapasitesi 35 | FileUpload + ListingMediaService; ilk görsel kapak | **Kapasite 35 yapıldı**; diğer medya kabul maddeleri ayrıca doğrulanacak. İyileştirme servisi tespit edilmedi | Sonuçlar aşağıda |
| 164927 | Video/sanal tur/not/iletişim | Bilgisayardan/sürükleyerek video; 3gp/mp4/mov, en fazla 1 dakika. Mevcut tur seç, Matterport/Immoviewer linki veya tursuz. Özel not ve açıklaması. Ad soyad, iki iş telefonu, cep telefonu | Video/tur isteğe bağlı; not yalnız yetkililer | video_path/video_url; hesap/ofis ilişkileri var; not/tur alanı yok | Dönüştürme gereksinimi, güvenli tur adresi, özel not ve hesap ilişkili iletişim | Henüz uygulanmadı |
| 164942 | İletişim/onay/gönderim | Telefon yayınlansın/yayınlanmasın; mesaj almak istemiyorum; seçilmemiş kurallar onayı; Devam Et | Referans metni/Devam Et alınmayacak; ZYD onay metni kullanılacak | Mevcut yayın öncesi accepted checkbox ve submit servisi | Tercihleri sunucuda uygula; Taslak/Önizle/İlanı Onaya Gönder tek sayfa | Onay gerektiren yayın bağlantısı |

## Uygulanan bağımsız değişiklik

`config/listings.php`: fotoğraf limiti 30 → 35. `MediaStep.php`: yardım metni ortak kapasite/boyut ayarından geliyor. `ListingMediaService.php`: sunucu hata metni aynı ayardan geliyor. `ListingMediaWorkflowTest.php`: 35 fotoğraf kabulü ve 36 fotoğraf reddi için mevcut sınır testleri güncellendi. Yeni migration yok; mevcut veri ve medya sırası/kapak kuralları değiştirilmedi.

## Önceden açık onay gerektiren somut kapsam

AGENTS.md: “Bir görev EİDS değişikliği gerektiriyorsa önce somut değişikliği açıklayıp kullanıcıdan açık onay al.”

Önerilen uyarlama: mevcut üç Wizard bölümünü aynı formdaki bölümlere dönüştürmek; yeni ilanı aynı sayfada taslak olarak kaydedip kimliğiyle çalışmaya devam etmek; mevcut `ListingPublicationService::submit()` çağrısını yeni sayfanın **İlanı Onaya Gönder** eylemine bağlamak; EİDS durum/taşınmaz/adres alanlarını aynı form düzenine taşımak. Eksik taslak kaydına izin veren kurallar yalnız taslağa uygulanmalı; onaya gönderirken tam doğrulama korunmalı. Servisin kendisi değiştirilmesi gerekirse fark ayrıca değerlendirilmeli.

Bu izin; EİDS servis protokolünü, callback/SSO/API rotalarını, anahtarları, ayarları, doğrulama kararlarını, resmî alan kilitlerini veya OTP mekanizmasını değiştirme izni değildir. Canlıya yayın, gerçek ilan gönderimi/silme yoktur. İzin alınana kadar bu akış dosyaları değiştirilmedi.

Yeni not, özellikler, iletişim tercihleri, harita ve taslak sürüm kontrolü için yalnız yeni veri alanları/tabloları hazırlamak gerekecek. EİDS tablolarına, alanlarına ve ilişkilerine dokunulmadan tasarlanmalı; eski ilanlara uydurma değer eklenmemeli. Bu aşamada migration hazırlanmadı/çalıştırılmadı.

## Doğrulama ve inceleme

Testler `output/ilan-form-20261028/sandbox` içindeki güncel kök kaynak kopyasında, SQLite `:memory:`, sahte depolama/mail ve kapalı URL stream'leriyle çalıştırılıyor. Üretim ortam dosyası kopyalanmadı. `.live-source/vendor` yalnız bağımlılık kaynağıdır; `.live-source` uygulaması değiştirilmedi.

İlk kurulum denemesinde sandbox paket keşif manifesti boş olduğundan Filament/Livewire bulunamadı; eksik public dosyaları da hata üretti. Bu sonuç uygulamanın başlangıç hatası sayılmaz. Paket manifesti ve yerel public JS/CSS kopyasıyla tekrar başlatıldı. Sonuç kaydı bu belgenin devamında yer alır.

Henüz yeni tek sayfa ekranı yoktur; mevcut ekran `/portal/listings/create` ve yetkili admin içindeki ilan oluşturma ekranıdır. Kullanıcıya tamamlanmış yeni ekran olarak sunulmaz. Yeni tasarımın masaüstü/tablet/telefon, gizlilik, otomatik taslak, önizleme, mükerrer gönderim ve gerçek entegrasyon kabul koşulları doğrulanmadı. Gerçek EİDS/OTP çağrısı yapılmadı.

### Gerçek sonuçlar

- Değişiklik öncesi tam paket: **220 test, 1417 doğrulama, 3 error, 3 failure, 3 skipped** (`output/ilan-form-20261028/baseline.txt` ve `baseline.xml`). Üç error sandbox'a kopyalanmamış demo resimleri/PWA/logo dosyalarından kaynaklandı. Bu dosyalar eklendikten sonra aynı üç test tekrar çalıştırıldı: **3 test / 55 doğrulama başarılı** (`fixture-recheck.txt`). Bu ortam sorunları ürün hatası olarak raporlanmaz.
- Üç başlangıç failure: `ListingDecimalPrecisionTest::test_decimal_values_survive_create_update_and_public_display`, `ListingMediaWorkflowTest::test_detail_keeps_legacy_and_new_values_and_both_video_sources`, `ListingPublicationTest::test_listing_detail_is_not_public_until_eids_is_verified`. Ortak neden: kökte bulunmayan `resources/views/partials/mobile-selected-details.blade.php` dosyasının mevcut ilan detayından çağrılması. Bu görevde bu dosya silinmedi ve sorun değiştirilmedi.
- Üç video testi ffprobe bulunmadığı için atlandı; gerçek 60 saniye sınırı/codec testi yerelde doğrulanmış sayılmaz.
- Değişiklik sonrası ilgili test seçimi: **5 test / 150 doğrulama başarılı** (`media-after.txt`, `media-after.xml`). İki panelde oluşturma/düzenleme, resim sıralama/kapak/silme, başka ilan dosyasını reddetme, 36 fotoğraf reddi, 35 fotoğrafın mevcut 15 MB sınırında kabulü, commit/rollback ve ortak dosya koruması kapsanıyor.
- Değişen dört PHP dosyasında `php -l` başarılı. Görsel yerleşim değiştirilmedi; masaüstü/tablet/telefon tarayıcı kabul testi yapılmadı. Yeni JS/CSS veya bağımlılık eklenmedi; derleme çalıştırılmadı.
- Tek sayfa, yeni alanlar, otomatik taslak ve önizleme henüz uygulanmadığından bu test sonuçları Aşama 1'in kabulü değildir.

Testi yeniden çalıştırmak için proje kökünde PowerShell:

```powershell
& 'output/ad-tools/php/php.exe' '.live-source/vendor/phpunit/phpunit/phpunit' -c 'output/ilan-form-20261028/sandbox/phpunit.xml' --bootstrap 'output/ilan-form-20261028/bootstrap.php' --filter 'test_photo_limits_are_enforced_on_the_server|test_thirty_five_photos_at_the_existing_size_limit_are_accepted|test_photo_and_video_paths_from_another_listing_are_rejected|test_both_panels_create_and_edit_housing_without_replacing_the_listing|test_media_cleanup_waits_for_commit_and_preserves_shared_files'
```

Bu komut test anındaki sandbox kopyasını sınar. Sonraki kaynak değişiklikleri testten önce bu kopyaya kontrollü aktarılmalıdır. Geri alma gerekirse yalnız `max_files` değerini 30'a ve ilgili sınır testlerini eski beklentilerine döndürmek yeterlidir; 35 fotoğraflı yeni kayıtlar varsa önce düzenleme etkisi değerlendirilmelidir. Veritabanı geri dönüşü yoktur.

## Onay sonrası uygulanan çalışma

- Üç Wizard adımı kaldırıldı. Kategori, başlık/açıklama/fiyat, taşınmaz, tapu/yetki, adres/harita, özellik, medya, özel not, iletişim ve doğrulama aynı formda. Eski ve kullanılmayan kopya şema yöntemleri kaldırıldı. Yeni bağımsız proje oluşturulmadı.
- Ana kategori → taşınmaz türü gruplaması, aranabilir tür seçenekleri, kategori yolu ve beş hızlı seçim mevcut metin değerlerini koruyor. Yeni konut türleri ve devren seçenekleri eklendi. Kategori değişimi uyarılı; sunucu uyumsuz konut/arsa/kiralık alanlarını kalıcı kayda taşımıyor. Kullanıcı taslak girdileri ayrıca korunuyor.
- Türkçe fiyat/alan girişi normalleştiriliyor; negatif, tutarsız net/brüt alan ve güvensiz tur bağlantıları reddediliyor. Açık alan, banyo, mutfak, balkon, enerji sınıfı, site tercihi ve kira dönemi `form_details` içinde saklanıyor. Eski kayıtlara yeni zorunlu varsayılanlar doldurulmadı.
- Kategoriye uygun mevcut alanlar, eski seçenekler, sahip/danışman ataması, kota ve resmî kilitler korunuyor. Aidat satılıkta da girilebiliyor; depozito yalnız kiralıkta. Doğrulama göstergesi tarayıcının gönderdiği statüye güvenmiyor.
- `listing_form_drafts` kullanıcıya bağlı, şifreli payload ve sürüm numarası tutuyor. Eksik bilgilerle manuel ve 45 saniyelik otomatik taslak kaydı; kayıt durumu/zamanı, taslağa dönüş bağlantıları ve iki sekme çakışma denetimi var. OTP/kimlik/servis yanıtı anahtarları payload beyaz listesinde yok. Tarayıcı depolamasına taslak/hassas doğrulama verisi yazılmıyor. URL yalnız rastgele taslak kimliğini taşıyor; her sorgu sahiplik denetimli.
- Oluşturma aynı formda kalıyor. Aynı taslak anahtarıyla yinelenen oluşturma tek ilanı kullanıyor. Önizleme güncel taslağı gerçek ilan detay görünümünde iframe içinde gösteriyor; özel notu, sahte EİDS başarısını ve otomatik yayını içermiyor. Taslak kayıt hatasında eski veriyle önizleme gösterilmiyor.
- Alt eylemler Taslak Kaydet / Önizle / İlanı Onaya Gönder. Düzenlemede ayrıca mevcut Değişiklikleri Kaydet eylemi korunuyor. Gönderim seçilmemiş açık onay kutusu istiyor ve mevcut üyelik sözleşmesi rotasına bağlanıyor. EİDS sonucu başarısızsa taslak korunuyor; başarıda yalnız `pending_approval`, yayın için ayrıca mevcut yönetici onayı gerekiyor. İlan satırı kilidi ve durum denetimi yinelenen onay bildirimini engelliyor.
- Fotoğraf limiti 35; sürükleme, kapak/sıra, küçük önizleme, FilePond ilerleme/yeniden deneme ve klavyeyle öne/arkaya/kapak eylemleri var. Yeni fotoğraflar sunucuda gerçek tür/boyut/ölçüyle doğrulanıp EXIF/GPS içermeyen JPEG olarak saklanıyor. Taslak medyası yenilemede korunuyor; mevcut ilan dosyaları yeniden yükleme istemiyor.
- MP4/WebM korunuyor; MOV/3GP için FFprobe doğrulaması ve FFmpeg ile MP4 dönüşüm yolu eklendi. Dönüşen kaynak video private diskte korunuyor. Eksik araçta açık hata veriliyor; sahte başarı yok. 60 saniye ve boyut sınırı mevcut sunucu kuralına bağlı.
- Özel not şifreli ve model serileştirmesinde gizli; yeni özel alanlar değişiklik geçmişine düz metin olarak yazılmıyor. Telefon gizliyse ilan telefon/WhatsApp bağlantıları ve bu ilan sayfasındaki genel telefon alanı gösterilmiyor; ilişkili model serileştirmesinden telefonlar çıkarılıyor. Koordinat ve açık adres JSON ayrıntısı herkese açık serileştirmede gizli; görünür harita bağlantısına yalnız gizleme kapalıyken koordinat konuyor.
- Yeni alanlar ilan detayında ortak `listing-extra-details` bileşeninde gösteriliyor. Başlangıçtaki eksik mobil parçaya ait üç test hatası `includeIf` ile giderildi; mevcut bir dosya silinmedi.

### Güncel görsel eşleme ve kabul sonucu

| Görsel sonu | Uygulanan karşılık | Sonuç / sınır |
|---|---|---|
| 164007 | Ana kategori, tür, hızlı seçim | Altı ana grup ve sekiz oluşturma senaryosu test edildi |
| 164113 | Satılık konut türleri ve kategori yolu | Aynı sayfa; referansın Devam düğmesi alınmadı |
| 164138 | Kiralık konut, kira koşulları | Kiralık daire ve işyeri test edildi |
| 164200 | Daire/Villa için Devren Satılık Konut | Tür eşleştirmesi tanımlı; gerçek EİDS kabulü sınanmadı |
| 164251 | Seçili yol ve aynı sayfada değiştirme | Tarayıcı hızlı seçim testi geçti |
| 164325 | Hızlı seçim ve aranabilir kategori | Ana grup/tür araması var; serbest “kiralık daire” ifadesini ayrıştıran ayrı arama motoru yok |
| 164538 | Başlık, güvenli editör, fiyat/para birimi | Mevcut açıklama ve hassas ondalık testleri geçti |
| 164602 | Brüt/net/açık alan, oda, yaş, ısıtma, banyo | Kayıt ve tutarlılık testleri geçti; kapalı listelerden seçenek uydurulmadı |
| 164624 | Mutfak, balkon, eşya, kullanım, aidat, kredi, enerji, sahibi | Kayıt/düzenleme destekleniyor; yeni alanların tamamı zorunlu yapılmadı, eski ilanlar korunuyor |
| 164650 | Tapu, taşınmaz, kimden, takas, bağlı adres, site | Resmî kilit ve mevcut yayın testleri geçti |
| 164710 | OSM harita/işaretçi, büyütme, koordinat alternatifi, gizlilik | Servis hata davranışı görüldü; gerçek harita/konum tutarlılığı açık kabul maddesi |
| 164732 | Cephe + yedi özellik grubu, bilinçli vazgeçme | Kayıt/vazgeçme testleri geçti; kapalı grup seçenekleri kopyalanmadı, gerçek özellikler etiket olarak giriliyor |
| 164829 | 35 fotoğraf, sıra/kapak ve klavye alternatifi | 35 kabul/36 ret, kayıt/düzenleme/temizlik testleri geçti; iyileştirme servisi yok |
| 164927 | Video/tur, özel not, hesap iletişimi | Özel not/tur/gizlilik testleri geçti; video araçları ve ek telefon alanları aşağıda |
| 164942 | Telefon gizliliği, açık onay ve tek gönderim | Başarı/hata/tekrar gönderim testleri geçti; mevcut olmayan mesaj sistemi taklit edilmedi |

## Son kontroller

Değişen **31 ilgili dosyanın tam listesi ve SHA-256 değerleri**: [changed-files.json](../output/ilan-form-20261028/changed-files.json). Yalnız bu kaynaklar/testler ve bu rapordan oluşan inceleme paketi: [ilan-tek-sayfa-kaynak.zip](../output/ilan-form-20261028/ilan-tek-sayfa-kaynak.zip). Ortam dosyaları, vendor/node_modules, mevcut veritabanları, test oturum açma yardımcıları ve başka bekleyen çalışmalar pakete dahil değildir. Bu paket otomatik yayın betiği değildir.

- **Tam paket: 237 test, 1651 doğrulama, 0 hata, 0 başarısızlık; 3 video testi atlandı.** `output/ilan-form-20261028/final-suite.txt` / `final-suite.xml`. Gerçek EİDS taşınmaz başarısı/başarısızlığı yerine açıkça servis dublörü kullanan iki yeni bağlantı testi var; bunlar dış servisin çalıştığını kanıtlamaz. Eski EİDS/OTP testleri kendi sahte taşıyıcılarıyla çalıştı.
- Son bölüm sırası düzenlemesinden sonra **4 test / 34 doğrulama** tekrar geçti (`final-smoke.txt`).
- Gerçek Chrome: **1440×1000, 768×1024, 390×844**. Hızlı kategori seçimi, taslak kaydı, URL ile yenileme sonrası aynı girdilere dönüş; yatay taşma yok, JavaScript hatası yok. `browser-results.json`; `form-*-top.png` ve `form-*.png`. Yalnız yerel sunucu istekleri açık; dış ağ tarayıcı testinde engelli.
- Pint ilgili PHP dosyalarında uygulandı; PHP sözdizimi kontrolü yapıldı. `public/js/listing-media.js` Node sözdizimi kontrolü geçti.
- Normal `npm run build` esbuild'in çalışma alanı üst dizinine erişememesi yüzünden başarısız oldu. Aynı projenin **`npm run build -- --configLoader runner`** komutu başarılı: 55 modül, Vite 7.3.6 (`build-runner.txt`). Bağımlılık/lock/config dosyaları bu hatayı aşmak için değiştirilmedi.

## Migration ve geri dönüş

Yeni migration: `database/migrations/2026_10_28_000001_add_listing_form_details_and_drafts.php`. Yalnız `listings.form_details`, `listings.private_note` ve yeni `listing_form_drafts` tablosu ekleniyor; EİDS/OTP tabloları veya eski alanların null/zorunluluk yapısı değişmiyor. Yalnız SQLite test/veri inceleme ortamında çalıştırıldı. Canlı veya mevcut kullanıcı geliştirme veritabanına uygulanmadı.

`down()` bilerek veri silmez; kod geri alınırken yeni not/taslaklar korunur. `up()` tekrar uygulamada mevcut alanları/tabloyu atlar. Geri alma/reapply veri koruma testi geçti. Tam veri temizliği bu işin parçası değildir. Kod geri dönüşünde yeni medya kapasitesiyle oluşmuş kayıtlar ve yeni notlar göz önünde bulundurulmalı; eski notsuz şemaya veri kaybettiren dönüş yapılmamalı.

## Açık kabul maddeleri ve bağımlılıklar

1. **Gerçek EİDS/OTP servisleri** ve zorunlu SSO dönüşünden sonra taslağın geri yüklenmesi uçtan uca sınanmadı. Servis/callback/OTP kodu değiştirilmedi; yerel dış çağrı yapılmadı.
2. **FFprobe/FFmpeg yok.** Üç gerçek video testi bu nedenle atlandı. MOV/3GP dönüşüm kodu var ancak gerçek çıktı/codec/süre sınırı bu ortamda kanıtlanmadı. Sunucu kurulumuna müdahale edilmedi.
3. **Harita ağ erişimi engelli.** OSM seçici ve hata/manuel koordinat alternatifi var. Uydu katmanı mevcut sağlayıcıda yok. Adres ile koordinatın gerçek coğrafi eşleşmesini denetleyen geocoding/poligon servisi bağlanmadı; arayüzde kullanıcı kontrolü isteniyor. Mevcut eski medya dosyalarının EXIF/GPS içeriği taranmadı; metadata temizliği yalnız yeni fotoğraflara uygulanıyor.
4. **Fotoğraf iyileştirme ve masaüstünden telefona aktarım servisi yok.** İşlevsiz düğme/bildirim eklenmedi. Telefonun kendi galeri/kamera dosya seçimi mevcut yükleme alanından kullanılabiliyor; fiziksel cihaz testi yapılmadı.
5. **Mesajlaşma ve ikinci iş telefonu kaynağı yok.** Doğrulanmış profil/ofis telefonları salt okunur kullanılıyor. Çalışmayan mesaj tercihi veya doğrulanmamış ilave telefonlar eklenmedi.
6. Görsellerde kapalı olan özellik listeleri doğrulanamadığı için seçenekler “referanstan alındı” diye sunulmuyor. Gruplar gerçek özellik etiketi girişini ve varsa `options_listing_*` önerilerini destekliyor. Kategorilere özgü tüm yeni alanların zorunluluk/tercih şeması ve serbest kategori cümlesi araması, kapsamın eksiksiz karşılandığı iddiasına dahil değil.
7. Otomatik kaydın 45 saniyelik gerçek zaman aralığı ve ağ kesintisi browser testinde beklenerek sınanmadı; aynı sunucu `saveDraft` yolu, sürüm çakışması ve yenileme test edildi. Çok süreçli gerçek MySQL/Redis eşzamanlılık testi yapılmadı; SQLite/Livewire tekrar gönderim testi geçti.

Bu nedenlerle “15 görseldeki bütün işlevler eksiksiz uygulandı” veya “Aşama 1 tamamen kabul edildi” sonucu çıkarılmamalıdır. İlgisiz aşamaya geçilmedi ve yayın yapılmadı.
