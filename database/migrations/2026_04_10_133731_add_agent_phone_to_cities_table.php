<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->string('agent_phone', 50)->nullable()->after('is_departure');
            $table->string('agent_name', 255)->nullable()->after('agent_phone');
        });
    }

    public function down(): void
    {
        Schema::table('cities', function (Blueprint $table) {
            $table->dropColumn(['agent_phone', 'agent_name']);
        });
    }
};
