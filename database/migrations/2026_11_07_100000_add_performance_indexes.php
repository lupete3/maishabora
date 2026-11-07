<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index de performance pour les requêtes les plus fréquentes (caisse agents, clôtures,
 * historique membres, carnets, crédits, rapports).
 *
 * Additif uniquement : aucune donnée modifiée, aucun index existant supprimé.
 * Chaque index n'est créé que s'il n'existe pas déjà (migration rejouable sans risque).
 */
return new class extends Migration
{
    /** table => [nom de l'index => colonnes] */
    private array $indexes = [
        'transactions' => [
            'transactions_user_id_created_at_index'    => ['user_id', 'created_at'],
            'transactions_account_id_created_at_index' => ['account_id', 'created_at'],
        ],
        'daily_contributions' => [
            'daily_contributions_card_paid_date_index' => ['membership_card_id', 'is_paid', 'contribution_date'],
            'daily_contributions_date_paid_index'      => ['contribution_date', 'is_paid'],
        ],
        'repayments' => [
            'repayments_is_paid_due_date_index' => ['is_paid', 'due_date'],
            'repayments_paid_date_index'        => ['paid_date'],
        ],
        'credits' => [
            'credits_is_paid_created_at_index' => ['is_paid', 'created_at'],
        ],
        'membership_cards' => [
            'membership_cards_created_at_index' => ['created_at'],
        ],
        'accounts' => [
            'accounts_user_currency_type_index' => ['user_id', 'currency', 'type'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach ($indexes as $name => $columns) {
                if (Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($columns, $name) {
                    $blueprint->index($columns, $name);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $indexes) {
            foreach (array_keys($indexes) as $name) {
                if (!Schema::hasIndex($table, $name)) {
                    continue;
                }

                Schema::table($table, function (Blueprint $blueprint) use ($name) {
                    $blueprint->dropIndex($name);
                });
            }
        }
    }
};
