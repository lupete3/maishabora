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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('agent_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('last_transaction_at')
                ->nullable();

            $table->index('role');

            $table->index('agent_id');

            $table->index('last_transaction_at');

            $table->index([
                'role',
                'agent_id'
            ]);

            $table->index([
                'role',
                'agent_id',
                'last_transaction_at'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['agent_id']);
            $table->dropColumn(['agent_id', 'last_transaction_at']);
        });
    }
};
