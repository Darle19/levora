<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flight_paths', function (Blueprint $table) {
            $table->foreignId('tour_template_id')->nullable()->after('id')
                ->constrained('tour_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('flight_paths', function (Blueprint $table) {
            $table->dropForeign(['tour_template_id']);
            $table->dropColumn('tour_template_id');
        });
    }
};
