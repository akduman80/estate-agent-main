<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            foreach (['price', 'square_meters', 'net_square_meters', 'deposit_price', 'dues_price'] as $field) {
                $table->decimal($field, 65, 30)->nullable($field !== 'price')->change();
            }
        });
    }

    public function down(): void
    {
        // Keep precision on rollback; reducing the scale would destroy entered values.
    }
};
