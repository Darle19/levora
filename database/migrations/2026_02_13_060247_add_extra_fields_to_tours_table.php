<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->boolean('child_bed_separate')->default(false)->after('no_stop_sale');
            $table->boolean('comfortable_seats')->default(false)->after('child_bed_separate');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table) {
            $table->dropColumn(['child_bed_separate', 'comfortable_seats']);
        });
    }
};
