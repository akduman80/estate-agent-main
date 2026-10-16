# Anket 1: yeni dönem ve canlı doğrulama — 16 Ekim 2026

Mevcut GitHub main → imzalı webhook → systemd dağıtımı kullanıldı. Dağıtım betiği değiştirilmedi. GitHub yazma yetkisi yayın için yeterliydi; SSH gerekmedi. Veritabanı işlemleri mevcut artisan migrate --force adımındaki bir defalık migration kayıtlarıyla yürütüldü. HTTP üzerinden oy sıfırlayan bağlantı eklenmedi.

## Hedef, yedek ve sıfırlama

Tekil hedef ID 1, başlık “WEB SİTEMİZ SİZCE OLMUŞMU ?”. Soru, Osmaniye halkına fayda sağlayıp sağlamayacağını soran devam cümlesini de içeriyor. 000002 soru alanında eşleşme bulamayınca veri değiştirmedi. 000004 başlık/soru eşleşmesinin tekil olduğunu doğrulayıp özel disk yedeğini aldı.

Oylar, seçenekler ve anket kaydı storage/app/private/poll-vote-backups/site-poll-before-reset-20261016-UUID.json dosyasına yazılıp yeniden okunarak doğrulandı. Dosya yolu, SHA-256 ve soru parmak izi özel site-poll-reset-20261016-status.json raporunda bulunur. Oy yedekleri yalnız dosya sahibine okunabilir; sohbete veya depoya gönderilmedi.

000005 yalnız ID 1, soru parmak izi ve yedeğin SHA-256 bütünlüğünü doğrulayarak polls:reset-voting-once 1 komutunu transaction içinde bir kez çalıştırdı. Komut ayrıca sıfırlama anının yedeğini aldı. Oy kayıtları ve seçenek sayaçlarının sıfır olduğu canlı raporla doğrulandı.

Yeni dönem: 32b5e655-e019-46e5-b9bb-6542f9f7a107.

Başlangıç 10:35 Türkiye saatiydi. Kullanıcının ayrıca verdiği “Başlangıcı şimdiye al ve doğrula” onayıyla 000006 yalnız başlangıcı 16 Ekim 2026 10:19:08 Türkiye saatine aldı. Bitiş 31 Aralık 2026 10:35 Türkiye saati olarak korundu. Soru, seçenekler ve diğer anketler korundu.

## Tekrar çalışmama ve kimlik

Migration kayıtları, kalıcı voting_reset_at ve dönem UUID'si tekrar yayında sıfırlamayı engeller. Geri alma eski oyları geri yüklemez veya yeni oyları silmez. Sayfa yüklemeleri ve uygulama başlangıcı sıfırlama çağırmaz. Sıfırlama komutu tekrar çalıştırıldığında yeni oyları korur; yedek yazılamazsa sıfırlama yapmaz.

Üye oyu dönem + hesap, misafir oyu dönem + kalıcı tarayıcı UUID'siyle HMAC olarak tanınır. Yeni dönemde IP kimlik olarak kullanılmaz. Tarayıcı oy/kapatma anahtarları anket + dönem içerir; eski kayıtlar yeni oyu engellemez. Eski açık pencere yeni döneme oy göndermeye çalışırsa 409 alır. Farklı cihaz, gizli sekme ve silinen tarayıcı kayıtları üyeliksiz kullanıcıyı kesin tanımaya elverişli değildir.

## Gerçek canlı tarayıcı doğrulaması

Gerçek HTTPS yanıtlarıyla Chrome masaüstü 1366×900, mobil 390×844 ve tablet 768×1024 test edildi. Zaman hızlandırılmadı; HTTP yanıtları taklit edilmedi.

- Ana sayfadan iletişim sayfasına geçildi; sayaç değişmedi. Pencere yaklaşık 60.45–60.46 saniyede açıldı.
- Küçük pencere genişliği, kenar boşlukları, ekran içinde kalma ve ilk odak kontrol edildi; ekran görüntüleri incelendi.
- Eski poll.voted.1, poll.shown ve poll.dismissed.1 kayıtları özellikle eklenmesine rağmen yeni dönem açıldı.
- Masaüstü ve mobilde seçeneksiz gönderim engellendi; gerçek oy kaydedildi, teşekkür gösterildi ve pencere kapandı. Aynı kimlikle ikinci HTTP oy isteği “daha önce oy verdiniz” döndü. Sonraki sayfada pencere açılmadı; sunucu o ziyaretçiye anket döndürmedi.
- Tablette Daha Sonra sonrası sonraki sayfada tekrar açılmama doğrulandı. Sayfa JavaScript hatası görülmedi.
- İki test tarayıcı UUID'si işlem öncesinde kaydedildi. 000007 yalnız bu kimliklerin ID 1 ve belirtilen dönemdeki iki oyunu sildi, ilgili sayaçları düşürdü. Canlı rapor removed_test_vote_count: 2, test_votes_removed: true, counters_consistent_after_cleanup: true döndü. Gerçek ziyaretçi oyları silinmedi.
- Temizlikten sonraki yayında aynı dönem UUID'si korundu. Geçici salt okunur işlem raporu ziyaretçi API'sinden kaldırıldı; rapor özel diskte tutulur.

15 Laravel testi / 115 doğrulama geçti. Bunlar yedek başarısızlığında veri korumasını, tekil hedefi, diğer anketleri, üyelerin cihazlar arası tek oyunu, misafirleri, yayın tarihlerini ve yalnız test oylarını temizlemeyi kapsar. Canlı üye hesabıyla giriş ve Android uygulaması ayrıca denenmedi; canlı mobil kontrol tarayıcı emülasyonuyla yapıldı.

EİDS, diğer entegrasyonlar, reklamlar ve mevcut yönetim paneli düzeni değiştirilmedi. Canlıdaki RichEditor soru düzenleyicisi korundu.