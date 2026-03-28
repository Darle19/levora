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
        Schema::create('flight_paths', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->date('departure_date');
            $table->foreignId('departure_city_id')->constrained('cities');
            $table->decimal('total_price', 10, 2)->default(0);
            $table->foreignId('currency_id')->constrained('currencies');
            $table->unsignedSmallInteger('nights')->default(7);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->index(['route_name', 'departure_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_paths');
    }
};
