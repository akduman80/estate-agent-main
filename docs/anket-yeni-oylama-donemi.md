# Tek seferlik anket sıfırlama

Bu sürüm anketin sorusunu, seçeneklerini, yayın tarihlerini ve durumunu korur. Yalnızca açıkça seçilen anketin oyları ve seçenek sayaçları sıfırlanır. EİDS ve diğer entegrasyonlar bu işlemin kapsamı dışındadır.

## Uygulama

1. İlgili anket kodunu ve `2026_10_16_000001_add_voting_period_to_polls.php` migration dosyasını yayınlayın. Migration yalnızca iki nullable alan ekler; oy silmez ve kendiliğinden sıfırlama yapmaz.
2. `php artisan migrate --force` çalıştırın ve uygulama/route/view önbelleklerini standart yayın süreciyle yenileyin.
3. Yönetim panelinden hedef anketin ID'sini ve sorusunu doğrulayın. Yalnız bu ID ile `php artisan polls:reset-voting-once ANKET_ID` komutunu çalıştırın.
4. Komutun bildirdiği dönem UUID'sini kayıt altına alın. Hedef ankette sayaçların sıfırlandığını, soru ve seçeneklerin korunduğunu kontrol edin.

Canlı yayın öncesinde mevcut sunucu koduyla ayrıca hedef anketin oy yedeği alınmalıdır. Sıfırlama komutu da ek güvence olarak anket, seçenek ve oy kayıtlarını `local` özel diskte `poll-vote-backups/poll-ID-UUID.json` dosyasına yazar; dosyayı yeniden okuyup SHA-256 bütünlüğünü doğrulamadan oy silmez. Yedek dosyasının içeriği sohbet veya herkese açık depoyla paylaşılmamalıdır.

Komut oy silme, sayaç sıfırlama, yeni UUID ve kalıcı `voting_reset_at` işaretini aynı transaction içinde kaydeder. Aynı komutun yeniden çalışması yeni oyları silmez. Sayfa yüklemesi, uygulama başlangıcı veya yayın sürecinde bu komuta otomatik çağrı yoktur. Mevcut başlama/bitiş tarihleri korunur; anketin açılabilmesi için yayında ve geçerli tarih aralığında olması gerekir.

Yeni dönem oy istekleri o dönemin UUID'sini taşır. Eski açık pencereler 409 yanıtı alır. Üye oyu dönem + hesap kimliğiyle, misafir oyu dönem + kalıcı tarayıcı kimliğiyle HMAC olarak tanınır. Yeni dönemde IP adresi oy kimliği değildir. Diğer anketlerdeki eski kısıtlar korunur. Tarayıcıdaki oy ve kapatma kayıtları anket + dönem anahtarıyla tutulur; eski kayıtlar silinmeden yeni dönemde etkisiz kalır.

Üyeliksiz kullanımda farklı cihazlar, gizli sekme ve silinen tarayıcı kayıtları aynı kişiyi kesin olarak tanımaya elverişli değildir.

## Yerel doğrulama

- 10 Laravel testi, 76 doğrulama: özel disk oy yedeği; yedek başarısızsa sıfırlamanın engellenmesi; yalnız hedef ankette sıfırlama; soru/seçenek koruma; diğer anketin korunması; işlemi tekrar çalıştırınca yeni oyların korunması; hesap/tarayıcı tekrar oy engeli; eski dönem isteğinin reddi; yayın tarihleri; seçenek doğrulaması.
- `node scripts/check-visitor-poll.mjs`: 1280, 768, 390 piksel; eski yerel kayıtlarla yeni dönemin açılması, 60 saniye, sayfa geçişi, hata/yeniden deneme, teşekkür ve kapanma, kalıcı oy kaydı, kapatma/Daha Sonra/Escape, uzun içerik kaydırma ve aynı oturumda dönem değişimi.
- Tarayıcı testleri kontrollü HTTP yanıtları kullanır. Gerçek canlı veritabanı ve Android uygulaması bu testlere dahil değildir.

Bu çalışma sırasında canlı veritabanında sıfırlama veya canlıya yayın yapılmadı. Hedef anketin ID'si ve sunucuda komut çalıştırma erişimi olmadan gerçek sıfırlama tamamlanmış sayılmaz.
