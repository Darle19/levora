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
        Schema::create('cruise_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ship_id')->constrained()->onDelete('cascade');
            $table->string('route_name');
            $table->text('description')->nullable();
            $table->foreignId('from_port_id')->constrained('ports')->onDelete('cascade');
            $table->foreignId('to_port_id')->nullable()->constrained('ports')->onDelete('cascade');
            $table->integer('duration_days');
            $table->decimal('price', 10, 2);
            $table->foreignId('currency_id')->constrained()->onDelete('cascade');
            $table->date('date_from');
            $table->date('date_to');
            $table->boolean('is_available')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cruise_routes');
    }
};
