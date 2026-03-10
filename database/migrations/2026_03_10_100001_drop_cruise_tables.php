<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('cruise_routes');
        Schema::dropIfExists('ships');
        Schema::dropIfExists('cruise_companies');
        Schema::dropIfExists('ports');
    }

    public function down(): void
    {
        // These tables are permanently removed
    }
};
