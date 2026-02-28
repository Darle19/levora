<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_flight', function (Blueprint $table) {
            $table->foreignId('tour_id')->constrained()->onDelete('cascade');
            $table->foreignId('flight_id')->constrained()->onDelete('cascade');
            $table->string('direction')->default('outbound'); // 'outbound' or 'return'
            $table->primary(['tour_id', 'flight_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_flight');
    }
};
