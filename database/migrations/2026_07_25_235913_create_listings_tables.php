<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. İlanlar Tablosu
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // İlan sahibi

            // Temel İlan Bilgileri
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('currency', 3)->default('TRY');
            $table->enum('type', ['sale', 'rent']); // Satılık / Kiralık
            $table->enum('category', ['housing', 'workplace', 'land']); // Konut / İşyeri / Arsa

            // EİDS & Tapu Doğrulama Alanları (Zorunlu TR Yapısı)
            $table->string('eids_status')->default('pending'); // pending, verified, rejected
            $table->string('eids_document_no')->nullable(); // Yetki belgesi / Taşınmaz No
            $table->string('city_id'); // İl
            $table->string('district_id'); // İlçe
            $table->string('neighborhood_id')->nullable(); // Mahalle
            $table->string('block_number')->nullable(); // Ada
            $table->string('parcel_number')->nullable(); // Parsel

            // Video & Medya
            $table->string('video_url')->nullable(); // YouTube / Vimeo linki

            // Statü ve Performans İndeksleri
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // Vitrin ilanı mı?
            $table->timestamps();

            // Performans için İndeksler (Sahibinden hızı için şart)
            $table->index(['is_active', 'type', 'category']);
            $table->index(['city_id', 'district_id']);
        });

        // 2. İlan Görselleri Tablosu
        Schema::create('listing_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_main')->default(false); // Kapak fotoğrafı mı?
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_images');
        Schema::dropIfExists('listings');
    }
};
