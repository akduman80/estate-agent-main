<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PostSeeder extends Seeder
{
    public function run(): void
    {
        $editor = User::where('email', 'editor@test.com')->first();

        if (! $editor) {
            $this->call(UserSeeder::class);
            $editor = User::where('email', 'editor@test.com')->first();
        }

        $categories = collect([
            'Emlak Rehberi',
            'Piyasa Haberleri',
            'Yatırım',
            'EİDS Bilgilendirme',
        ])->mapWithKeys(function (string $name): array {
            $category = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name]
            );

            return [$name => $category];
        });

        $imageMap = $this->copyDemoImages();

        $posts = [
            [
                'title' => 'Konut Alırken Tapu Sürecinde Dikkat Edilmesi Gerekenler',
                'category' => 'Emlak Rehberi',
                'summary' => 'Tapu devri öncesinde kontrol edilmesi gereken belgeler, ödeme planı ve yetki adımlarını sade bir kontrol listesiyle toparladık.',
                'image' => 'blog1.jpg',
                'published_at' => now()->subDays(2),
                'content' => [
                    'Konut satın alma sürecinde en kritik aşamalardan biri tapu devridir. İlan bilgileri, tapu kaydı, satış bedeli ve ödeme yöntemi aynı çerçevede kontrol edilmelidir.',
                    'Alıcı ve satıcı tarafın kimlik bilgileri, vekaletname kullanılıyorsa vekalet kapsamı ve taşınmaz üzerinde ipotek ya da şerh olup olmadığı işlem öncesinde doğrulanmalıdır.',
                    'Zyd Emlak ekibi, satış sürecinde belge takibini düzenli şekilde yaparak tarafların daha güvenli hareket etmesine yardımcı olur.',
                ],
            ],
            [
                'title' => '2026 Yazında Kiralık Daire Arayanlar İçin Bölge Seçimi',
                'category' => 'Piyasa Haberleri',
                'summary' => 'Ulaşım, okul, sosyal alan ve aidat dengesi kiralık daire tercihinde fiyat kadar belirleyici hale geliyor.',
                'image' => 'blog2.jpg',
                'published_at' => now()->subDays(5),
                'content' => [
                    'Kiralık daire arayan kullanıcılar artık yalnızca metrekare ve oda sayısına bakmıyor. Toplu taşımaya erişim, otopark, site giderleri ve günlük yaşam kolaylığı da karar sürecinde güçlü rol oynuyor.',
                    'Özellikle büyük şehirlerde merkezi semtlerde arz sınırlı kaldığı için çevre ilçelerdeki yeni yaşam alanları daha fazla ilgi görüyor.',
                    'Doğru bölge seçimi için bütçeyi kira bedeliyle sınırlamamak, aidat ve ulaşım maliyetlerini de hesaba katmak gerekir.',
                ],
            ],
            [
                'title' => 'EİDS Doğrulaması İlan Güvenliğini Nasıl Artırıyor?',
                'category' => 'EİDS Bilgilendirme',
                'summary' => 'Elektronik İlan Doğrulama Sistemi, yetki kontrolü ve taşınmaz bilgilerinin doğrulanmasıyla daha güvenli bir ilan deneyimi sunar.',
                'image' => 'blog1.jpg',
                'published_at' => now()->subDays(8),
                'content' => [
                    'EİDS, emlak ilanlarında yetki ve taşınmaz bilgilerinin daha sağlıklı kontrol edilmesi için kullanılan önemli bir doğrulama altyapısıdır.',
                    'Bu sistem sayesinde ilan veren kişinin yetkisi ve ilandaki temel taşınmaz bilgileri daha şeffaf hale gelir.',
                    'Platformda EİDS doğrulaması yapılmış ilanlar, kullanıcıların güvenle inceleme yapmasına ve doğru temsilciyle iletişim kurmasına katkı sağlar.',
                ],
            ],
            [
                'title' => 'Arsa Yatırımında İmar Durumu Neden İlk Kontrol Olmalı?',
                'category' => 'Yatırım',
                'summary' => 'Arsa yatırımı yapmadan önce imar durumu, cephe, yol bağlantısı ve çevre gelişim planları birlikte değerlendirilmelidir.',
                'image' => 'blog2.jpg',
                'published_at' => now()->subDays(12),
                'content' => [
                    'Arsa yatırımlarında fiyat cazip görünse bile imar durumu netleşmeden karar vermek ciddi risk oluşturabilir.',
                    'Yol cephesi, yapılaşma koşulları, belediye plan notları ve bölgedeki altyapı çalışmaları yatırım değerini doğrudan etkiler.',
                    'Uzun vadeli kazanç hedefleyen yatırımcılar, yalnızca bugünkü fiyatı değil bölgenin gelişim potansiyelini de okumalıdır.',
                ],
            ],
        ];

        foreach ($posts as $data) {
            $category = $categories[$data['category']];
            $slug = Str::slug($data['title']);
            $image = $data['image'];
            $paragraphs = $data['content'];

            Post::updateOrCreate(
                ['slug' => $slug],
                [
                    'user_id' => $editor->id,
                    'category_id' => $category->id,
                    'title' => $data['title'],
                    'summary' => $data['summary'],
                    'content' => $this->toHtml($paragraphs),
                    'cover_image' => $imageMap[$image] ?? 'seed-posts/blog1.jpg',
                    'status' => 'published',
                    'published_at' => $data['published_at'],
                ]
            );
        }

        Cache::forget('blog_categories');
    }

    private function copyDemoImages(): array
    {
        $sources = [
            'blog1.jpg' => public_path('fe-assets/assets/img/blog1.jpg'),
            'blog2.jpg' => public_path('fe-assets/assets/img/blog2.jpg'),
        ];

        $targetDirectory = storage_path('app/public/seed-posts');
        File::ensureDirectoryExists($targetDirectory);

        $imageMap = [];

        foreach ($sources as $name => $source) {
            $target = $targetDirectory . '/' . $name;

            if (File::exists($source) && ! File::exists($target)) {
                File::copy($source, $target);
            }

            $imageMap[$name] = 'seed-posts/' . $name;
        }

        return $imageMap;
    }

    private function toHtml(array $paragraphs): string
    {
        return collect($paragraphs)
            ->map(fn (string $paragraph): string => '<p>' . e($paragraph) . '</p>')
            ->implode("\n");
    }
}
