<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_amenity_types', function (Blueprint $table) {
            $table->id();
            $table->string('name_en');
            $table->string('name_ru')->nullable();
            $table->string('name_uz')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('hotel_hotel_amenity_type', function (Blueprint $table) {
            $table->foreignId('hotel_id')->constrained()->onDelete('cascade');
            $table->foreignId('hotel_amenity_type_id')->constrained()->onDelete('cascade');
            $table->primary(['hotel_id', 'hotel_amenity_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_hotel_amenity_type');
        Schema::dropIfExists('hotel_amenity_types');
    }
};
