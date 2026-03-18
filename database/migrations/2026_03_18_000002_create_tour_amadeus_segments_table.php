<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_amadeus_segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('leg_order');
            $table->foreignId('origin_airport_id')->constrained('airports');
            $table->foreignId('destination_airport_id')->constrained('airports');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['tour_id', 'leg_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_amadeus_segments');
    }
};
