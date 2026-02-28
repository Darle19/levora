<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tourists', function (Blueprint $table) {
            $table->string('title')->nullable()->after('booking_id'); // MR, MRS, CHD, INF
            $table->string('document_type')->nullable()->after('nationality'); // passport, birth_certificate
            $table->string('passport_series')->nullable()->after('document_type');
            $table->date('passport_issued')->nullable()->after('passport_expiry');
            $table->string('passport_issued_by')->nullable()->after('passport_issued');
            $table->string('birth_country')->nullable()->after('birth_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tourists', function (Blueprint $table) {
            $table->dropColumn(['title', 'document_type', 'passport_series', 'passport_issued', 'passport_issued_by', 'birth_country']);
        });
    }
};
