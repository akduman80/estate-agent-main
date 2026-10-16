# Kullanıcının kalıcı proje kuralı

Kullanıcı talimatı (12 Ekim 2026): EİDS entegrasyonuna kullanıcıdan önceden açık onay alınmadan dokunulmayacak ve entegrasyonda hiçbir değişiklik yapılmayacak.

Bu koruma EİDS servisleri, callback/SSO akışı, API bağlantıları, doğrulama ve ilan yayınlama bağlantıları, ilgili ayarlar, ortam değişkenleri, rotalar ve veritabanı yapısını kapsar. Başka bir görev için verilen genel düzenleme veya yayınlama izni EİDS değişikliği izni sayılmaz. Bir görev EİDS değişikliği gerektiriyorsa önce somut değişikliği açıklayıp kullanıcıdan açık onay al.

Bu kural bu çalışma alanındaki alt projeler ve canlı yayın için hazırlanan değişiklikler için de geçerlidir.


## Kalıcı değişiklik ve yayın kuralları

- Her değişiklikte EİDS ve diğer entegrasyonların mevcut çalışma biçimini, bağlantılarını, yetkilendirmelerini ve ayarlarını koru.
- İstenen değişiklik dışındaki sayfa düzenini, özellikleri, reklam yerleşimlerini ve kullanıcı akışlarını koru.
- Görsel düzenleme görevlerinde gereksiz veritabanı, migration, API, kimlik doğrulama veya sunucu ayarı değişikliği yapma.
- İstenen işin bu alanlara müdahale etmesi zorunluysa nedenini ve etkisini açıkla; kapsam dışı veya veri kaybına yol açabilecek işlemden önce onay al.
- Arayüz değişikliklerini masaüstü, tablet ve telefonda doğrula.
- Yalnızca ilgili dosyaları gönder; diğer bekleyen çalışmaları dahil etme. Doğrulanamayan noktaları açıkça belirt.
