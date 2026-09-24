# Android Projeler Uygulaması – ZYDEMLAK Web Entegrasyonu

Bu doküman Android geliştiricisinin proje yayınlama akışını güncellemesi için hazırlanmıştır. Web tarafı eski uygulama sürümüyle uyumluluğu korur; ancak kalıcı ve cihazdan bağımsız eşleştirme için aşağıdaki değişiklikler gereklidir.

## Amaç

- İlk gönderimde web sitesinde tek proje oluşturmak.
- Aynı proje tekrar gönderildiğinde aynı web kaydını güncellemek.
- Ağ tekrarı veya kullanıcının arka arkaya dokunması halinde mükerrer kayıt oluşturmamak.
- Projenin koordinat ve seçilen fotoğraflarını web sitesinde güncel tutmak.
- Uygulamadan yayın kaldırıldığında web kaydını silmeden gizlemek.

## 1. Kalıcı proje kimliği

Room veritabanındaki `Project` kaydına aşağıdaki alan eklenmelidir:

```text
webProjectUuid: String
```

- UUID proje ilk oluşturulurken `UUID.randomUUID().toString()` ile bir kez üretilmelidir.
- Proje düzenlendiğinde UUID değişmemelidir.
- Uygulama güncellemesinde mevcut projeler için migration sırasında UUID üretilmelidir.
- Proje adı, adres, koordinat veya Room otomatik ID değeri kalıcı web kimliği yerine kullanılmamalıdır.
- Room migration uygulanmalı; `fallbackToDestructiveMigration` kullanılmamalıdır.

## 2. Yayınlama endpoint'i

```http
POST https://www.zydemlak.com/api/v1/projects/publish
Authorization: Bearer <yayın anahtarı>
Accept: application/json
Content-Type: multipart/form-data
```

Yayın anahtarı kaynak koda, loglara veya bu dokümana yazılmamalıdır. Mevcut `BuildConfig.ZYD_PUBLISH_TOKEN` yöntemi kullanılabilir.

Her yayınlama ve güncelleme isteğinde zorunlu alanlar:

```text
local_project_id=<Room project id>
project_uuid=<kalıcı UUID>
action=publish | update
is_published=1
selected_photo_count=<gönderilen fotoğraf sayısı>
replace_photos=1
```

Sunucudan daha önce alınmışsa şu alan da gönderilmelidir:

```text
remote_publication_id=<sunucunun publication_id değeri>
```

`project_uuid` ana kimliktir. `remote_publication_id` ek doğrulama ve eski kayıtlarla eşleştirme için saklanmaya devam edilmelidir.

## 3. Proje alanları

Kullanıcının web'de yayınlanmasını seçtiği alanlar mevcut isimlerle gönderilmelidir:

```text
contractor_name
contractor_phone
address
street
island_parcel
detailed_address
latitude
longitude
floor_count
square_meters
room_count
bathroom_count
materials
start_date
end_date
contractor_notes
status
```

Yayın ekranındaki seçim, projenin web üzerindeki güncel ve tam görünümüdür. Kullanıcının artık yayınlamak istemediği alan gönderilmemelidir. Koordinat seçilmişse `latitude` ve `longitude` birlikte gönderilmelidir.

Fotoğraflar:

```text
photos[]=<dosya>
```

- En fazla 12 fotoğraf gönderilebilir.
- Her fotoğraf en fazla 10 MB olmalıdır.
- JPEG, PNG veya WebP kullanılmalıdır.
- `replace_photos=1`, gönderilen listenin web üzerindeki eski fotoğraf listesinin tamamının yerini alacağı anlamına gelir.
- Fotoğraf seçilmediyse `selected_photo_count=0` ve `replace_photos=1` gönderilmelidir; bu işlem web fotoğraflarını kaldırır.
- İlk fotoğraf kapak fotoğrafı kabul edilir.

## 4. Başarılı yanıt

İlk yayın `201`, güncelleme ve yayından kaldırma `200` döndürür:

```json
{
  "message": "Proje zydemlak.com üzerinde güncellendi.",
  "publication_id": "42",
  "id": 42,
  "project_uuid": "xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx",
  "is_published": true,
  "url": "https://www.zydemlak.com/projeler/...",
  "admin_url": "https://www.zydemlak.com/admin/projects/42/edit"
}
```

Başarılı yanıttan sonra `publication_id`, `project_uuid` ve yayın durumu yerel veritabanında saklanmalıdır. Yalnızca SharedPreferences kullanılması önerilmez; proje kaydıyla birlikte Room'da tutulmalıdır.

## 5. Yayından kaldırma

Proje uygulamada silinmeden önce veya kullanıcı “Web yayınını kaldır” dediğinde:

```text
local_project_id=<Room project id>
project_uuid=<kalıcı UUID>
remote_publication_id=<sunucu id>
action=unpublish
is_published=0
```

Başarılı yanıt alınmadan yerel yayın eşleştirmesi silinmemelidir. Ağ hatasında işlem tekrar denenebilmelidir. Web kaydı fiziksel olarak silinmez; yalnızca ziyaretçilere ve haritaya kapatılır.

Yerel proje doğrudan silinecekse kullanıcıya iki seçenek sunulmalıdır:

1. Yalnızca telefondan sil.
2. Web yayınını kaldır ve telefondan sil.

İkinci seçenekte web isteği başarısız olursa kullanıcı bilgilendirilmeli ve yeniden deneme imkânı verilmelidir.

## 6. Hata ve tekrar davranışı

- `401`: yayın anahtarı eksik veya geçersiz.
- `422`: alan doğrulama ya da proje kimliği eşleşme hatası. JSON `errors` içeriğini kullanıcıya anlaşılır biçimde gösterin.
- `429`: çok fazla istek; gecikmeli tekrar deneyin.
- `5xx` veya bağlantı hatası: aynı `project_uuid` ile tekrar gönderin. Yeni UUID üretmeyin.
- Yayın düğmesi işlem boyunca devre dışı kalmalı ve callback tamamlandığında her durumda yeniden açılmalıdır.
- Otomatik tekrarlar exponential backoff kullanmalıdır; aynı proje için eşzamanlı iki istek başlatılmamalıdır.

## 7. Kabul testi

1. Yeni proje oluşturun ve yayınlayın; `publication_id` kaydedilsin.
2. Aynı projeyi üç kez güncelleyin; her yanıtta aynı `publication_id` dönsün.
3. Başlık, koordinat ve fotoğraf değişikliklerinin aynı web proje sayfasında güncellendiğini doğrulayın.
4. İstek sırasında bağlantıyı kesip aynı istekle tekrar deneyin; ikinci web kaydı oluşmasın.
5. Uygulamayı kapatıp açın; proje eşleştirmesi Room'dan geri gelsin.
6. Yayını kaldırın; proje web listesinden ve haritadan kaybolsun, telefondaki proje korunsun.
7. Yeniden yayınlayın; eski web kaydı tekrar açılsın, yeni kayıt oluşmasın.

## 8. Geçiş notu

Mevcut uygulama `remote_publication_id` değerini SharedPreferences'ta tutmaktadır. Güncelleme sırasında bu değer Room kaydına taşınmalıdır. Kullanıcının mevcut eşleştirmeleri migration tamamlanmadan silinmemelidir.
