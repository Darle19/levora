<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->decimal('hard_block_price', 10, 2)->nullable()->after('price_infant');
            $table->decimal('soft_block_price', 10, 2)->nullable()->after('hard_block_price');
            $table->integer('soft_block_release_days')->nullable()->after('soft_block_price');
        });
    }

    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            $table->dropColumn(['hard_block_price', 'soft_block_price', 'soft_block_release_days']);
        });
    }
};
