<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('loan_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_application_id')->constrained()->onDelete('cascade');
            $table->integer('note_caractere')->nullable();
            $table->integer('note_capacite')->nullable();
            $table->integer('note_capital')->nullable();
            $table->integer('note_caution')->nullable();
            $table->integer('note_caracteristiques_financieres')->nullable();
            $table->text('commentaire_global')->nullable();
            $table->enum('decision_finale', ['approuve', 'rejete', 'a_revoir'])->nullable();
            $table->foreignId('user_id')->references('id')->on('users'); // comité crédit
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_decisions');
    }
};
