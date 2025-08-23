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
        Schema::create('journals', function (Blueprint $table) {
            $table->id();
            $table->date('date_operation');
            $table->string('libelle');
            $table->string('reference')->nullable();
            $table->string('devise', 10)->default('USD');
            $table->decimal('montant_debit', 15, 2)->nullable();
            $table->decimal('montant_credit', 15, 2)->nullable();
            $table->enum('type_operation', ['debit', 'credit']);
            
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->foreignId('type_journal_id')->constrained('journal_types')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
