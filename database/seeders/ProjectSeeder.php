<?php

namespace Database\Seeders;

use App\Models\Project;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ProjectSeeder extends Seeder
{
    private const DEMO_VIDEO_URL = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    public function run(): void
    {
        $imageMap = $this->copyDemoImages();

        $projects = [
            ['title' => 'Rahime Hatun Yaşam Evleri', 'developer_name' => 'ZYD Yapı', 'status' => 'construction', 'neighborhood' => 'Rahime Hatun Mahallesi', 'latitude' => 37.0718200, 'longitude' => 36.2474300, 'image' => 'property-4.jpg', 'featured' => true],
            ['title' => 'Kurtuluş Cadde Projesi', 'developer_name' => 'Akdeniz İnşaat', 'status' => 'construction', 'neighborhood' => 'Kurtuluş Mahallesi', 'latitude' => 37.0759600, 'longitude' => 36.2588600, 'image' => 'property-5.jpg', 'featured' => true],
            ['title' => 'Eyüpsultan Rezidans', 'developer_name' => 'Güven Yapı', 'status' => 'planning', 'neighborhood' => 'Eyüpsultan Mahallesi', 'latitude' => 37.0634200, 'longitude' => 36.2408700, 'image' => 'property-2.jpg'],
            ['title' => 'Haraz Modern Konutları', 'developer_name' => 'Osmaniye Prestij', 'status' => 'construction', 'neighborhood' => 'Haraz Mahallesi', 'latitude' => 37.0647800, 'longitude' => 36.2649300, 'image' => 'property-6.jpg'],
            ['title' => 'Karaçay Park Evleri', 'developer_name' => 'Karaçay Yapı', 'status' => 'completed', 'neighborhood' => 'Yedi Ocak Mahallesi', 'latitude' => 37.0814400, 'longitude' => 36.2441700, 'image' => 'property-1.jpg', 'featured' => true],
            ['title' => 'Fakıuşağı Bahçe', 'developer_name' => 'Amanos İnşaat', 'status' => 'construction', 'neighborhood' => 'Fakıuşağı Mahallesi', 'latitude' => 37.0565100, 'longitude' => 36.2359300, 'image' => 'property-3.jpg'],
            ['title' => 'Yunus Emre Vadi', 'developer_name' => 'Vadi Proje', 'status' => 'planning', 'neighborhood' => 'Yunus Emre Mahallesi', 'latitude' => 37.0857600, 'longitude' => 36.2577400, 'image' => 'property-5.jpg'],
            ['title' => 'Adnan Menderes Panorama', 'developer_name' => 'Panorama Yapı', 'status' => 'construction', 'neighborhood' => 'Adnan Menderes Mahallesi', 'latitude' => 37.0683600, 'longitude' => 36.2722100, 'image' => 'property-4.jpg'],
        ];

        foreach ($projects as $index => $data) {
            $project = Project::firstOrNew(['slug' => Str::slug($data['title'])]);
            $project->forceFill([
                'title' => $data['title'],
                'developer_name' => $data['developer_name'],
                'status' => $data['status'],
                'city' => 'Osmaniye',
                'district' => 'Merkez',
                'neighborhood' => $data['neighborhood'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'cover_image' => $imageMap[$data['image']],
                'video_path' => null,
                'video_url' => self::DEMO_VIDEO_URL,
                'summary' => $data['neighborhood'].'nde modern mimari, güvenli yaşam alanları ve merkezi konum avantajı sunan yeni nesil konut projesi.',
                'content' => '<p>Geniş sosyal alanları, modern mimarisi ve ulaşım noktalarına yakınlığıyla aile yaşamı ve yatırım için tasarlanmıştır.</p><p>Proje hakkında güncel fiyat, kat planı ve teslim bilgileri için bizimle iletişime geçebilirsiniz.</p>',
                'is_featured' => $data['featured'] ?? false,
                'is_published' => true,
                'published_at' => now()->subDays($index + 1),
            ]);
            $project->save();

            $availableGalleryNames = collect(array_keys($imageMap))
                ->reject(fn (string $name): bool => $name === $data['image'])
                ->values();
            $rotation = $index % $availableGalleryNames->count();
            $galleryNames = $availableGalleryNames
                ->slice($rotation)
                ->concat($availableGalleryNames->slice(0, $rotation))
                ->values();

            $project->images()->delete();
            foreach ($galleryNames as $sortOrder => $galleryName) {
                $project->images()->create([
                    'image_path' => $imageMap[$galleryName],
                    'sort_order' => $sortOrder,
                ]);
            }
        }
    }

    private function copyDemoImages(): array
    {
        $names = ['property-1.jpg', 'property-2.jpg', 'property-3.jpg', 'property-4.jpg', 'property-5.jpg', 'property-6.jpg'];
        $targetDirectory = storage_path('app/public/seed-projects');

        File::ensureDirectoryExists($targetDirectory);

        return collect($names)->mapWithKeys(function (string $name) use ($targetDirectory): array {
            $target = $targetDirectory.'/'.$name;

            File::copy(public_path('fe-assets/assets/img/demo/'.$name), $target);

            return [$name => 'seed-projects/'.$name];
        })->all();
    }
}
