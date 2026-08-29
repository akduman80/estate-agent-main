# ZYDEMLAK Haber Sistemi — Mevcut Durum ve Fark Analizi

**İnceleme tarihi:** 29 Ağustos 2026  
**Kapsam:** Kullanıcının ilettiği 34 maddelik “Emlak Haberleri, Editör ve Admin Onay Sistemi Geliştirme Talimatı” ile mevcut Laravel/Filament kodunun karşılaştırılması.  
**Not:** Bu belge bir analiz ve uygulama kapsamıdır; bu inceleme sırasında uygulama kodu değiştirilmemiştir.

## 1. Yönetici özeti

Projede haber sistemi sıfırdan başlamıyor. Halihazırda:

- `Post` ve `Category` veri modelleri,
- `/haberler` liste sayfası ve `/haberler/{slug}` detay sayfası,
- sayfalama,
- kapak görseli, kategori, özet, zengin metin ve yayın tarihi alanları,
- ayrı `/editor` paneli,
- rol bazlı panel erişimi,
- e-posta + şifre sonrasında 6 haneli e-posta OTP doğrulaması

bulunuyor.

Ancak sistem, dokümandaki en önemli yayın güvenliği kuralını şu anda sağlamıyor: **editör formdan `published` durumunu seçebiliyor ve haberi doğrudan yayına alabiliyor.** Ayrıca editör kayıtları kendi haberleriyle sınırlandırılmamış, kategori yönetimi editöre açık, yayınlanmış haber silinebiliyor ve haber içeriği detay sayfasında temizlenmeden ham HTML olarak basılıyor.

Sonuç olarak mevcut yapı, haber gösterimi için kullanılabilir bir başlangıç iskeleti olsa da profesyonel editör–admin onay sistemi değildir. En büyük çalışma alanı arayüzden çok; **durum makinesi, sunucu tarafı yetkilendirme, sürüm/onay yönetimi, denetim kaydı ve medya güvenliğidir.**

## 2. Mevcut sistemde hazır olanlar

| Alan | Mevcut durum | Karar |
|---|---|---|
| Haber veri tabanı | `posts` ve `categories` tabloları var. Başlık, slug, özet, içerik, kapak, kategori, yazar, durum ve yayın tarihi tutuluyor. | Korunup genişletilecek. |
| Temiz URL | Slug üretiliyor ve haber `/haberler/{slug}` üzerinden açılıyor. | Korunacak. İstenirse dokümandaki tekil `/haber/{slug}` biçimine yönlendirme ile geçilebilir; zorunlu değil. |
| Tüm haberler | `/haberler` yalnızca `published` kayıtları listeliyor. | Korunup arama ve gerçek kategori filtresi eklenecek. |
| Sayfalama | Liste 9 kayıtlık Laravel pagination kullanıyor. | Korunacak. |
| Haber detayı | Başlık, yazar, kategori, tarih, kapak ve zengin içerik gösteriliyor. Önceki/sonraki haber bağlantısı var. | Korunup medya, SEO ve güvenlik yönünden geliştirilecek. |
| Panel ayrımı | `/admin`, `/editor` ve `/portal` ayrı Filament panelleri. | Korunacak. |
| Sunucu tarafı panel yetkisi | Aktif ve e-postası doğrulanmış `EDITOR` yalnızca editör paneline; `SUPERVISOR` admin ve editör paneline erişebiliyor. | Temel yaklaşım doğru; kaynak/politika seviyesinde sıkılaştırılacak. |
| Güvenli giriş | Ortak giriş akışı e-posta + şifre sonrasında 6 haneli e-posta OTP üretiyor; deneme, süre ve yeniden gönderim sınırları var. | Korunacak ve haber rolleri için test edilecek. |
| Editör hesap durumu | Kullanıcı modelinde aktif, askıda, reddedildi vb. hesap durumları mevcut. Admin listeden askıya alabiliyor ve aktifleştirebiliyor. | Editöre özel oluşturma/düzenleme ekranıyla tamamlanacak. |
| Kapak yükleme | Editör formunda görsel tipinde dosya yükleme alanı var. | Doğrulama, limit ve optimizasyon eklenerek korunacak. |

## 3. Mevcut sistemde kısmen olanlar

