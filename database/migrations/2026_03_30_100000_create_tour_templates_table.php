<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_templates', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->foreignId('departure_city_id')->constrained('cities')->cascadeOnDelete();
            $table->unsignedInteger('total_nights')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tour_template_stays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_template_id')->constrained('tour_templates')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->unsignedInteger('stay_order');
            $table->unsignedInteger('nights')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_template_stays');
        Schema::dropIfExists('tour_templates');
    }
};
