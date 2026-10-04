# İlan ve medya düzenlemesi — uygulama ve dağıtım notları

4 Ekim 2026. Önceki analiz: [ilan-medya-analiz-ve-yol-haritasi.md](ilan-medya-analiz-ve-yol-haritasi.md).

## Uygulanan değişiklikler

- Mevcut Ada/Parsel kolonları Satılık Daire, Müstakil Ev, Ev ve mevcut toplu Konut kategorisinde kullanılabilir. Kategori ayarları değiştirilmedi. Arsa/Tarla davranışı ve EİDS alan kilitleri korundu. Detayda iki değer ayrı gösterilir; `0` boş sayılmaz.
- Cephe multiple seçime geçirildi. Nullable `facades` JSON alanı kullanılır; null ise eski `facade` tekli değeri okunur. Boş dizi, bütün cephelerin kaldırıldığını ifade eder. Eski özel cephe değerleri seçeneklerden kaybolmaz. Eski kolon/veri silinmez.
- Fotoğraf önizleme URL’si yerel public disk için panelle aynı origin’i kullanır. Sabit APP_URL üzerinden farklı host/port/HTTPS’e giden önizleme isteği önlenir. Önizleme ağ hatası/timeout görünür hata ile sonlanır; upload hatasında state güncelleme kilidi açılır. Vendor kodu değiştirilmedi.
- Fotoğraf kayıtları farklarına göre senkronize edilir. Kalan satırların kimlikleri korunur; yalnızca çıkarılanlar silinir. Kapak/sıra güncellenir. Dosya temizliği başarılı transaction commit’inden sonra yapılır; paylaşılan dosyalar ve seed/default görseller korunur. Eksik fiziksel dosya, ilgisiz bir kayıtta DB satırının kendiliğinden silinmesine yol açmaz.
- Ayrı, tek dosyalı `video_path` alanı eklendi. MP4/H.264 ve WebM/VP8/VP9 desteklenir. Süre sunucuda ffprobe ile doğrulanır. 60 saniye kabul edilir, 60,04 saniye reddedilir. Süre okunamıyorsa dosya kabul edilmez. Tarayıcıda erken süre/boyut uyarısı vardır. Silme/değiştirme, kayıt başarıyla tamamlanana kadar eski videoyu diskten kaldırmaz.
- YouTube `video_url` alanı ve mevcut gösterimi korunur. Dosya videosuyla birlikte kaydedilebilir; video eklemek linki silmez.
- Mevcut fotoğraf limitleri: 30 adet, dosya başına 15360 KB. Video boyutu mevcut proje yükleme tavanını varsayılan olarak kullanır: 204800 KB. İlan tavanı `LISTING_VIDEO_MAX_SIZE_KB` ile değiştirilebilir. Frontend kontrollerine ek backend kontrolleri vardır.
- Yeni kayıt ve düzenleme aynı aktif şemayı kullanır; admin ve portal kapsamındadır. Medya path’lerinin başka ilana ait kayıtlar üzerinden enjekte edilmesi reddedilir.

## Fotoğraf hatasının doğrulanan nedeni

İzole testte panel `127.0.0.1:8097` üzerinden açılırken public disk URL’si sabit `estate-agent.test` adresini kullanıyordu. Yüklü Filament önizleme loader’ı bu URL’ye yaptığı fetch başarısız olunca `TypeError: Failed to fetch` üretiyor; hata callback’i çağrılmadığı için “Loading” ve Abort durumu kalıyordu. Önizleme/silme yönetimi bu nedenle kullanılamıyordu.

Aynı testte düzenlemeden sonra önizleme ve Remove düğmesi açıldı. Hem oluşturma hem düzenlemede çoklu fotoğraf eklendi, bir fotoğraf kaldırıldı, kalan medya kaydedildi ve yeniden açıldı. Production’da aynı nedenin geçerli olduğu ayrıca canlı ağ kayıtlarıyla doğrulanmalıdır.

## Veritabanı ve geri dönüş

Migration: `2026_10_04_000002_add_listing_facades_and_video_path.php`.

Yalnızca nullable `facades` ve `video_path` eklenir. Eski kolonlar veya tablolar düşürülmez; backfill zorunlu değildir. `down()` yeni veriyi korumak için kolonları bırakır; tekrar `up()` çalışması idempotenttir. Uygulama rollback’i sonrası yeniden dağıtımda kolon çakışması olmaz.

Yerel MySQL’de ilgili tablolar JSON olarak özel storage altında yedeklendi; yeni migration tek dosya yolu verilerek uygulandı. Önceki ve sonraki bütün eski alanlar karşılaştırıldı: **13 ilan ve 10 görsel satırı birebir korundu.** Yedek dizini: `storage/app/private/backups/`; yedek dosyası izinleri 0600. Production DB’ye işlem yapılmadı.

## Dağıtımdan önce sunucu kontrolü

