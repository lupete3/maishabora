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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->string('type_operation'); // Adhésion / Contribution / Retrait
            $table->decimal('montant', 10, 2);
            $table->string('reference_type')->nullable(); // Nom du modèle source
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de l'élément source
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
