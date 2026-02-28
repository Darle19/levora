<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('amadeus_flight_cache', function (Blueprint $table) {
            $table->id();
            $table->string('search_hash', 64)->unique();
            $table->json('response_data');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('amadeus_flight_cache');
    }
};
