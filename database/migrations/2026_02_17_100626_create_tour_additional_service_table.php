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
        Schema::create('tour_additional_service', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->onDelete('cascade');
            $table->foreignId('additional_service_id')->constrained()->onDelete('cascade');
            $table->decimal('price_override', 10, 2)->nullable();
            $table->boolean('is_included')->default(false);
            $table->timestamps();

            $table->unique(['tour_id', 'additional_service_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tour_additional_service');
    }
};
