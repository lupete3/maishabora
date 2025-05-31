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
        Schema::create('contribution_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contribution_book_id')->constrained('contribution_books');
            $table->integer('numero_ligne'); // 1 à 30
            $table->date('date_contribution')->useCurrent();
            $table->decimal('montant', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contribution_lines');
    }
};
