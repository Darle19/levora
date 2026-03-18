<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_amadeus_flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tour_amadeus_segment_id')->constrained('tour_amadeus_segments')->cascadeOnDelete();
            $table->string('amadeus_offer_id')->nullable();
            $table->string('airline', 10);
            $table->string('airline_name');
            $table->string('flight_number', 20);
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->date('departure_date');
            $table->string('departure_time', 5);
            $table->date('arrival_date');
            $table->string('arrival_time', 5);
            $table->string('duration', 20)->nullable();
            $table->unsignedSmallInteger('stops')->default(0);
            $table->string('cabin_class', 20);
            $table->decimal('price_per_adult', 10, 2);
            $table->decimal('price_per_child', 10, 2)->nullable();
            $table->decimal('price_per_infant', 10, 2)->nullable();
            $table->decimal('price_total', 10, 2);
            $table->string('currency', 3);
            $table->json('raw_offer_data')->nullable();
            $table->timestamps();

            $table->unique(['booking_id', 'tour_amadeus_segment_id'], 'booking_amadeus_segment_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_amadeus_flights');
    }
};
