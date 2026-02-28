<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('program_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->foreignId('resort_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('hotel_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('transport_type_id')->constrained()->onDelete('cascade');
            $table->foreignId('departure_city_id')->constrained('cities')->onDelete('cascade');
            $table->integer('nights');
            $table->decimal('price', 10, 2);
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->date('date_from');
            $table->date('date_to');
            $table->integer('adults')->default(1);
            $table->integer('children')->default(0);
            $table->foreignId('meal_type_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('is_available')->default(true);
            $table->boolean('is_hot')->default(false);
            $table->boolean('instant_confirmation')->default(false);
            $table->boolean('no_stop_sale')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tours');
    }
};
