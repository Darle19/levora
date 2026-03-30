<?php

// File: database/migrations/2026_03_30_200000_alter_tour_templates_add_status_currency_margin.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_templates', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->after('is_active');
            $table->string('base_currency', 3)->default('USD')->after('status');
            $table->unsignedInteger('margin_percent')->default(0)->after('base_currency');
        });
    }

    public function down(): void
    {
        Schema::table('tour_templates', function (Blueprint $table) {
            $table->dropColumn(['status', 'base_currency', 'margin_percent']);
        });
    }
};
