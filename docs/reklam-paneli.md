# Ana sayfa reklam yönetimi

Önceki uygulama: 1e29e91, tek yatay ana sayfa reklamı. Yerleşim, üç orijinal 1600 × 240 JPG, 960 × 144 px üst ölçü, 5 saniye gösterim ve 640 ms geçiş korunur. CSS, ana sayfa sütunları ve EİDS kodu değiştirilmez.

Reklamlar artık veritabanındaki `advertisements` kayıtlarından gösterilir. Admin Reklamlar sayfası üstündeki kutu ve oluşturma/düzenleme formları gerçek yayın kurallarını açıklar. `home01`–`home99` mevcut placement sütununda benzersiz 1–99 gösterim sırasını temsil eder; başka reklam alanı yoktur. Yeni reklamlar boş sıraya eklenir, kayıt düzenlenerek sırası değiştirilebilir. Soldan/sağdan gösterim ve ağırlıklı eski dönüş süresi kullanılmaz; bunların kolonları ve formları kaldırılmıştır. Kullanılmayan eski veritabanı sütunları şema değiştirilmemesi için korunur.

Zorunlu alanlar ad (255 karakter), sıra, 1600 × 240 px görsel, HTTP/HTTPS hedef bağlantıdır (2048 karakter). JPEG/PNG/WebP ve 8192 KB sınırı Filament yüklemesinde, piksel ölçüsü Laravel dimensions doğrulamasıyla denetlenir. Türkiye saatiyle girilen tarihler mevcut UTC veritabanı düzenine dönüştürülür. Yayında anahtarı açık ve tarih aralığında olan kayıtlar sırayla gösterilir. Kayıt yoksa yedek görsel geri gelmez; alan tamamen kaldırılır.

## Veri aktarımı ve eski kayıtların temizlenmesi

Yeni migration veya şema değişikliği yoktur. Mevcut dağıtım betiği `optimize:clear` sonrasında `php artisan advertisements:prepare-home` çalıştırır. İlk yayında çalışmakta olan eski deploy betiği henüz yeni satırı içermeyebileceğinden Composer post-install kancası da aynı idempotent komutu `--if-ready` ile çalıştırır. Bu seçenek .env veya reklam tablosu olmayan yeni kurulumlarda aktarımı atlar; mevcut üretim veritabanındaki hataları gizlemez.

Yalnız kaynak çeken yayın kurulumlarında, yetki kontrolünden sonra ilk Reklamlar liste sayfası açılışı da aynı güvenli aktarımı çalıştırır. Aktarım tamamlanmadan önce henüz hiç home kaydı yoksa son yayınlanan üç statik JPG gösterilmeye devam eder. Aktarım işareti oluştuktan sonra varsayılanlara dönüş yapılmaz. Kamuya açık ana sayfa veritabanına yazmaz ve kayıt silmez.

1. Dosya kilidiyle eşzamanlı aktarımı engeller.
2. Yalnız placement=`left`/`right` eski kayıtlarını yerel diskin private `advertisements/legacy-*.json` dosyasına yedekler. Yedekleme başarısız olursa hiçbir eski kayıt silinmez.
3. İlk çalışmada henüz kaydı olmayan ilk üç sırayı orijinal JPG'leri public diske birebir kopyalayarak içe aktarır. Var olan home kayıtlarının kimliği, görseli, bağlantısı, yayın tarihleri ve kapalı durumu korunur.
4. Yalnız yedeklenen eski left/right kayıtlarını transaction içinde siler. Yüklenmiş dosyaları silmez.
5. Private `advertisements/home-imported-v1.json` işaretiyle sonraki dağıtımlarda silinmiş veya kapatılmış reklamların yeniden eklenmesini önler. Bu işareti normal kullanımda silmeyin.

Varsayılan yerel disk kökü `storage/app/private` olduğundan yedekler `storage/app/private/advertisements/` altındadır. Geri yükleme gerekiyorsa JSON kayıtlarını inceleyip yalnız geri alınacak eski kayıtları aktarın; bu dosyalar kamuya açık değildir. Canlı yedekleri GitHub'a gönderilmez.

## Doğrulama

`tests/Feature/HomeAdvertisementManagementTest.php` gerçek Filament/Livewire oluşturma, görsel yükleme, düzenleme, yayın anahtarı, tarih, sıra, yanlış ölçü/tür/boyut, veri aktarımı ve yedekleme davranışını; `SiteAdvertisementsTest` yerleşimi ve ortak sayfalarda eski alan bulunmamasını sınar. `node tests/JavaScript/home-advertisements.mjs` dönüş süresi/sırası, hedef URL, duraklatma, azaltılmış hareket ve yükleme hatasını doğrular.