| Talep | Bugünkü durum | Eksik olan |
|---|---|---|
| Haber durumları | Yalnızca `draft`, `in_review`, `published` var. | `revision_requested`, `rejected`, `unpublished` ve gerekiyorsa `scheduled` davranışı; geçiş kuralları ve notlar. |
| Editör paneli | Haber ve kategori kaynakları görünüyor. | “Taslaklarım”, “Onaya gönderdiklerim”, “Yayınlananlarım”, “Düzeltme istenenlerim” görünümü ve sadece kendi kayıtlarına erişim. |
| Admin haber yönetimi | Admin paneli editör kaynak klasörünü de keşfediyor; ayrıca boş bir admin `PostResource` mevcut. | Tek, açık ve tam yetkili admin haber kaynağı; durum filtreleri ve onay aksiyonları. |
| Kategori | Kategori CRUD var. | `is_active`, sıralama ve yalnızca adminin yönetmesi. Editör yalnızca aktif kategori seçebilmeli. |
| Haber listesi | Kapak, başlık, özet, yazar, kategori, tarih ve sayfalama var. | Arama kutusu ve çalışan kategori filtresi. `selectedCategory` kodda var fakat görünümde kategori kontrolleri kullanılmıyor. |
| Zengin içerik | Filament `RichEditor` var. | İzinli biçimlendirme listesi ve kaydetme/gösterme sırasında HTML temizleme. |
| Kullanıcı yönetimi | Admin kullanıcıları filtreleyebiliyor, askıya alıp aktifleştirebiliyor. | Adminin yeni editör oluşturması; ad, soyad, telefon, kullanıcı adı/tercihen e-posta, ilk şifre ve doğrulama akışı; editöre özel menü. |
| Yayın tarihi | `published_at` var. | Gelecek tarihli yayının ziyaretçiye zamanından önce açılmasını engelleyen sorgu kapsamı ve planlama davranışı. |
| SEO | Slug, başlık, kapak, tarih ve yazar verisi var. | SEO başlığı, meta açıklaması, canonical/Open Graph, NewsArticle yapılandırılmış verisi ve sayfa bazlı meta çıktısı. |

## 4. Tamamen eklenecekler

### 4.1 Ana sayfa haber alanı

Ana sayfada şu anda yalnızca slider, arama ve güncel ilanlar bulunuyor; haber bölümü yok. Sağ sütunda masaüstünde, mobilde ana içeriğin altında gösterilecek bir **Emlak Haberleri** bileşeni eklenecek.

Bu bileşen:

- yalnızca yayında ve yayın zamanı gelmiş haberleri almalı,
- önce adminin “Ana Sayfada Göster” seçtiği haberleri, kalan kontenjana en yeni haberleri koymalı,
- sonucu tekilleştirip kesin olarak en fazla 3 kayıtla sınırlandırmalı,
- kapak, kategori, kısaltılmış başlık ve yayın tarihi göstermeli,
- başlık ile “Tüm Haberleri Gör” bağlantısını `/haberler` sayfasına bağlamalıdır.

Veri modeline en az `is_home_featured` alanı eklenmelidir. Üçten fazla kayıt işaretlenirse gösterim sorgusu yine 3 ile sınırlandırılmalı; tercihen admin arayüzü de en fazla 3 seçim kuralını uygulamalıdır.

### 4.2 Editör–admin onay iş akışı

Durum değişiklikleri serbest bir açılır listeden değil, yetkiye bağlı aksiyonlardan yürütülmelidir:

```text
Editör: TASLAK -> ONAY BEKLİYOR
Admin:  ONAY BEKLİYOR -> YAYINDA
Admin:  ONAY BEKLİYOR -> DÜZELTME İSTENDİ
Admin:  ONAY BEKLİYOR -> REDDEDİLDİ
Editör: DÜZELTME İSTENDİ/REDDEDİLDİ -> TASLAK veya tekrar ONAY BEKLİYOR
Admin:  YAYINDA -> YAYINDAN KALDIRILDI
```

Önerilen yeni alanlar:

- `submitted_at`
- `approved_at`, `approved_by`
- `review_note` veya ayrı inceleme kayıtları
- `rejected_at`, `rejected_by`
- `unpublished_at`, `unpublished_by`
- `scheduled_for` veya yayın planı için tutarlı biçimde `published_at`
- `is_home_featured`

Durum geçişleri tek bir `PostWorkflowService` içinde yapılmalı; Filament butonları bu servisi çağırmalıdır. Böylece arayüz dışında yapılan çağrılar da aynı kurallara tabi olur.

### 4.3 Yayındaki haberin revizyonu

Dokümanın 17. maddesi mevcut şemayla sağlanamaz. Editör yayımdaki metni düzenlediğinde, onaysız metnin canlı kayda yazılmaması gerekir.

Önerilen çözüm:

