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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('airline_id')->constrained()->onDelete('cascade');
            $table->foreignId('from_airport_id')->constrained('airports')->onDelete('cascade');
            $table->foreignId('to_airport_id')->constrained('airports')->onDelete('cascade');
            $table->string('flight_number');
            $table->time('departure_time');
            $table->time('arrival_time');
            $table->decimal('price', 10, 2);
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->integer('available_seats');
            $table->date('date');
            $table->boolean('is_direct')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
