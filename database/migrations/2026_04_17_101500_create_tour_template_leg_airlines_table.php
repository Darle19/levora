<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Many-to-many between a tour template leg and the airlines that may serve it.
 *
 * Previously tour_template_legs.airline_id pinned exactly one carrier per leg,
 * which could not express "this leg can be flown by either Centrum or Qanot
 * Sharq". This pivot lets ops list every allowed airline per leg; the path
 * generator then produces one FlightPath per valid airline combo, pairing
 * outbound and return by round_trip_pair_id.
 *
 * The legacy airline_id column is kept untouched for backward compatibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_template_leg_airlines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_template_leg_id')->constrained()->cascadeOnDelete();
            $table->foreignId('airline_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tour_template_leg_id', 'airline_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_template_leg_airlines');
    }
};
