<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_template_stay_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_template_stay_id')->constrained('tour_template_stays')->cascadeOnDelete();
            $table->foreignId('additional_service_id')->constrained('additional_services')->cascadeOnDelete();
            $table->unsignedBigInteger('price_cents');
            $table->string('currency', 3)->default('USD');
            $table->boolean('is_mandatory')->default(false);
            $table->timestamps();

            $table->unique(['tour_template_stay_id', 'additional_service_id'], 'stay_service_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_template_stay_services');
    }
};
