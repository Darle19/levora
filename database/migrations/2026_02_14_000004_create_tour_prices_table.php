<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tour_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tour_id')->constrained()->onDelete('cascade');
            $table->foreignId('room_type_id')->constrained()->onDelete('cascade');
            $table->decimal('price_adult', 10, 2);
            $table->decimal('price_child', 10, 2)->nullable();
            $table->decimal('price_infant', 10, 2)->nullable();
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->integer('availability')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tour_prices');
    }
};
