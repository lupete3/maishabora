<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('loan_cashflows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->onDelete('cascade');
            $table->string('type_activite');
            $table->decimal('chiffre_affaires_mensuel_estime', 15, 2)->nullable();
            $table->decimal('camv_ou_achats_mensuels', 15, 2)->nullable();
            $table->decimal('charges_activite_mensuelles', 15, 2)->nullable();
            $table->decimal('autres_revenus_mensuels', 15, 2)->nullable();
            $table->decimal('charges_menage_mensuelles', 15, 2)->nullable();

            $table->decimal('revenu_disponible_mensuel', 15, 2)->nullable();
            $table->decimal('capacite_remboursement_mensuelle', 15, 2)->nullable();

            $table->date('date_calcul')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_cashflows');
    }
};
