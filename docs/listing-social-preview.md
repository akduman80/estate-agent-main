# İlan paylaşım önizlemesi — yayın raporu

## Telefon, tablet ve masaüstü paylaşım kartı güncellemesi

8 Kasım 2026: Paylaşım kartı “EİDS doğrulamalı ilan” bilgi kutusunun hemen altına taşındı. Fotoğraflı önizleme, dış font/CDN gerektirmeyen SVG platform logoları, WhatsApp/Facebook/Telegram/X bağlantıları, BİP/Messenger için cihaz paylaşım menüsü ve bağlantı kopyalama düğmesi eklendi. Paylaş düğmesi artık tarayıcıda yerel paylaşım desteği bulunmadığında gizlenmiyor; bağlantı seçme/kopyalama penceresi açılıyor.

Kart telefon ve masaüstü yan sütununda iki, tablet genişliğinde üç sütun kullanır; düğmeler en az 48–52 piksel yüksekliktedir. CSS ve JavaScript dosyaları sürüm parametresiyle yüklenir. Kopyalama API'si engellenirse bağlantı elle kopyalanmak üzere seçilir. Logolu platform bağlantıları JavaScript olmadan da çalışır.

Güncelleme testleri: 42 PHP testi / 244 doğrulama geçti. JavaScript testinde yerel paylaşım, destek bulunmaması, iptal, engellenmiş paylaşım, kopyalama ve elle kopyalama akışları geçti. Blade derlemesi ve fark kontrolü geçti. Gerçek telefon/tablet görsel testi yapılamadı: tarayıcı erişim servisi kullanılamadı; yerel Edge kontrolü de araç mevcut URL'yi güvenilir biçimde belirleyemediği için durduruldu. Bu kontroller başarılı olarak işaretlenmedi. Sosyal medya hesaplarından gerçek mesaj gönderilmedi.

Arayüz dosyaları: `resources/views/listings/show.blade.php`, `resources/views/partials/listing-share.blade.php`, `public/css/listing-share.css`, `public/js/listing-share.js`; testler: `tests/Feature/ListingSocialResponseTest.php`, `tests/JavaScript/listing-share.mjs`. EİDS entegrasyonu veya doğrulama metni değiştirilmedi.

8 Kasım 2026. Canlıya yayımlanan commit: `bade540434187575b66dfd8317992a55bf4ab2a2`.
Önceki canlı commit: `762c5d8e`. Yayın SSH üzerinden, yalnız bu görevin dokuz dosyasıyla yapıldı. GitHub main canlıdan 48 commit geride olduğundan webhook körlemesine tetiklenmedi. Yerel main gerçek canlı kaynak ve yeni commit ile eşitlendi; GitHub'a push yapılmadı.

## Sonuç

- İlanın gerçek kapak fotoğrafı öncelikli; eksik/bozuk fotoğrafta sonraki geçerli fotoğraf, fotoğraf yoksa mevcut ZYDEMLAK logosu kullanılır.
- R2'nin mevcut `public` / `r2_media` akışı salt okunur kullanılır. 1200 × 630 JPEG ayrı cache kaydı olarak üretilir. Orijinaller, mevcut filigranlı galeri, EİDS, OTP, üyelik, admin ve diğer entegrasyonlar değiştirilmedi. Yeni paylaşım kopyası da soluk merkez logosuyla korunur.
- İlk HTML yanıtında istenen Open Graph ve Twitter Card etiketleri, canonical, normal title/description bulunur. Mevcut `APP_URL=https://zydemlak.com` korunur; www ve www olmayan alan adlarında sayfa ve görsel erişimi doğrulandı.
- Fotoğraf içeriği ve ilan kimliği görsel adresinin karmasına katılır. Aynı depolama anahtarına yeni fotoğraf gelmesi de adresi değiştirir. Başlık/açıklama her istekte güncel kayıttan üretilir. Eski görsel adresi güncel geçerli görsele yönlenir. HTML `private, no-store`; görsel yeniden doğrulama gerektirir.
- WhatsApp, Facebook, Telegram, X, bağlantı kopyalama ve cihazın yerel paylaşım menüsü eklendi. BİP/Messenger gibi uygulamalar cihazın desteklediği yerel menüden seçilir.

## Yapılan testler

