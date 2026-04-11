<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->string('baggage', 50)->default('1PC 20 kg')->after('class_type');
        });

        // Centrum Air (C2) allows 23 kg
        $centrumId = DB::table('airlines')->where('code', 'C2')->value('id');
        if ($centrumId) {
            DB::table('flights')->where('airline_id', $centrumId)->update(['baggage' => '1PC 23 kg']);
        }
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn('baggage');
        });
    }
};
