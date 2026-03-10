<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_group_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hotel_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->json('check_in_dates');
            $table->unsignedSmallInteger('nights')->default(6);
            $table->unsignedSmallInteger('pax_count');
            $table->unsignedSmallInteger('rooms_count');
            $table->string('room_configuration');
            $table->string('nationality')->nullable();
            $table->decimal('rate_per_night', 10, 2);
            $table->foreignId('currency_id')->constrained();
            $table->foreignId('meal_type_id')->nullable()->constrained();
            $table->text('cancellation_policy')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_group_offers');
    }
};
