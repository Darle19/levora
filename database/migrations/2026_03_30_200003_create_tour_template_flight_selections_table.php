<?php

// File: database/migrations/2026_03_30_200003_create_tour_template_flight_selections_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_template_flight_selections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_template_leg_id')->constrained('tour_template_legs')->cascadeOnDelete();
            $table->foreignId('flight_id')->nullable()->constrained('flights')->nullOnDelete();
            $table->string('provider_flight_id')->nullable();
            $table->string('airline_code', 10);
            $table->string('flight_number', 20);
            $table->dateTime('departure_datetime');
            $table->dateTime('arrival_datetime');
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('seats_available')->default(0);
            $table->json('raw_data')->nullable();
            $table->timestamp('selected_at')->nullable();
            $table->timestamps();

            $table->unique('tour_template_leg_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_template_flight_selections');
    }
};
