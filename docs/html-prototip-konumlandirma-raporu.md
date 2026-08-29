# ZYDEMLAK HTML Prototipleri Konumlandırma Raporu

**İnceleme tarihi:** 29 Ağustos 2026  
**Kapsam:** `admin1.html`, `ilanver.html`, `iletişim.html`, `üyeol.html`, `üyepaneli.html`, `ilan-detayı.html` ve `anket-yonetim.html` tasarımlarının mevcut Laravel/Filament projesindeki yerlerinin belirlenmesi.  
**Temel karar:** HTML dosyaları bağımsız statik sayfalar olarak kopyalanmamalı; görsel prototip olarak kullanılmalı ve mevcut dinamik rota, model, yetki ve EİDS akışlarına uygulanmalıdır.

## 1. Önerilen bilgi mimarisi

```text
Halka açık site
├── /                         Ana sayfa
├── /ilanlar                 Tüm ilanlar
├── /ilan/{slug}             İlan detayı         ← ilan-detayi.html
├── /haberler                Tüm haberler
├── /haberler/{slug}         Haber detayı
├── /iletisim                İletişim             ← iletişim.html
├── /kayit                   Üyelik               ← üyeol.html
└── /login                   Güvenli giriş

Üye alanı
└── /portal                  Üye paneli           ← üyepaneli.html
    ├── /portal/listings     Üyenin ilanları
    ├── /portal/listings/create                   ← ilanver.html
    ├── /portal/listings/{id}/edit
    └── hesap güvenliği/profil

Yönetim alanı
└── /admin                   Admin paneli         ← admin1.html
    ├── ilan ve EİDS kayıtları
    ├── üyeler/işletmeler
    ├── haber yönetimi
    ├── ayarlar/sözleşmeler
    └── anketler                                   ← anket-yonetim.html
```

## 2. Dosya bazında kararlar

### 2.1 `ilan-detayi.html`

**Konumu:** Halka açık `/ilan/{listing:slug}` rotası.  
**Mevcut karşılığı:** `resources/views/listings/show.blade.php`.

Bu prototip, mevcut sayfaya göre belirgin biçimde daha zengin ve doğrudan uygulanması gereken en hazır tasarımdır. Aşağıdaki parçalar dinamikleştirilmelidir:

- ana görsel ve küçük görsel galerisi → `listing.images`,
- başlık, fiyat, ilan tipi ve konum → `listings`,
- ilan numarası → model `id` veya ayrı ilan numarası,
- EİDS rozeti ve doğrulanmış alanlar → `eidsVerification`,
- oda, brüt/net m², bina yaşı, kat, ısıtma, tapu vb. → mevcut `Listing` alanları,
- açıklama → `description`,
- video → `video_url`,
- iletişim → site ayarı veya ilanın işletme profili,
- WhatsApp metni → ilan numarası ve başlıkla güvenli biçimde üretilmiş URL,
- benzer ilanlar → kategori/konum bazlı sorgu.

**Alınmaması gerekenler:** Sabit telefon, sabit Osmaniye konumu, Unsplash görselleri ve örnek ilan bilgileri.

**Öncelik:** Yüksek. Haber sayfalarıyla başlatılan modern ön yüzün devamıdır.

### 2.2 `iletişim.html`

**Konumu:** Halka açık `/iletisim` rotası.  
**Mevcut karşılığı:** `resources/views/contact.blade.php`.

Prototipin kart düzeni, modern ortak header/footer ve harita/telefon çağrı butonları alınmalıdır. Adres, telefon ve e-posta kesinlikle HTML içine sabit yazılmamalı; `Setting` modelinden gelmelidir.

Mevcut sayfadaki `mailto:` formu gerçek bir iletişim formu değildir ve tarayıcıda kullanıcıya bağlı çalışır. İletişim formu korunacaksa ileride sunucu taraflı `POST /iletisim`, validasyon, spam/rate limit ve e-posta bildirimi eklenmelidir.

**Öncelik:** Yüksek; düşük riskli bir görsel uyarlamadır.

### 2.3 `üyeol.html`

**Konumu:** Halka açık `/kayit` rotası.  
**Mevcut karşılığı:** `resources/views/auth/register.blade.php` ve `RegistrationController`.

Prototipteki üyelik türü, ad/soyad, e-posta, telefon, şifre ve sözleşme alanları mevcut kayıt akışıyla eşleştirilebilir. Ancak:

- kayıt ekranı modal yerine ayrı sayfa olarak kalmalıdır,
- prototipteki **SMS doğrulama kaldırılmalıdır**,
- mevcut 6 haneli **e-posta OTP** kullanılmalıdır,
- bireysel ve emlak işletmesi alanları mevcut controller kurallarıyla korunmalıdır,
- KVKK/kullanım koşulları mevcut `LegalDocument` ve `UserConsent` yapısına bağlanmalıdır,
- emlak işletmesi için yetki belgesi/vergi/adres alanları mevcut gerçek kayıt formundan alınmalıdır.

