<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['confirmation', 'memo', 'voucher', 'ticket', 'insurance']);
            $table->foreignId('tourist_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('file_path');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_documents');
    }
};