1. Production DB ve medya yedeği alınmalı; diğer pending migration’lar da varsa kapsamı ayrı kontrol edilmeli.
2. `ffprobe` PHP servis kullanıcısı tarafından çalıştırılabilmeli. PATH sorunlarını önlemek için ortamda `LISTING_FFPROBE_BINARY=/usr/bin/ffprobe` gibi gerçek mutlak yol tanımlanması önerilir. Araç yoksa sistem süre kontrolünü atlamaz; video kaydını anlaşılır hata ile reddeder. Kod sunucuda yazılım kurmaz.
3. MP4/WebM örnekleri staging’de PHP üzerinden doğrulanmalı. MOV/HEVC dosyaları desteklenen formata dönüştürülmeden kabul edilmez; yeni bir transcoding altyapısı eklenmedi.
4. Video boyut tavanı, PHP-FPM `upload_max_filesize`/`post_max_size`, web sunucusu/proxy body limiti, geçici Livewire tavanı ve timeout’larla uyumlu olmalı. İlan limiti global proje limitinin üstüne çıkarılırsa geçici upload ayrıca sınırlar; global ayarlar bu değişiklikte değiştirilmedi. 200 MB mevcut uygulama tavanıdır, production kapasitesinin doğrulandığı anlamına gelmez.
5. Yeni migration, kodla birlikte uygulanmalı. Standart deploy akışında migration ve cache yenileme tamamlandıktan sonra panel/detail kontrol edilmeli. JS bağımsız public asset’tir, Vite build gerektirmez; Blade dosya mtime’ı ile cache sürümü verir.
6. Yetkili staging’de EİDS gerçek bağlantı ve yayın akışı ayrıca kontrol edilmeli. Controller/service/route/middleware/token/verification koduna müdahale edilmedi; bu dosyaların HEAD’e göre farkı boş doğrulandı.

## Doğrulama kapsamı

- Son tek süreçli tam test çalışması: **132 test, 958 assertion başarılı** (34,34 saniye). PHP biçim kontrolü, JavaScript sözdizimi kontrolü ve `git diff --check` başarılı.
- `ListingMediaWorkflowTest`: iki panelde Satılık Daire/Müstakil Ev oluşturma ve aynı ID ile düzenleme; toplu Konut ayarı; eski/özel/null/boş cephe; mevcut görsel kimlikleri; ekleme/silme; son görseli kaldırma; 30/31 adet ve 15 MB sınırı; başka ilana ait dosya reddi; resmî alan kilitleri; gerçek 60 ve 60,04 saniyelik fixture; desteklenmeyen codec; eksik ffprobe; video değiştirme/silme; hatalı yüklemede eski video; rollback/shared file temizliği; detayda video ve YouTube; migration geri dönüşü ve yeniden uygulanması.
- Mevcut tüm test paketi, EİDS regresyonları ve proje videosu davranışı da kontrol edilir. HTTP mock kullanan EİDS testleri gerçek servis bağlantısı garantisi değildir.
- Tarayıcı: izole SQLite veri tabanıyla yeni Satılık Daire oluşturma, iki cephe seçimi, Ada/Parsel, çoklu fotoğraf/tek fotoğraf kaldırma, 60 saniyelik video kaydı, daha uzun videoya uyarı, YouTube’un birlikte saklanması ve aynı ilanı düzenleme doğrulandı.
- 390×844 mobil görünümde document genişliği ve scroll genişliği ikisi de 390: yatay taşma yok. Bu kontrol gerçek Android/iOS cihaz testi değildir; gerçek telefon dosya seçicisi ve codec davranışı dağıtım kabulünde ayrıca kontrol edilmeli.

Canlı sunucuya dağıtım, gerçek EİDS çağrısı ve gerçek telefon testi bu çalışma sırasında yapılmadı.

## 4 Ekim 2026 — canlı sunucu doğrulaması

Sonraki kontrolde `/var/www/estate-agent` üzerinde `5fd23d2` sürümünün otomatik dağıtıldığı ve yeni migration'ın 17. batch'te uygulandığı doğrulandı. `facades` ve `video_path` kolonları mevcut. Yeni JavaScript dosyasının canlı ve yerel SHA-256 değerleri aynı.

Sunucuda eksik olan ffprobe, Ubuntu'nun resmi deposundan ffmpeg paketiyle kuruldu. `www-data` kullanıcısıyla gerçek uygulama kuralı çalıştırıldı: 60 saniyelik fixture kabul edildi, 60,04 saniyelik fixture reddedildi. Bu kontrol CLI üzerinden servis kullanıcısıyla yapıldı; oturumlu HTTP yükleme testi yerine geçmez.

PHP 8.3 FPM'de önceki 2M dosya/8M POST tavanları `/etc/php/8.3/fpm/conf.d/99-estate-media.ini` ile 200M dosya/210M POST ve 300 saniye input süresi olarak düzenlendi. Nginx'in ilgili site bloğuna 210M body tavanı ve 300 saniye body timeout eklendi. Yapılandırma testleri başarılı; iki servis reload sonrası aktif. Ana sayfa ve portal giriş sayfası HTTP 200 döndü. 200 MB sınırında gerçek HTTP upload henüz denenmedi.

