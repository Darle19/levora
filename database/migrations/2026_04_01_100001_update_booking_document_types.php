<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Update old type values to new ones
        DB::table('booking_documents')->where('type', 'confirmation')->update(['type' => 'tourist_voucher']);
        DB::table('booking_documents')->where('type', 'voucher')->update(['type' => 'hotel_voucher']);
        DB::table('booking_documents')->where('type', 'ticket')->update(['type' => 'eticket']);
        // Delete memo documents (no longer generated)
        DB::table('booking_documents')->where('type', 'memo')->delete();
    }

    public function down(): void
    {
        DB::table('booking_documents')->where('type', 'tourist_voucher')->update(['type' => 'confirmation']);
        DB::table('booking_documents')->where('type', 'hotel_voucher')->update(['type' => 'voucher']);
        DB::table('booking_documents')->where('type', 'eticket')->update(['type' => 'ticket']);
    }
};
