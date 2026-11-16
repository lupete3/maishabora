<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute les colonnes de l'accès mobile (migration additive :
     * aucune donnée existante n'est modifiée).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Date à laquelle l'agence a activé l'accès mobile du membre.
            // NULL = le membre ne peut pas se connecter à l'application mobile.
            $table->timestamp('mobile_activated_at')->nullable();

            // Vrai tant que le membre utilise un mot de passe temporaire :
            // l'application l'oblige alors à en choisir un nouveau.
            $table->boolean('must_change_password')->default(false);
        });
    }

    /**
     * Ne supprime que ce que cette migration a créé.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['mobile_activated_at', 'must_change_password']);
        });
    }
};
