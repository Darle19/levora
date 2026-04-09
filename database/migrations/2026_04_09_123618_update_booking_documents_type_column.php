<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the composite index that references 'type' first
        Schema::table('booking_documents', function (Blueprint $table) {
            $table->dropIndex('booking_documents_booking_id_type_index');
        });

        // Add new string column
        Schema::table('booking_documents', function (Blueprint $table) {
            $table->string('type_new', 50)->default('')->after('type');
        });

        // Copy data
        DB::table('booking_documents')->update([
            'type_new' => DB::raw('"type"'),
        ]);

        // Drop old enum column
        Schema::table('booking_documents', function (Blueprint $table) {
            $table->dropColumn('type');
        });

        // Rename new column
        Schema::table('booking_documents', function (Blueprint $table) {
            $table->renameColumn('type_new', 'type');
        });

        // Recreate index
        Schema::table('booking_documents', function (Blueprint $table) {
            $table->index(['booking_id', 'type']);
        });
    }

    public function down(): void
    {
        // No rollback needed — string is more permissive than enum
    }
};