**Öncelik:** Orta-yüksek. Görsel olarak modernleştirilebilir, güvenlik akışı değiştirilmemelidir.

### 2.4 `ilanver.html`

**Konumu:** Giriş yapılmış üye alanında `/portal/listings/create`.  
**Mevcut karşılığı:** Filament `Portal/Resources/ListingResource` ve dört adımlı wizard şeması.

Bu dosya yeni bir halka açık form olmamalıdır. Mevcut Filament formunda zaten:

- temel bilgi/kategori/fiyat,
- EİDS ve konum,
- tür bazlı gayrimenkul detayları,
- çoklu görsel ve YouTube videosu,
- taslak kaydetme ve admin onayına gönderme

bulunmaktadır.

Prototipten alınacaklar:

- lacivert başlıklı EİDS bilgilendirme kutusu,
- bölümlerin daha anlaşılır kart görünümü,
- alan gruplarının kullanıcı dili,
- mobilde rahat form aralıkları,
- yayın öncesi açıklayıcı durum özeti.

**Kritik kural:** EİDS kullanıcı adı/şifresi kullanıcı formuna konmamalı. Kullanıcı yalnızca Tapu Taşınmaz ID girer; sorguyu sunucudaki `EidsService` yapar. İlan önce kaydedilir, EİDS doğrulanır ve ardından admin yayın onayına gönderilir.

**Öncelik:** Yüksek fakat riskli; tasarım Filament formuna uygulanmalı, form baştan ayrı Blade/JavaScript olarak yazılmamalıdır.

### 2.5 `üyepaneli.html`

**Konumu:** Giriş yapılmış kullanıcı için `/portal`.  
**Mevcut karşılığı:** `PortalPanelProvider` ve Filament dashboard.

Prototipteki ikinci giriş ekranı kullanılmamalıdır; kullanıcı zaten e-posta + şifre + e-posta OTP ile giriş yaparak portala gelir. Prototip şu amaçlarla kullanılmalıdır:

- özel Filament portal teması,
- “toplam/yayında/onay bekleyen/taslak” ilan sayaç widget’ları,
- son ilanlar tablosu,
- hızlı “Yeni İlan” butonu,
- hesap/işletme durum kartı,
- EİDS ve yayın durumu açıklamaları.

Bireysel kullanıcı ve emlak işletmesi aynı dashboard'u görebilir; işletmeye özel firma/onay bilgileri role göre koşullu gösterilmelidir. Mevcut `ListingResource::getEloquentQuery()` sahiplik sınırı korunmalıdır.

**Öncelik:** Orta-yüksek.

### 2.6 `admin1.html`

**Konumu:** `/admin` Filament yönetim paneli.  
**Mevcut karşılığı:** `AdminPanelProvider` ve admin kaynakları.

Dosya tamamen yeni bir admin uygulaması olarak eklenmemelidir. Mevcut Filament panelinin giriş, OTP, rol kontrolü, CSRF, kaynak yetkileri ve veri tabloları korunmalı; prototip yalnızca şu görsel/özet bileşenlere kaynak olmalıdır:

- lacivert sidebar ve turuncu vurgu,
- genel bakış sayaçları,
- bekleyen ilan/haber göstergeleri,
- son işlemler ve hızlı bağlantılar,
- “Siteyi Görüntüle” aksiyonu.

**Prototipte düzeltilmesi gereken davranışlar:**

- “EİDS Onayı Ver” kaldırılmalı. EİDS sonucunu dış servis üretir; admin yalnızca yayın onayı verir.
- Haber modalındaki doğrudan “Yayınla” davranışı, editör–admin onay akışına bağlanmalı.
- Doğrudan silme butonları yerine kapatma, yayından kaldırma veya soft delete kullanılmalı.
- Sahte JavaScript login ekranı kullanılmamalı; mevcut güvenli Filament login + e-posta OTP kalmalı.
- Ziyaretçi IP/konum analitiği mevcut değildir. Bu alanlar gerçek analitik altyapı ve KVKK kararı olmadan gösterilmemelidir.
- Prototipteki sabit istatistikler gerçek sorgu/widget olmadan panele taşınmamalıdır.

Admin menüsünde önerilen gruplama:

```text
Genel Bakış
İlan Yönetimi
  ├── Tüm İlanlar
  ├── Onay Bekleyenler
  ├── EİDS Sorgu Kayıtları
  └── Değişiklik Geçmişi
Üye Yönetimi
  ├── Üyeler
  └── Emlak İşletmeleri
İçerik Yönetimi
  ├── Haberler
  ├── Haber Kategorileri
  └── Anketler
Site Yönetimi
  ├── Site Ayarları
  └── Sözleşme ve Politikalar
```

