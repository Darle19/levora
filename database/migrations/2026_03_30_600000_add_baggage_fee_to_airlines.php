<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('airlines', function (Blueprint $table) {
            $table->decimal('baggage_fee', 8, 2)->default(0)->after('is_active');
        });

        // Set $30 for Turkish Airlines and Azerbaijan Airlines
        DB::table('airlines')->where('code', 'TK')->update(['baggage_fee' => 30]);
        DB::table('airlines')->where('code', 'J2')->update(['baggage_fee' => 30]);
    }

    public function down(): void
    {
        Schema::table('airlines', function (Blueprint $table) {
            $table->dropColumn('baggage_fee');
        });
    }
};
