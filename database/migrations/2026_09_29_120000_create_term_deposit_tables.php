<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('term_deposit_products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->unsignedInteger('term_days');
            $table->decimal('annual_interest_rate', 8, 4);
            $table->decimal('early_withdrawal_penalty_rate', 8, 4);
            $table->enum('early_withdrawal_interest_policy', ['forfeit', 'pro_rata']);
            $table->decimal('minimum_amount', 15, 2)->default(0.01);
            $table->decimal('maximum_amount', 15, 2)->nullable();
            $table->foreignId('liability_account_id')->constrained('comptes')->restrictOnDelete();
            $table->foreignId('interest_expense_account_id')->constrained('comptes')->restrictOnDelete();
            $table->foreignId('penalty_income_account_id')->constrained('comptes')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('term_deposits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_deposit_product_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('source_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('settlement_account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('renewed_from_id')->nullable()->constrained('term_deposits')->nullOnDelete();
            $table->string('currency', 3);
            $table->decimal('principal_amount', 15, 2);
            $table->unsignedInteger('term_days');
            $table->decimal('annual_interest_rate', 8, 4);
            $table->decimal('early_withdrawal_penalty_rate', 8, 4);
            $table->enum('early_withdrawal_interest_policy', ['forfeit', 'pro_rata']);
            $table->date('opened_at');
            $table->date('matures_at');
            $table->dateTime('closed_at')->nullable();
            $table->enum('status', ['active', 'matured', 'closed', 'early_withdrawn', 'cancelled'])->default('active');
            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->decimal('penalty_amount', 15, 2)->default(0);
            $table->decimal('payout_amount', 15, 2)->default(0);
            $table->string('settlement_reference')->nullable()->unique();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['status', 'matures_at']);
        });

        Schema::create('term_deposit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('term_deposit_id')->constrained()->restrictOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('reference')->unique();
            $table->string('currency', 3);
            $table->decimal('amount', 15, 2);
            $table->decimal('term_balance_after', 15, 2);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->dateTime('occurred_at');
            $table->timestamps();
            $table->index(['term_deposit_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('term_deposit_transactions');
        Schema::dropIfExists('term_deposits');
        Schema::dropIfExists('term_deposit_products');
    }
};