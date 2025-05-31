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
        Schema::create('repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credit_id')->constrained()->onDelete('cascade');
            $table->date('due_date');
            $table->date('paid_date')->nullable(); // null si non payé
            $table->decimal('expected_amount', 15, 2);
            $table->decimal('penalty', 15, 2)->default(0); // pénalité si retard
            $table->decimal('total_due', 15, 2); // expected + penalty
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repayments');
    }
};
