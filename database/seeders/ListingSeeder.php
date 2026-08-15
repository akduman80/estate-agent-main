<?php

namespace Database\Seeders;

use App\Models\Listing;
use App\Models\ListingEidsVerification;
use App\Models\ListingImage;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ListingSeeder extends Seeder
{
    public function run(): void
    {
        $agent = User::where('email', 'emlakci@test.com')->first() ?? User::first();

        if (! $agent) {
            $this->call(UserSeeder::class);
            $agent = User::where('email', 'emlakci@test.com')->first();
        }

        $imageMap = $this->copyDemoImages();

        $listings = [
            [
                'title' => 'Osmaniye Kadirli Merkezde Satılık Daire',
                'type' => 'Satılık',
                'category' => 'Konut',
                'price' => 1850000,
                'square_meters' => 145,
                'city_id' => 'Osmaniye',
                'district_id' => 'Kadirli',
                'neighborhood_id' => 'Şehit Vedat Kocadallı',
                'description' => 'Kadirli merkezde, ulaşım ve sosyal alanlara yakın, geniş kullanımlı satılık daire.',
                'image' => 'property-1.jpg',
                'is_featured' => true,
            ],
            [
                'title' => 'İstanbul Kadıköyde Deniz Manzaralı Rezidans',
                'type' => 'Satılık',
                'category' => 'Konut',
                'price' => 12800000,
                'square_meters' => 165,
                'city_id' => 'İstanbul',
                'district_id' => 'Kadıköy',
                'neighborhood_id' => 'Fenerbahçe',
                'description' => 'Kadıköy sahile yakın, yüksek katta deniz manzaralı modern rezidans daire.',
                'image' => 'property-2.jpg',
                'is_featured' => true,
            ],
            [
                'title' => 'Ankara Çankayada Kiralık Ofis',
                'type' => 'Kiralık',
                'category' => 'İşyeri',
                'price' => 42000,
                'square_meters' => 210,
                'city_id' => 'Ankara',
                'district_id' => 'Çankaya',
                'neighborhood_id' => 'Kızılay',
                'description' => 'Kurumsal firmalara uygun, merkezi konumda, bölümlenebilir kiralık ofis.',
                'image' => 'property-3.jpg',
            ],
            [
                'title' => 'İzmir Urla İskelede Satılık Villa',
                'type' => 'Satılık',
                'category' => 'Villa',
                'price' => 19600000,
                'square_meters' => 280,
                'city_id' => 'İzmir',
                'district_id' => 'Urla',
                'neighborhood_id' => 'İskele',
                'description' => 'Bahçeli, müstakil, denize ve marina hattına yakın özel yaşam alanı.',
                'image' => 'property-4.jpg',
                'is_featured' => true,
            ],
            [
                'title' => 'Antalya Konyaaltında Eşyalı Kiralık Daire',
                'type' => 'Kiralık',
                'category' => 'Konut',
                'price' => 27500,
                'square_meters' => 110,
                'city_id' => 'Antalya',
                'district_id' => 'Konyaaltı',
                'neighborhood_id' => 'Hurma',
                'description' => 'Plaja kısa mesafede, site içinde, eşyalı ve bakımlı kiralık daire.',
                'image' => 'property-5.jpg',
            ],
            [
                'title' => 'Bursa Nilüferde Site İçinde Satılık Daire',
                'type' => 'Satılık',
                'category' => 'Konut',
                'price' => 4250000,
                'square_meters' => 135,
                'city_id' => 'Bursa',
                'district_id' => 'Nilüfer',
                'neighborhood_id' => 'Balat',
                'description' => 'Güvenlikli sitede, kapalı otoparklı, aile yaşamına uygun satılık daire.',
                'image' => 'property-6.jpg',
            ],
            [
                'title' => 'Muğla Bodrumda Yazlık Villa',
                'type' => 'Satılık',
                'category' => 'Villa',
                'price' => 24500000,
                'square_meters' => 320,
                'city_id' => 'Muğla',
                'district_id' => 'Bodrum',
                'neighborhood_id' => 'Yalıkavak',
                'description' => 'Yalıkavak hattında, özel havuzlu, geniş bahçeli yazlık villa.',
                'image' => 'property1.jpg',
                'is_featured' => true,
            ],
            [
                'title' => 'Konya Selçukluda Satılık Arsa',
                'type' => 'Satılık',
                'category' => 'Arsa',
                'price' => 3350000,
                'square_meters' => 720,
                'city_id' => 'Konya',
                'district_id' => 'Selçuklu',
                'neighborhood_id' => 'Sille',
                'description' => 'Gelişen bölgede, imar planına yakın, yatırım amaçlı satılık arsa.',
                'image' => 'property2.jpg',
            ],
            [
                'title' => 'Trabzon Ortahisarda Deniz Gören Daire',
                'type' => 'Satılık',
                'category' => 'Konut',
                'price' => 2980000,
                'square_meters' => 125,
                'city_id' => 'Trabzon',
                'district_id' => 'Ortahisar',
                'neighborhood_id' => 'Boztepe',
                'description' => 'Şehir merkezine yakın, ferah balkonlu ve deniz gören satılık daire.',
                'image' => 'property3.jpg',
            ],
            [
                'title' => 'Gaziantep Şehitkamilde Kiralık Dükkan',
                'type' => 'Kiralık',
                'category' => 'İşyeri',
                'price' => 65000,
                'square_meters' => 180,
                'city_id' => 'Gaziantep',
                'district_id' => 'Şehitkamil',
                'neighborhood_id' => 'İbrahimli',
                'description' => 'Cadde üzerinde, yüksek görünürlüklü, tabela değeri güçlü kiralık dükkan.',
                'image' => 'property4.jpg',
            ],
        ];

        Listing::withoutEvents(function () use ($agent, $imageMap, $listings): void {
            foreach ($listings as $index => $data) {
                $slug = 'ornek-' . Str::slug($data['city_id'] . '-' . $data['district_id'] . '-' . $data['title']);
                $image = $data['image'];

                unset($data['image']);

                $listing = Listing::firstOrNew(['slug' => $slug]);
                $listing->forceFill(array_merge($data, [
                    'user_id' => $agent->id,
                    'slug' => $slug,
                    'currency' => 'TRY',
                    'eids_status' => 'verified',
                    'eids_document_no' => 'EIDS-DEMO-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'is_active' => true,
                    'is_featured' => $data['is_featured'] ?? false,
                ]));
                $listing->save();

                ListingImage::updateOrCreate(
                    ['listing_id' => $listing->id, 'sort_order' => 0],
                    [
                        'image_path' => $imageMap[$image] ?? 'seed-listings/property-1.jpg',
                        'is_main' => true,
                    ]
                );

                ListingEidsVerification::updateOrCreate(
                    ['listing_id' => $listing->id],
                    [
                        'verified_at' => now(),
                        'expires_at' => now()->addYear(),
                        'city' => $data['city_id'],
                        'district' => $data['district_id'],
                        'neighborhood' => $data['neighborhood_id'],
                        'block' => (string) (100 + $index),
                        'parcel' => (string) (200 + $index),
                        'response' => ['seed' => true],
                    ]
                );
            }
        });

        Cache::forget('home_recent_listings');
    }

    private function copyDemoImages(): array
    {
        $sources = [
            'property-1.jpg' => public_path('fe-assets/assets/img/demo/property-1.jpg'),
            'property-2.jpg' => public_path('fe-assets/assets/img/demo/property-2.jpg'),
            'property-3.jpg' => public_path('fe-assets/assets/img/demo/property-3.jpg'),
            'property-4.jpg' => public_path('fe-assets/assets/img/demo/property-4.jpg'),
            'property-5.jpg' => public_path('fe-assets/assets/img/demo/property-5.jpg'),
            'property-6.jpg' => public_path('fe-assets/assets/img/demo/property-6.jpg'),
            'property1.jpg' => public_path('fe-assets/assets/img/property-1/property1.jpg'),
            'property2.jpg' => public_path('fe-assets/assets/img/property-1/property2.jpg'),
            'property3.jpg' => public_path('fe-assets/assets/img/property-1/property3.jpg'),
            'property4.jpg' => public_path('fe-assets/assets/img/property-1/property4.jpg'),
        ];

        $targetDirectory = storage_path('app/public/seed-listings');
        File::ensureDirectoryExists($targetDirectory);

        $imageMap = [];

        foreach ($sources as $name => $source) {
            $target = $targetDirectory . '/' . $name;

            if (File::exists($source) && ! File::exists($target)) {
                File::copy($source, $target);
            }

            $imageMap[$name] = 'seed-listings/' . $name;
        }

        return $imageMap;
    }
}
