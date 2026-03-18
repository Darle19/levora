<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('stay_order')->default(1);
            $table->foreignId('city_id')->nullable()->constrained();
            $table->foreignId('hotel_id')->nullable()->constrained();
            $table->foreignId('resort_id')->nullable()->constrained();
            $table->unsignedSmallInteger('nights');
            $table->foreignId('meal_type_id')->nullable()->constrained();
            $table->decimal('price_per_person', 10, 2)->nullable();
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tour_id', 'stay_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_stays');
    }
};
