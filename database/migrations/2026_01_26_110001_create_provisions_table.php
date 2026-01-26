<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('provisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained('credits')->cascadeOnDelete();
            $table->enum('classification', ['saine', '1-30', '31-60', '61-90', '>90']);
            $table->decimal('provision_rate', 5, 2); // Taux appliqué
            $table->decimal('outstanding_amount', 15, 2); // Capital restant dû
            $table->decimal('provision_amount', 15, 2); // Montant provisionné
            $table->string('currency', 10);
            $table->date('calculated_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['credit_id', 'calculated_at']);
            $table->index('classification');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provisions');
    }
};