- `post_revisions` tablosu oluşturmak,
- yayındaki onaylı içerik `posts` kaydında kalırken editör değişikliğini bekleyen revizyonda tutmak,
- admin onayladığında revizyonu canlı kayda atomik olarak aktarmak,
- reddetme/düzeltme notunu revizyona bağlamak.

Bu, “admin onaylayana kadar eski onaylı sürüm yayında kalır” koşulunu doğrudan karşılar.

### 4.4 İşlem geçmişi

Yeni `post_activity_logs` (veya genel audit log) tablosu ile en az şu olaylar tutulmalı:

- oluşturma ve düzenleme,
- taslak kaydetme,
- onaya gönderme,
- düzeltme isteme ve notu,
- reddetme ve nedeni,
- onaylama/yayınlama,
- planlama,
- yayından kaldırma,
- ana sayfa seçimini değiştirme,
- silme/geri alma.

Her kayıtta aktör, haber/revizyon, olay, önceki/yeni durum, not ve zaman bulunmalıdır.

### 4.5 Ek görseller ve video

Mevcut haber şemasında ek görsel, YouTube URL’si veya video dosyası alanı yoktur. Şunlar eklenecek:

- sıralanabilir çoklu ek görsel ilişkisi (`post_media` veya `post_images`),
- doğrulanmış YouTube bağlantısı/video kimliği,
- alternatif olarak yerel video dosya yolu,
- aynı anda hangi video yönteminin kullanılacağına ilişkin kural,
- detay sayfasında güvenli embed veya HTML5 video oynatıcı.

Görsel/video yüklemelerinde MIME içerik doğrulaması, izinli türler, dosya boyutu sınırı, rastgele sunucu dosya adı ve yetkisiz dosya çalıştırmayı önleyen depolama düzeni uygulanmalıdır. Görseller için kuyrukta yeniden boyutlandırma/WebP benzeri optimizasyon yapılabilir; projedeki ilan görsel işleme yaklaşımı haberler için uyarlanabilir.

### 4.6 Arama, filtre ve SEO

- Başlık, özet ve kontrollü biçimde içerik üzerinde arama eklenecek.
- Kategori filtresi query string/Livewire ile gerçekten çalışır hale getirilecek.
- Pasif kategoriler ziyaretçiye gösterilmeyecek.
- Haber detayına SEO başlığı, meta açıklaması, canonical URL, Open Graph alanları ve `NewsArticle` JSON-LD eklenecek.
- `published_at <= now()` koşulu bütün halka açık sorgularda ortak bir `published()` scope ile uygulanacak.

### 4.7 Test kapsamı

Projede haber modülüne özel otomatik test bulunmuyor. Dokümandaki manuel senaryolara ek olarak şu otomatik testler yazılmalıdır:

- editör admin paneline erişemez,
- pasif/askıdaki editör editör paneline erişemez,
- editör yalnızca kendi haberlerini görür ve değiştirir,
- editör `published` değerini istekle zorlayamaz,
- editör başka editörün kayıt kimliğini kullanamaz,
- editör yayımlanmış haberi silemez,
- bekleyen/reddedilen/gelecek tarihli/yayından kaldırılmış haber halka açık değildir,
- admin onay, düzeltme, ret ve yayından kaldırma yapabilir,
- yayımdaki haber revizyonunda eski sürüm onaya kadar görünür,
- ana sayfa en fazla 3 benzersiz haber gösterir,
- arama ve kategori filtresi çalışır,
- zararlı HTML temizlenir,
- geçersiz MIME veya büyük medya reddedilir,
- YouTube ve yerel video doğru/güvenli gösterilir.

## 5. Değiştirilecek mevcut davranışlar

1. **Editör formundaki serbest durum seçimi kaldırılacak.** Editör `published` seçemeyecek; yalnızca “Taslak Kaydet” ve “Admin Onayına Gönder” aksiyonları olacak.
2. **Editör sorguları kullanıcıya göre sınırlandırılacak.** `user_id = auth()->id()` sunucu tarafında uygulanacak; yalnızca formda yazar atamak yeterli değildir.
3. **Admin ve editör haber kaynakları ayrıştırılacak.** Admin panelindeki boş `App\Filament\Resources\PostResource` tamamlanacak; adminin editör kaynağını dolaylı keşfetmesine dayanan yapı kaldırılacak.
4. **Kategori CRUD editör panelinden çıkarılacak.** Kategoriyi admin yönetecek; editör yalnızca aktif kategoriler arasından seçim yapacak.
5. **Halka açık yayın kontrolü merkezileştirilecek.** Sadece `status = published` değil, yayın tarihi ve kaldırılma durumunu da kontrol eden model scope kullanılacak.
6. **Haber detayı ham HTML basmayacak.** İçerik izinli HTML ile temizlenerek kaydedilecek ve/veya güvenli render edilecek.
7. **Silme davranışı role ve duruma bağlanacak.** Editörün genel silme butonu kaldırılacak; soft delete/çöp kutusu ve admin yayından kaldırma akışı kullanılacak.
8. **Kategori filtresi görünür ve işlevsel yapılacak.** Mevcut kategori adı bağlantıları filtre uygulamıyor.
9. **Ana sayfa görünümü 12 ilanlık tam genişlik yapıdan haber yan sütununu barındıran responsive düzene uyarlanacak.** İlan arama ve listeleme davranışı korunacak.
10. **Yazar kaydının kullanıcı silinince haberle birlikte silinmesi yeniden değerlendirilecek.** Mevcut `cascadeOnDelete`, bir editör hesabı silinirse bütün haberlerini silebilir. Editör hesaplarında kalıcı silme yerine pasifleştirme; veri tabanında `restrictOnDelete` veya `nullOnDelete` + yazar adı snapshot’ı tercih edilmelidir.

