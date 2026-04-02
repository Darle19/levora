<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->boolean('is_excursion')->default(false)->after('is_one_time');
        });
    }

    public function down(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->dropColumn('is_excursion');
        });
    }
};
