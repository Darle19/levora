<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->boolean('is_departure')->default(false)->after('is_active');
        });

        // Set Tashkent as departure city
        DB::table('cities')->where('name_en', 'Tashkent')->update(['is_departure' => true]);
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn('is_departure');
        });
    }
};
