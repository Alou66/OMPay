<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop all constraints on the type column
        DB::statement("ALTER TABLE comptes DROP CONSTRAINT IF EXISTS comptes_type_check");

        // Convert to text to allow updates
        DB::statement("ALTER TABLE comptes ALTER COLUMN type TYPE text");

        // Update existing data
        DB::statement("UPDATE comptes SET type = CASE
            WHEN type = 'cheque' THEN 'simple'
            WHEN type = 'epargne' THEN 'marchand'
            ELSE 'simple'
        END");

        // Add new check constraint
        DB::statement("ALTER TABLE comptes ADD CONSTRAINT comptes_type_check CHECK (type IN ('marchand', 'simple'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new constraint
        DB::statement("ALTER TABLE comptes DROP CONSTRAINT IF EXISTS comptes_type_check");

        // Revert data
        DB::statement("UPDATE comptes SET type = CASE
            WHEN type = 'simple' THEN 'cheque'
            WHEN type = 'marchand' THEN 'epargne'
            ELSE 'cheque'
        END");

        // Add old check constraint
        DB::statement("ALTER TABLE comptes ADD CONSTRAINT comptes_type_check CHECK (type IN ('cheque', 'epargne'))");
    }
};
