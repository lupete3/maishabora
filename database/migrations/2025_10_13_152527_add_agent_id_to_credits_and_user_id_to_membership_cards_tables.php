<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajout de l'agent_id dans credits
        Schema::table('credits', function (Blueprint $table) {
            $table->foreignId('agent_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->onDelete('cascade');
        });

        // Ajout du user_id (agent responsable) dans membership_cards
        Schema::table('membership_cards', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->after('member_id')
                ->constrained('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('credits', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });

        Schema::table('membership_cards', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
