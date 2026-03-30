<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            // Drop unique constraint on code, make nullable
            $table->dropUnique(['code']);
        });

        Schema::table('additional_services', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('additional_services', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
            $table->unique('code');
        });
    }
};
