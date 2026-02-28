<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->renameColumn('photos', 'images');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->renameColumn('facilities', 'amenities');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->string('name_en')->nullable()->after('name');
            $table->string('name_ru')->nullable()->after('name_en');
            $table->string('name_uz')->nullable()->after('name_ru');
            $table->text('description_en')->nullable()->after('description');
            $table->text('description_ru')->nullable()->after('description_en');
            $table->text('description_uz')->nullable()->after('description_ru');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            $table->decimal('latitude', 10, 8)->nullable()->after('website');
            $table->decimal('longitude', 10, 8)->nullable()->after('latitude');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropColumn([
                'name_en', 'name_ru', 'name_uz',
                'description_en', 'description_ru', 'description_uz',
                'phone', 'email', 'website',
                'latitude', 'longitude',
            ]);
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->renameColumn('amenities', 'facilities');
        });

        Schema::table('hotels', function (Blueprint $table) {
            $table->renameColumn('images', 'photos');
        });
    }
};
