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
        Schema::create('flight_path_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flight_path_id')->constrained()->cascadeOnDelete();
            $table->foreignId('flight_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('leg_order')->default(1);
            $table->string('direction')->default('outbound'); // outbound/return
            $table->timestamps();

            $table->index(['flight_path_id', 'leg_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_path_legs');
    }
};
