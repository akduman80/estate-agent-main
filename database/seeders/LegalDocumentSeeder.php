<?php

namespace Database\Seeders;

use App\Models\LegalDocument;
use Illuminate\Database\Seeder;

class LegalDocumentSeeder extends Seeder
{
    public function run(): void
    {
        $warning = '<div style="padding:12px;background:#fff7ed;border:1px solid #fdba74"><strong>Hukukçu incelemesi bekleyen taslak:</strong> Şirket unvanı, adresi, veri sorumlusu iletişim ve KVKK başvuru bilgileri canlı kullanımdan önce tamamlanmalıdır.</div>';

        $documents = [
            'kvkk-aydinlatma' => [
                'title' => 'KVKK Aydınlatma Metni (Taslak)',
                'content' => $warning.'<h2>İşlenen veriler</h2><p>Hesap ve ilan hizmetleri kapsamında kimlik, iletişim, hesap güvenliği, işletme, ilan, EİDS doğrulama, işlem ve onay kayıtları işlenir.</p><h2>Amaç ve hukuki sebepler</h2><p>Veriler; üyeliğin kurulması, kimlik ve ilan yetkisinin doğrulanması, güvenli giriş, ilan yönetimi, hukuki yükümlülüklerin yerine getirilmesi ve uyuşmazlıkların çözümü amaçlarıyla işlenir.</p><h2>Aktarım ve güvenlik</h2><p>Gerekli veriler mevzuatın izin verdiği ölçüde Ticaret Bakanlığı/EİDS, altyapı ve e-posta hizmet sağlayıcıları ile yetkili kamu kurumlarına aktarılabilir. T.C. kimlik ve vergi numaraları halka açık gösterilmez.</p><h2>İlgili kişi hakları</h2><p>6698 sayılı Kanun kapsamındaki bilgi alma, düzeltme, silme, itiraz ve başvuru hakları saklıdır. Başvuru iletişim bilgileri canlıya geçmeden önce bu metne eklenmelidir.</p>',
            ],
            'uyelik-sozlesmesi' => [
                'title' => 'Üyelik Sözleşmesi (Taslak)',
                'content' => $warning.'<h2>Hesap türleri</h2><p>Platform bireysel gayrimenkul sahibi ve yetki belgeli emlak işletmesi hesapları sunar. Emlak işletmesi hesabı yönetici incelemesi tamamlanmadan ilan yayınlayamaz.</p><h2>Hesap güvenliği</h2><p>Kullanıcı doğru ve güncel bilgi vermek, şifresini ve tek kullanımlık kodlarını korumakla yükümlüdür. Giriş şifre ve e-posta doğrulama koduyla tamamlanır.</p><h2>İlan kuralları</h2><p>Kimliği ve ilan yayınlama hakkı EİDS üzerinden doğrulanmayan ilan yayınlanmaz. EİDS ile doğrulanmış taşınmaz, konum, ada/parsel, ilan türü ve kaynak bilgileri başka bir ilan oluşturmak amacıyla değiştirilemez.</p><h2>Askıya alma ve sona erme</h2><p>Mevzuata, üçüncü kişi haklarına veya platform kurallarına aykırı hesap ve ilanlar askıya alınabilir ya da kapatılabilir.</p>',
            ],
            'gizlilik-politikasi' => [
                'title' => 'Gizlilik Politikası (Taslak)',
                'content' => $warning.'<h2>Gizlilik ilkeleri</h2><p>ZYDEMLAK hizmetin çalışması için gerekli verileri amaçla sınırlı, ölçülü ve güvenli biçimde işler. Parolalar güçlü özetleme yöntemiyle; T.C. kimlik, vergi numarası ve EİDS kullanıcı kodları şifreli olarak saklanır.</p><h2>Kamuya açık bilgiler</h2><p>T.C. kimlik numarası, vergi bilgileri, EİDS kullanıcı kodları ve doğrulama kayıtları ilanlarda veya herkese açık profillerde gösterilmez.</p><h2>Oturum ve güvenlik kayıtları</h2><p>Hesap güvenliği için IP adresi, tarayıcı bilgisi, oturum, doğrulama denemesi ve değişiklik geçmişi tutulabilir.</p>',
            ],
        ];

        foreach ($documents as $slug => $document) {
            LegalDocument::updateOrCreate(['slug' => $slug, 'version' => 'draft-1'], $document + [
                'is_required' => true,
                'is_active' => true,
                'published_at' => now(),
            ]);
        }
    }
}
