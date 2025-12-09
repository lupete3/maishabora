<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('loan_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->onDelete('cascade');
            $table->decimal('cash', 15, 2)->nullable();
            $table->decimal('creances', 15, 2)->nullable();
            $table->decimal('stock', 15, 2)->nullable();
            $table->decimal('actifs_immobilises', 15, 2)->nullable();

            $table->decimal('dettes_formelles_ct', 15, 2)->nullable();
            $table->decimal('dettes_formelles_mt', 15, 2)->nullable();
            $table->decimal('dettes_formelles_lt', 15, 2)->nullable();

            $table->decimal('dettes_informelles_ct', 15, 2)->nullable();
            $table->decimal('dettes_informelles_mt', 15, 2)->nullable();
            $table->decimal('dettes_informelles_lt', 15, 2)->nullable();

            $table->decimal('fonds_propres', 15, 2)->nullable();

            $table->decimal('total_actif', 15, 2)->nullable();
            $table->decimal('total_dettes', 15, 2)->nullable();
            $table->decimal('total_passif', 15, 2)->nullable();

            $table->date('date_calcul')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_balances');
    }
};
