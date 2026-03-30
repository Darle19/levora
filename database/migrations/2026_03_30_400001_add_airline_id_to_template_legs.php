<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_template_legs', function (Blueprint $table) {
            $table->foreignId('airline_id')->nullable()->after('arrival_city_id')
                ->constrained('airlines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tour_template_legs', function (Blueprint $table) {
            $table->dropForeign(['airline_id']);
            $table->dropColumn('airline_id');
        });
    }
};
