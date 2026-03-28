<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite: set default value for tour_type_id so NOT NULL doesn't fail
        // First ensure at least one tour_type exists
        $exists = DB::table('tour_types')->exists();
        if (! $exists) {
            DB::table('tour_types')->insert([
                'name_en' => 'Standard',
                'name_ru' => 'Стандарт',
                'name_uz' => 'Standart',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $defaultId = DB::table('tour_types')->value('id');

        // Set default for existing and future rows
        DB::statement("UPDATE tours SET tour_type_id = {$defaultId} WHERE tour_type_id IS NULL");

        // For SQLite, we can't ALTER COLUMN to nullable, but we can set a default
        // so new inserts without tour_type_id get the default value
        DB::statement("CREATE TABLE IF NOT EXISTS _tours_default_fix (id INTEGER)");
        DB::statement("DROP TABLE IF EXISTS _tours_default_fix");

        // Pragmatic fix: just ensure the column has a default
        // SQLite doesn't support ALTER COLUMN DEFAULT, so we use a trigger
        DB::statement("
            CREATE TRIGGER IF NOT EXISTS tours_default_tour_type_id
            AFTER INSERT ON tours
            FOR EACH ROW
            WHEN NEW.tour_type_id IS NULL
            BEGIN
                UPDATE tours SET tour_type_id = {$defaultId} WHERE id = NEW.id;
            END
        ");
    }

    public function down(): void
    {
        DB::statement("DROP TRIGGER IF EXISTS tours_default_tour_type_id");
    }
};
