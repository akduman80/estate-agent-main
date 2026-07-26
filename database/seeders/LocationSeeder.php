<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        // Örnek il ve ilçeler (Gerçek senaryoda tüm TR verisi basılır)
        $cities = [
            ['id' => 34, 'name' => 'İstanbul'],
            ['id' => 6,  'name' => 'Ankara'],
            ['id' => 35, 'name' => 'İzmir'],
            ['id' => 80, 'name' => 'Osmaniye'],
        ];

        // Redis cache'i temizleyip güncel lokasyonları yazıyoruz
        Cache::forget('locations_cities');
        Cache::rememberForever('locations_cities', function () use ($cities) {
            return $cities;
        });
    }
}
