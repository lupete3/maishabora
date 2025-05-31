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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->nullable()->constrained()->onDelete('set null'); // compte du membre
            $table->foreignId('agent_account_id')->nullable()->constrained('agent_accounts')->onDelete('set null'); // si c’est un agent
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // qui a fait l'opération
            $table->foreignId('credit_id')->nullable()->constrained()->onDelete('set null'); // si lié à un crédit

            $table->string('type'); // ex: dépôt, retrait, crédit, remboursement, écart_agent, virement_caisse
            $table->string('currency'); // USD ou CDF
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2); // solde après transaction
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
