<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-tour agent_fee and hidden_fee. NULL means "use the global Setting
 * (tour_agent_fee / tour_hidden_fee) as before" — existing templates keep
 * the current behaviour until ops sets an explicit override.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tour_templates', function (Blueprint $table) {
            $table->decimal('agent_fee', 10, 2)->nullable()->after('margin_percent');
            $table->decimal('hidden_fee', 10, 2)->nullable()->after('agent_fee');
        });
    }

    public function down(): void
    {
        Schema::table('tour_templates', function (Blueprint $table) {
            $table->dropColumn(['agent_fee', 'hidden_fee']);
        });
    }
};
