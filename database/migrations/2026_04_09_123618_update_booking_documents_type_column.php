<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite doesn't support ALTER COLUMN, so recreate the column
        // Change type from enum to string to allow new document types
        Schema::table('booking_documents', function (Blueprint $table) {
            $table->string('type_new', 50)->default('')->after('type');
        });

        DB::table('booking_documents')->update([
            'type_new' => DB::raw('"type"'),
        ]);

        Schema::table('booking_documents', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        Schema::table('booking_documents', function (Blueprint $table) {
            $table->renameColumn('type_new', 'type');
        });
    }

    public function down(): void
    {
        // No rollback needed — string is more permissive than enum
    }
};
