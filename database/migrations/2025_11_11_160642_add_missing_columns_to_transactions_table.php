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
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignUuid('user_id')->constrained('users')->after('id');
            $table->enum('statut', ['reussi', 'echec', 'en_cours'])->default('reussi')->after('montant');
            $table->timestamp('date_operation')->useCurrent()->after('statut');
            $table->string('description')->nullable()->after('date_operation');
            $table->string('reference')->unique()->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['user_id', 'statut', 'date_operation', 'description', 'reference']);
        });
    }
};