Sunucuda özel yedek dizini: `/var/backups/estate-listing-media-20261004-105810` (0700). Eski Nginx/PHP ayarları, başarılı MySQL dump'ı (3,5 MB, 0600) ve public medya arşivi (14 MB, 0600) tutuluyor. Medya arşivi listelenerek okunabilirliği doğrulandı; veritabanı geri yükleme provası yapılmadı. Bu veri yedeği otomatik migration sonrası alındı; migration öncesi yedek olduğu iddia edilmez.

Kalan kabul kontrolleri: yetkili panel oturumuyla gerçek yükleme/kaydetme/silme, gerçek Android/iOS dosya seçimi ve yetkili EİDS akışı. Canlı ilan kayıtlarına bu sunucu kontrolünde yazma/silme yapılmadı.

## 4 Ekim 2026 — canlı panel medya kabul testi

Kullanıcının açtığı admin oturumunda ayrı `MEDYA KABUL TESTİ 04.10.2026 — YAYINLAMAYIN` taslağı oluşturuldu (ilan ID 26). Taşınmaz ID `0` yalnızca test değeridir; onaya/yayına gönderilmedi ve EİDS çağrısı yapılmadı. Mevcut ilanlar değiştirilmedi.

- Satılık Daire, Ada 12/Parsel 34 ve Kuzey/Güney cepheleri kaydedilip yeniden açılarak doğrulandı. Cepheler Kuzey/Batı olarak güncellendi ve kalıcı olduğu doğrulandı.
- İki fotoğraf gerçek HTTP üzerinden yüklendi: 13 KB ve 8.051.497 bayt. Önizleme ve kaldırma düğmeleri yeniden açılan formda çalıştı. Bu test önceki 2 MB PHP sınırının üstündeki yüklemeyi doğrular; 15 MB/200 MB tavanlarının tamamının gerçek HTTP testi değildir.
- 60,04 saniyelik video frontend'de anlaşılır uyarıyla reddedildi. Gerçek 60 saniyelik video kaydedildi; YouTube bağlantısı birlikte korundu.
- Kullanıcının test medyasını kalıcı silme onayı sonrası bir fotoğraf kaldırıldı ve video değiştirildi. Yeniden açıldığında aynı ilan ID 26, kalan fotoğrafın aynı dosya yolu, yeni video ve YouTube bağlantısı doğrulandı. Eski fotoğraf/video URL'leri HTTP 404, kalan fotoğraf/yeni video HTTP 200 döndü.
- Son fotoğraf ve yeni video da kaldırılıp kaydedildi; dosya URL'leri HTTP 404 döndü. Taslak ve test YouTube bağlantısı inceleme için bırakıldı. Mevcut ilan verileri/medyası silinmedi. Tarayıcı hata kaydında test sırasında hata görülmedi.

Uygulama yükleme önerisi testte İleri düğmesini örttü; öneri kapatıldıktan sonra wizard ilerledi. Bu gözlem genel bir wizard backend hatası olarak değerlendirilmez. Chrome dosya erişim izni kullanıcı tarafından açıldıktan sonra test yüklemeleri yapılabildi.

Kalan kontroller: gerçek Android/iOS cihaz testi ve yetkili EİDS doğrulama/yayın akışı. Test taslağı, sahte taşınmaz değeriyle EİDS'e gönderilmemelidir.

## 4 Ekim 2026 — Android ve gerçek EİDS kontrolü

Kullanıcı, Android cihazındaki medya kabul testinin geçtiğini bildirdi. Bu sonuç kullanıcı tarafından gerçekleştirilen cihaz testidir; ajan tarafından bağımsız cihaz kontrolü yapılmadı. iOS cihaz testi yapılmadı.

Kullanıcının sağladığı gerçek taşınmaz numarası, mevcut ve yayındaki 11 numaralı ilanla eşleşti. Canlı sunucuda uygulamanın mevcut `EidsService::verifyListing()` metodu yeni bir servis çağrısıyla çalıştırıldı; sonuç `VERIFIED` oldu. EİDS kodu değiştirilmedi.

Kontrol DB transaction içinde yapılıp rollback edildi. İlanın bütün ham kolonları çağrı öncesi ve rollback sonrası karşılaştırıldı: `Original listing preserved: YES`. Yayın durumu `approved`, aktiflik `1` olarak korundu. Sorgunun DB güncellemeleri ve sorgu log satırı rollback edildi; yeni doğrulama tarihi ilanda kalıcı olarak değiştirilmedi. Harici servis çağrısı gerçekten yapıldı; rollback harici servis kaydını geri almaz.

Medya kabulü, kullanıcının Android testi ve gerçek EİDS servis sorgusu tamamlandı. Canlıda yeni bir ilanın taslaktan onaya ve yayına geçişi ayrıca denenmedi; mevcut yayındaki ilan bu test için yayından kaldırılmadı. Bu son uçtan uca yayın kontrolü, uygun gerçek bir yeni ilanla normal iş akışında yapılmalıdır. iOS, 200 MB sınırında gerçek HTTP yükleme ve yedekten geri yükleme provası da doğrulanmış kapsamın dışındadır.
