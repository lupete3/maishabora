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
        Schema::create('ecarts_caisse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cloture_id')->constrained('clotures')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            $table->enum('type', ['surplus', 'deficit']); // surplus = plus physique que logique, deficit = moins
            $table->enum('currency', ['USD', 'CDF']);
            $table->decimal('amount', 15, 2); // valeur absolue de l'écart

            $table->text('description')->nullable(); // note initiale de l'agent
            $table->enum('status', ['ouvert', 'en_cours', 'cloture'])->default('ouvert');

            // Résolution
            $table->text('resolution_note')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecarts_caisse');
    }
};
