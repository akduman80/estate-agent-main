# Telefona uygulama olarak ekleme

Site `/pwa/site.webmanifest`, yönetim paneli `/pwa/admin.webmanifest` kullanır.
Ayrı kimlikler sayesinde ZYDEMLAK ve ZYDEMLAK Yönetim ayrı kurulabilir; açılış
adresleri sırasıyla `/` ve `/admin` olur. Yönetim oturumu ve yetki kontrolleri korunur.

## Yayınlama

Normal uygulama dağıtımına `public/pwa`, `public/sw.js`, Blade şablonları ve
AdminPanelProvider değişikliklerini dahil edin. HTTPS gereklidir (localhost hariç).
Web sunucusu `.webmanifest` dosyalarını tercihen `application/manifest+json`
olarak sunmalı; `/sw.js` için uzun süreli/immutable cache kullanılmamalıdır.
Bu özellik için yeni npm bağımlılığı veya migration yoktur.

Service worker yalnızca ağdan okur; uygulama sayfalarını, kişisel verileri veya
API yanıtlarını Cache Storage'a yazmaz. Ağ hatasında Türkçe yeniden deneme
sayfası gösterir. Çevrimdışı ilan gezme veya yönetim işlemleri desteklenmez.

## Telefonla kabul kontrolü

- Android Chrome'da HTTPS siteyi açın, “Uygulamayı yükle” düğmesini veya
  tarayıcıdaki yükleme menüsünü kullanın. Simgenin ana sayfayı açtığını doğrulayın.
- `/admin/login` üzerinden yönetim uygulamasını da ekleyin. İki simgenin ayrı
  olduğunu ve yönetim simgesinin oturum açılmamışsa giriş ekranını açtığını doğrulayın.
- iPhone Safari'de site ve admin adreslerini ayrı ayrı açıp Paylaş > Ana Ekrana
  Ekle'yi seçin. Varsa “Web Uygulaması Olarak Aç”ı etkinleştirin.
- Uygulama içinde kurulum bildiriminin görünmediğini, oturum açma/çıkış ve
  yönetim işlemlerinin çalıştığını kontrol edin.
- İnterneti kapatıp başka sayfaya gidin; bağlantı ekranını doğrulayın. İnterneti
  açtıktan sonra “Yeniden dene” ile geri dönün.

Tarayıcı yükleme düğmesinin görünmesini kendi kurulum uygunluğu kurallarına göre
belirler. Mağaza yayını bu kapsamda değildir. Fiziksel cihaz kabul kontrolleri
canlı HTTPS ortamında ayrıca yapılmalıdır.

Otomatik kontrol: `php artisan test --compact tests/Feature/PwaTest.php`.
