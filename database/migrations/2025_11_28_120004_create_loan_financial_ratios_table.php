<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('loan_financial_ratios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->onDelete('cascade');
            $table->decimal('fonds_roulement', 15, 2)->nullable();
            $table->decimal('independance_financiere', 15, 3)->nullable();
            $table->decimal('liquidite_generale', 15, 3)->nullable();
            $table->decimal('rotation_stock', 15, 3)->nullable();
            $table->decimal('creances_sur_ventes', 15, 3)->nullable();
            $table->decimal('profitabilite_nette', 15, 3)->nullable();
            $table->decimal('solvabilite', 15, 3)->nullable();

            $table->decimal('ventes_mensuelles', 15, 2)->nullable();
            $table->decimal('benefice_net_mensuel', 15, 2)->nullable();

            $table->date('date_calcul')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_financial_ratios');
    }
};
