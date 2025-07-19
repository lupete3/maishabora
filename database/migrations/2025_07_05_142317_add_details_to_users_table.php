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
        Schema::table('users', function (Blueprint $table) {
            // Identité officielle
            $table->enum('sexe', ['Masculin', 'Féminin'])->nullable()->after('prenom');
            $table->string('type_piece')->nullable()->after('profession');
            $table->string('numero_piece')->nullable()->after('type_piece');
            $table->date('date_expiration_piece')->nullable()->after('numero_piece');

            // État civil
            $table->enum('etat_civil', ['célibataire', 'marié', 'divorcé', 'veuf'])->nullable()->after('date_expiration_piece');
            $table->integer('nombre_dependants')->nullable()->after('etat_civil');

            // Naissance
            $table->string('lieu_naissance')->nullable()->after('date_naissance');

            // Revenu
            $table->decimal('revenu_mensuel', 15, 2)->nullable()->after('nombre_dependants');
            $table->string('source_revenu')->nullable()->after('revenu_mensuel');
            $table->string('nom_employeur')->nullable()->after('source_revenu');

            // Conjoint
            $table->string('nom_conjoint')->nullable()->after('nom_employeur');
            $table->string('telephone_conjoint')->nullable()->after('nom_conjoint');

            // Référence
            $table->string('nom_reference')->nullable()->after('telephone_conjoint');
            $table->string('telephone_reference')->nullable()->after('nom_reference');
            $table->string('lien_reference')->nullable()->after('telephone_reference');

            // Localisation
            $table->string('province')->nullable()->after('adresse_physique');
            $table->string('ville')->nullable()->after('province');
            $table->string('commune')->nullable()->after('ville');
            $table->string('quartier')->nullable()->after('commune');

            // Média
            $table->string('photo_profil')->nullable()->after('lien_reference');
            $table->string('scan_piece')->nullable()->after('photo_profil');

            // Infos institutionnelles
            $table->date('date_adhesion')->nullable()->after('scan_piece');

            // Divers
            $table->string('nationalite')->nullable()->after('date_adhesion');
            $table->string('niveau_etude')->nullable()->after('nationalite');
            $table->text('remarque')->nullable()->after('niveau_etude');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'type_piece', 'numero_piece', 'date_expiration_piece',
                'etat_civil', 'nombre_dependants',
                'lieu_naissance',
                'revenu_mensuel', 'source_revenu', 'nom_employeur',
                'nom_conjoint', 'telephone_conjoint',
                'nom_reference', 'telephone_reference', 'lien_reference',
                'province', 'ville', 'commune', 'quartier',
                'photo_profil', 'scan_piece',
                'date_adhesion', 'sexe',
                'nationalite', 'niveau_etude', 'remarque'
            ]);
        });
    }
};