**Öncelik:** Orta. Önce işlevsel kaynaklar tamamlanmalı, sonra dashboard görselleştirilmelidir.

### 2.7 `anket-yonetim.html`

**Konumu:** Admin panelinde **İçerik Yönetimi → Anketler**.  
**Mevcut karşılığı:** Yok; yeni modüldür.

Prototip şu anda `localStorage` kullanan tek cihazlık demodur. Canlı sistemde bu yöntem kullanılmamalıdır. Önerilen gerçek yapı:

- `polls`: başlık, soru, durum, başlangıç/bitiş, sonuç görünürlüğü,
- `poll_options`: seçenek metni ve sırası,
- `poll_votes`: anket, seçenek, zaman ve tekrar oy kontrolü için mahremiyeti koruyan tanımlayıcı,
- Filament `PollResource`: taslak/yayında/kapalı, seçenek yönetimi, önizleme ve sonuçlar,
- halka açık oy endpoint'i: CSRF, rate limit ve sunucu validasyonu,
- aynı anda en fazla bir aktif anket kuralı,
- sonuçları oy öncesi/sonrası/anket kapanınca gösterme seçeneği.

Anketin halka açık konumu için öneri: **ana sayfada ilanların altında tam genişlikte kompakt bir bölüm**. Sağ haber sütununa eklemek sayfayı fazla sıkıştırır. Anket için ayrıca zorunlu bir halka açık yönetim sayfası gerekmez; admin yönetir, ziyaretçi ana sayfada oy verir.

IP adresini açık biçimde saklamak yerine mümkünse oturum/cookie + salt'lı hash kullanılmalı ve KVKK saklama süresi belirlenmelidir. Yalnız cookie kesin tekrar oy engeli sağlamaz; bunun kabul edilebilirliği ürün kararıdır.

**Öncelik:** Düşük-orta. Mevcut ana işlevlerden ayrı yeni kapsam olduğu için diğer sayfa uyarlamalarından sonra yapılmalıdır.

## 3. Ne birleştirilmeli, ne ayrı kalmalı?

### Ortaklaştırılacak tasarım

- Halka açık sayfalar `components.layouts.modern` header/footer yapısını kullanmalı.
- Renkler: lacivert (`blue-950`), turuncu (`orange-500/600`), açık gri zemin.
- Inter fontu, yuvarlatılmış kartlar, ortak buton ve boş durum dili korunmalı.
- Telefon, e-posta, logo ve adres `Setting` modelinden gelmeli.

### Ayrı kalacak uygulama yüzeyleri

- Halka açık site: Blade/Livewire ve modern layout.
- Admin: Filament `/admin`; HTML admin paneliyle değiştirilmemeli.
- Üye paneli: Filament `/portal`; halka açık layout içine gömülmemeli.
- Editör paneli: Filament `/editor`; admin veya portal ile birleştirilmemeli.

Bu ayrım rol kontrolünü, EİDS iş akışını ve bakım kolaylığını korur.

## 4. Önerilen uygulama sırası

1. **İlan detayını modernleştirme:** Görsel etkisi yüksek, veri modeli büyük ölçüde hazır.
2. **İletişim sayfasını modernleştirme:** Düşük riskli ve hızlı.
3. **Kayıt sayfasını modernleştirme:** Mevcut e-posta OTP ve hukuki onaylar korunarak.
4. **İlan verme wizard'ının görünümünü iyileştirme:** Filament içinde, EİDS akışına dokunmadan.
5. **Üye dashboard widget'ları:** Gerçek ilan durum sayılarına bağlanarak.
6. **Admin navigasyonu ve dashboard widget'ları:** Sahte analitik olmadan.
7. **Anket modülü:** Ayrı veri modeli, oy güvenliği ve admin kaynağıyla.

## 5. Son karar özeti

| Prototip | Yerleşim | Yaklaşım | Öncelik |
|---|---|---|---|
| `ilan-detayi.html` | `/ilan/{slug}` | Mevcut Blade detayını kapsamlı yenile | Yüksek |
| `iletişim.html` | `/iletisim` | Modern layout + dinamik ayarlar | Yüksek |
| `üyeol.html` | `/kayit` | Tasarımı al, SMS mantığını alma | Orta-yüksek |
| `ilanver.html` | `/portal/listings/create` | Filament wizard'ı görsel olarak geliştir | Yüksek/riskli |
| `üyepaneli.html` | `/portal` | Dashboard widget ve tema referansı | Orta-yüksek |
| `admin1.html` | `/admin` | Filament tema/dashboard referansı | Orta |
| `anket-yonetim.html` | `/admin/polls` + ana sayfa widget'ı | Yeni veritabanı tabanlı modül | Düşük-orta |

**Ana prensip:** Tasarımlar kullanılmalı; prototiplerdeki sabit veri, localStorage, sahte login, doğrudan silme/yayınlama ve istemci tarafı yetki mantığı kullanılmamalıdır.