## 6. Çıkarılacaklar

- Editör ekranından `published` seçeneği ve doğrudan yayınlama kodu.
- Editör ekranından kategori oluşturma, düzenleme ve silme yetkisi.
- Editörün yayınlanmış haberde `DeleteAction` kullanabilmesi.
- Editörün tüm yazarların haberlerini görüp düzenleyebilmesi.
- Admin panelindeki boş/işlevsiz haber kaynağı veya aynı modeli iki farklı keşfedilmiş kaynakla gösteren mükerrer yapı; yerine tek, tamamlanmış admin haber yönetimi.
- Haber detayındaki kontrolsüz `{!! $post->content !!}` kullanımına güvenen yaklaşım.
- İş akışını sadece formdaki `status` değerine güvenerek yönetme.

## 7. Veri modeli önerisi

### `posts` tablosunda genişletilecek alanlar

```text
status
submitted_at
published_at
approved_at / approved_by
rejected_at / rejected_by
unpublished_at / unpublished_by
review_note (veya inceleme tablosuna taşınır)
is_home_featured
seo_title
meta_description
youtube_video_id
video_path
source_name
source_url
deleted_at
```

Durum için veritabanı `enum`unu büyütmek yerine uygulama enumu + indeksli string tercih edilmesi, ileride yeni durum eklemeyi kolaylaştırır.

### Yeni tablolar

- `post_revisions`: Yayındaki habere ait onay bekleyen içerik sürümleri.
- `post_media`: Ek görseller/video metadatası, sıralama ve MIME/boyut bilgileri.
- `post_activity_logs`: İşlem ve durum geçmişi.

### `categories` tablosu

- `is_active`
- isteğe bağlı `sort_order`

eklenmelidir.

## 8. Yetki matrisi

| İşlem | Editör | Admin |
|---|---:|---:|
| Kendi taslağını oluşturma/düzenleme | Evet | Evet |
| Başka editörün taslağını görme/düzenleme | Hayır | Evet |
| Haberi onaya gönderme | Kendi haberi | Evet |
| Doğrudan yayınlama | Hayır | Evet |
| Düzeltme isteme/reddetme/onaylama | Hayır | Evet |
| Yayındaki haberi doğrudan değiştirme | Hayır; revizyon açar | Evet |
| Yayından kaldırma | Hayır | Evet |
| Kalıcı silme | Hayır | Çok sınırlı/tercihen çöp sonrası |
| Kategori yönetimi | Hayır | Evet |
| Ana sayfa seçimi | Hayır | Evet |
| Editör hesabı yönetimi | Hayır | Evet |
| İşlem geçmişi | Kendi kaydıyla sınırlı/opsiyonel | Evet |

## 9. Önerilen uygulama sırası

### Faz 1 — Güvenlik ve temel iş akışı (kritik)

- Haber durum enumu ve geçiş servisi.
- Policy/resource sorgusu ile sahiplik ve rol kontrolleri.
- Editörden doğrudan yayınlama, kategori yönetimi ve tehlikeli silmenin kaldırılması.
- Tam admin haber yönetimi; onayla, düzeltme iste, reddet, yayından kaldır aksiyonları.
- Yayın sorgusunun ortak scope’a alınması.
- Onay akışının feature testleri.

### Faz 2 — Sürüm, kayıt ve hesap yönetimi (kritik)

- Yayındaki haber için revizyon sistemi.
- İşlem geçmişi.
- Adminin editör oluşturma/düzenleme/pasifleştirme ekranı.
- Kategori aktif/pasif yönetimi.

