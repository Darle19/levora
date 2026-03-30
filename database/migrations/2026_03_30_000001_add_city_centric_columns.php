<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Flights: add origin_city_id + destination_city_id
        Schema::table('flights', function (Blueprint $table) {
            $table->foreignId('origin_city_id')->nullable()->after('id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('destination_city_id')->nullable()->after('origin_city_id')->constrained('cities')->cascadeOnDelete();
            $table->index(['origin_city_id', 'destination_city_id']);
        });

        // Additional services: add city_id (nullable = global service)
        Schema::table('additional_services', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('id')->constrained('cities')->cascadeOnDelete();
            $table->index('city_id');
        });

        // Banners: add city_id + dimension metadata
        Schema::table('banners', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('id')->constrained('cities')->cascadeOnDelete();
            $table->unsignedInteger('width')->default(2000)->after('image');
            $table->unsignedInteger('height')->default(500)->after('width');
            $table->index('city_id');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropForeign(['origin_city_id']);
            $table->dropForeign(['destination_city_id']);
            $table->dropIndex(['origin_city_id', 'destination_city_id']);
            $table->dropColumn(['origin_city_id', 'destination_city_id']);
        });

        Schema::table('additional_services', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropIndex(['city_id']);
            $table->dropColumn('city_id');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->dropIndex(['city_id']);
            $table->dropColumn(['city_id', 'width', 'height']);
        });
    }
};
