<?php

// File: database/migrations/2026_03_30_200002_create_tour_template_legs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_template_legs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_template_id')->constrained('tour_templates')->cascadeOnDelete();
            $table->unsignedInteger('leg_order');
            $table->foreignId('departure_city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('arrival_city_id')->constrained('cities')->cascadeOnDelete();
            $table->date('departure_date');
            $table->date('arrival_date');
            $table->string('preferred_time_range', 20)->default('any'); // morning, afternoon, evening, any
            $table->unsignedInteger('passenger_count')->default(1);
            $table->timestamps();

            $table->index(['tour_template_id', 'leg_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_template_legs');
    }
};
