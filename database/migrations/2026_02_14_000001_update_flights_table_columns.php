<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->renameColumn('price', 'price_adult');
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->decimal('price_child', 10, 2)->nullable()->after('price_adult');
            $table->decimal('price_infant', 10, 2)->nullable()->after('price_child');
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->renameColumn('date', 'departure_date');
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->date('arrival_date')->nullable()->after('departure_date');
            $table->string('class_type')->default('economy')->after('available_seats');
            $table->boolean('is_active')->default(true)->after('class_type');
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn('is_direct');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->boolean('is_direct')->default(true);
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn(['arrival_date', 'class_type', 'is_active']);
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->renameColumn('departure_date', 'date');
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn(['price_child', 'price_infant']);
        });

        Schema::table('flights', function (Blueprint $table) {
            $table->renameColumn('price_adult', 'price');
        });
    }
};