### Faz 3 — Medya ve içerik güvenliği (yüksek)

- Ek görseller, YouTube ve yerel video.
- MIME/boyut kontrolleri, güvenli adlandırma ve görsel optimizasyonu.
- HTML temizleme ve güvenli embed.

### Faz 4 — Ziyaretçi deneyimi ve SEO (yüksek/orta)

- Ana sayfa sağ sütunda 3 haber.
- Arama ve kategori filtresi.
- Geliştirilmiş haber kartı/detay responsive tasarımı.
- SEO meta ve yapılandırılmış veri.

### Faz 5 — Regresyon ve canlıya hazırlık

- Dokümanın 32. maddesindeki masaüstü/mobil manuel testlerin tamamı.
- İlan, üyelik, OTP ve EİDS testlerinin yeniden çalıştırılması.
- Kuyruk, depolama bağlantısı, video limitleri ve zamanlanmış yayın için canlı ortam kontrolleri.

## 10. Korunacak sınırlar

Bu geliştirme ayrı bir haber modülü olarak yürütülmeli ve aşağıdaki alanların davranışı değiştirilmemelidir:

- ilan yayınlama/onay akışı,
- EİDS servisleri ve callback rotaları,
- üyelik kayıt ve hesap onay akışı,
- mevcut e-posta OTP altyapısı,
- portal paneli,
- mevcut site ayarları ve ilan verileri.

Ortak `users` ve panel giriş yapısı kullanılabilir; ancak haber iş akışı ilan iş akışıyla aynı tablo veya servise bağlanmamalıdır.

## 11. Kod kanıtları

- Haber şeması yalnızca üç durum içeriyor: `database/migrations/2026_07_26_005240_create_posts_tables.php:20-38`.
- Editör formu `published` seçeneğini sunuyor: `app/Filament/Editor/Resources/PostResource.php:54-62`.
- Editör yayını doğrudan oluşturabiliyor: `app/Filament/Editor/Resources/PostResource/Pages/CreatePost.php:12-18`.
- Editör sayfasında genel silme aksiyonu var: `app/Filament/Editor/Resources/PostResource/Pages/EditPost.php:13-17`.
- Editör kayıt sorgusunda yazar sahipliği sınırı yok: `app/Filament/Editor/Resources/PostResource.php:83-105`.
- Kategori CRUD editör paneline açık: `app/Filament/Editor/Resources/CategoryResource.php:27-80`.
- Admin haber kaynağı boş: `app/Filament/Resources/PostResource.php:22-46`.
- Admin paneli hem admin hem editör kaynak klasörlerini keşfediyor: `app/Providers/Filament/AdminPanelProvider.php:38-40`.
- Panel erişimi rol, aktiflik ve e-posta doğrulamasıyla sunucuda kontrol ediliyor: `app/Models/User.php:70-87`.
- E-posta OTP giriş akışı mevcut: `app/Filament/Pages/Auth/Login.php:24-62`, `app/Http/Controllers/Auth/TwoFactorController.php:21-32`.
- Liste yalnızca `published` kayıtları alıyor ve 9’lu sayfalıyor: `app/Livewire/BlogList.php:18-27`.
- Detay rotası yalnızca durum kontrolü yapıyor; gelecek yayın tarihini kontrol etmiyor: `routes/web.php:28-45`.
- İçerik ham HTML olarak basılıyor: `resources/views/blogs/show.blade.php:53-55`.
- Ana sayfada haber sorgusu/bölümü yok: `resources/views/welcome.blade.php:4-91`.
- Admin kullanıcı ekranı yeni kullanıcı/editör oluşturmaya kapalı: `app/Filament/Resources/UserResource.php:52-60`.
- Haber modülüne özel test bulunmuyor: `tests/Feature` altında yalnızca üyelik, OTP, ilan ve EİDS testleri mevcut.

## 12. Sonuç

Mevcut haber listeleme ve detay altyapısı korunabilir; ayrı panel ve OTP temeli de doğru yöndedir. Buna karşılık mevcut editör kaynağını küçük arayüz eklemeleriyle canlıya almak güvenli değildir. Önce yayın yetkisi sunucu tarafında kesin biçimde adminle sınırlandırılmalı, sahiplik/policy kontrolleri ve revizyon modeli kurulmalıdır. Ardından medya, ana sayfa, arama ve SEO katmanları eklenmelidir.

**En kritik kabul ölçütü:** Bir editör; form verisini değiştirerek, doğrudan URL yazarak veya başka bir haber kimliği göndererek hiçbir koşulda admin onayı olmadan canlı içeriği oluşturamamalı ya da değiştirememelidir.
