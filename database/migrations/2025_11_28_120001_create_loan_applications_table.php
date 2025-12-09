<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('loan_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('business_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('montant_demande', 15, 2);
            $table->integer('duree_mois');
            $table->unsignedBigInteger('produit_credit_id')->nullable();
            $table->date('date_demande');
            $table->enum('statut', ['en_analyse', 'approuve', 'rejete', 'debourse', 'cloture'])->default('en_analyse');
            $table->foreignId('agent_id')->references('id')->on('users');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('loan_applications');
    }
};
