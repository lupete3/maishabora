<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('term_deposit_products', function (Blueprint $table) {
            $table->dropForeign(['liability_account_id']);
            $table->dropForeign(['interest_expense_account_id']);
            $table->dropForeign(['penalty_income_account_id']);
            $table->dropColumn([
                'liability_account_id',
                'interest_expense_account_id',
                'penalty_income_account_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('term_deposit_products', function (Blueprint $table) {
            $table->foreignId('liability_account_id')->constrained('comptes')->restrictOnDelete();
            $table->foreignId('interest_expense_account_id')->constrained('comptes')->restrictOnDelete();
            $table->foreignId('penalty_income_account_id')->constrained('comptes')->restrictOnDelete();
        });
    }
};