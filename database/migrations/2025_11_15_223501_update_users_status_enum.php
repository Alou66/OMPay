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
        // Drop the existing check constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_status_check');

        // Add the new check constraint with additional value
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status::text = ANY (ARRAY['Actif'::character varying, 'Inactif'::character varying, 'pending_verification'::character varying]::text[]))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new check constraint
        DB::statement('ALTER TABLE users DROP CONSTRAINT users_status_check');

        // Recreate the original check constraint
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_status_check CHECK (status::text = ANY (ARRAY['Actif'::character varying, 'Inactif'::character varying]::text[]))");
    }
};