| Kontrol | Sonuç |
| --- | --- |
| PHP sözdizimi, Pint, Blade derlemesi, üretim Vite derlemesi, git diff kontrolü | Geçti |
| Önizleme, galeri koruması, R2 ve seçili üyelik/doğrulama testleri | 42 test / 228 doğrulama geçti |
| Tam test paketi | 242 test / 3837 doğrulama; 1 hata, 1 başarısızlık, 3 atlama |
| İki EİDS testinin değişiklik öncesi `762c5d8e` sürümünde karşılaştırması | Aynı 1 hata + 1 başarısızlık tekrarlandı; EİDS koduna dokunulmadı |
| Yayındaki tüm ilanların gerçek kaynaklardan görsel üretimi | 8/8 geçerli HTTPS JPEG, 1200 × 630; 3 fotoğrafsız ilan logolu; hata ve ilanlar arası karma tekrarı yok |
| 4259 slug'ı | Gerçek kayıt ID 14; kapak fotoğrafı ID 34; 27 fotoğraf mevcut; gerçek kapak görsel olarak incelendi |
| WhatsApp, facebookexternalhit ve TelegramBot kimlikleriyle 4259 ilk HTML ve görseli | Üçünde de HTTP 200; 13 gerekli sosyal etiket dolu; tam HTTPS görsel, JPEG 1200 × 630 |
| İkinci ilan ve fotoğrafsız ilan için dış HTTP kontrolü | HTML ve JPEG HTTP 200; doğru ayrı adresler |
| Aynı görselin üç bot kimliğiyle yeniden indirilmesi | Üç SHA256 aynı; farklı/eski görsel gözlenmedi |
| Kapak değişimi/silinmesi, bozuk kapak, logo, yayından kalkma, metin değişimi | İzole otomatik testlerde geçti; üretim ilanları test için değiştirilmedi |
| Canlı robots.txt | `User-agent: *`, `Disallow:`; engelleme yok |

Tam paketteki sorunlu mevcut testler `ListingApprovalWorkflowTest::test_publish_attempt_checks_eids_and_keeps_rejected_listing_as_draft` ve `test_verified_listing_requires_admin_approval_and_locks_official_fields` testleridir. Beklentiler mevcut EİDS hesap doğrulamasıyla uyuşmuyor. Tam paketin tamamı geçmiş olarak işaretlenmedi.

## Doğrulanamayanlar ve platform sınırları

WhatsApp/Durum, Facebook, Messenger, Telegram, BİP ve X içinde gerçek mesaj gönderimi veya yayımlama yapılmadı. Tarayıcı erişim servisi kullanılamadı; WhatsApp penceresinin güvenilir erişilebilirlik durumu da alınamadı. Masaüstü, tablet ve telefonda görsel tarayıcı kontrolü tamamlanmış sayılmıyor. Bot kimliğiyle HTTP testi, gerçek platform içi önizleme testiyle aynı şey değildir.

Cloudflare hesap kuralları değiştirilmedi ve hesap içinden incelenmedi. Dışarıdan yapılan bot kimlikli sayfa/görsel istekleri engellenmedi. Platformların kendi önbellekleri uygulama tarafından silinemez; eski mesaj kartları hemen yenilenmeyebilir. WhatsApp Durum dahil her paylaşım yüzeyinin büyük fotoğraf göstermesi platformun davranışına bağlıdır.

Her HTML/görsel isteğinde kaynak dosya içerik değişimini kontrol etmek için okunur; JPEG dönüşümü 24 saat cache'lenir. Kontrol edilen örneklerde üretim yaklaşık 0,08–0,19 saniyeydi; yüksek trafik yük testi yapılmadı.

## Değişen dosyalar

1. `app/Services/ListingSocialPreview.php`
2. `app/Http/Controllers/ListingSocialImageController.php`
3. `app/Http/Controllers/ListingController.php`
4. `config/listings.php`
5. `resources/views/components/layouts/modern.blade.php`
6. `resources/views/listings/show.blade.php`
7. `routes/web.php`
8. `tests/Feature/ListingSocialPreviewTest.php`
9. `tests/Feature/ListingSocialResponseTest.php`

Rapor ayrıca `docs/listing-social-preview.md` dosyasında tutulur. Veritabanı migration'ı, ayar/kimlik bilgisi veya güvenlik kuralı değişikliği yapılmadı.

## Yayın ve geri alma

Yayın, mevcut deployment kilidi altında kaynak yedeği, canlı commit ve temiz tracked dosya kontrolüyle yapıldı. Yeni sürümde route/view cache ve Vite derlemesi alındı; bakım modu kapatıldı. Canlı HEAD ve temiz tracked kaynak son kontrolde doğrulandı.

Sunucudaki kaynak yedeği: `storage/app/private/social-preview-backups/20261108T111550Z/source-before.tar.gz`. Yedek web üzerinden yayımlanmaz. Gerektiğinde yalnız `bade540434187575b66dfd8317992a55bf4ab2a2` commit'i revert edilip route/view cache ve Vite derlemesi yenilenebilir. Kullanıcı ve ilan verileri geri alma işlemine dahil değildir.

Kanıtlar yerel `output/social-live-verification.json`, `output/social-focused-final.log`, `output/social-regression-verified.log`, `output/social-regression-verified.xml`, `output/social-eids-before-change.log` ve `output/social-live-*-after.*` dosyalarındadır.
