<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->uuid('mobile_project_uuid')->nullable()->unique()->after('id');
            $table->unsignedBigInteger('mobile_local_project_id')->nullable()->unique()->after('mobile_project_uuid');
            $table->decimal('latitude', 10, 7)->nullable()->after('neighborhood');
            $table->decimal('longitude', 11, 7)->nullable()->after('latitude');
        });

        Schema::create('project_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['project_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_images');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropUnique(['mobile_project_uuid']);
            $table->dropUnique(['mobile_local_project_id']);
            $table->dropColumn([
                'mobile_project_uuid',
                'mobile_local_project_id',
                'latitude',
                'longitude',
            ]);
        });
    }
};
