<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * PRODUCTION-SAFE: Cette migration ajoute uniquement de nouvelles colonnes
     * sans modifier ou supprimer les données existantes.
     */
    public function up(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            // Hiérarchie des comptes (permet structure classe -> sous-classe -> compte détaillé)
            $table->foreignId('parent_id')->nullable()->after('id')->constrained('comptes')->nullOnDelete();

            // Niveau hiérarchique (1 = classe, 2 = sous-classe, 3 = compte détaillé)
            $table->tinyInteger('level')->default(3)->after('parent_id');

            // Sous-classe pour organisation (ex: 57 pour Caisse, 571 pour détail)
            $table->string('sous_classe', 10)->nullable()->after('code');

            // Devise spécifique ou multi-devises
            $table->enum('currency_type', ['multi', 'USD', 'CDF'])->default('multi')->after('type');

            // Compte actif ou archivé
            $table->boolean('is_active')->default(true)->after('currency_type');

            // Description détaillée du compte
            $table->text('description')->nullable()->after('intitule');

            // Index pour optimisation des requêtes hiérarchiques
            $table->index('parent_id');
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('comptes', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropIndex(['parent_id']);
            $table->dropIndex(['type', 'is_active']);
            $table->dropColumn([
                'parent_id',
                'level',
                'sous_classe',
                'currency_type',
                'is_active',
                'description'
            ]);
        });
    }
};
