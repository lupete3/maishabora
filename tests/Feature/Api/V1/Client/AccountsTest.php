<?php

namespace Tests\Feature\Api\V1\Client;

use Tests\Concerns\BuildsClientApiSchema;
use Tests\TestCase;

/**
 * Tests des soldes et de l'historique des transactions.
 */
class AccountsTest extends TestCase
{
    use BuildsClientApiSchema;

    private const ACCOUNTS = '/api/v1/client/accounts';

    private const TRANSACTIONS = '/api/v1/client/transactions';

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClientApiSchema();
    }

    // ---------------------------------------------------------------- Comptes

    public function test_le_membre_voit_ses_comptes_ouverts_avec_les_soldes_en_texte(): void
    {
        $member = $this->member();
        $this->account($member, 'USD', 'savings', 120.5);
        $this->account($member, 'CDF', 'current', 15000);
        $this->account($member, 'USD', 'current', 42);
        $this->account($member, 'CDF', 'savings', 999, status: 'Inactif'); // non affiché

        $this->withToken($this->tokenFor($member))
            ->getJson(self::ACCOUNTS)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('balance_hidden', false)
            // courant avant épargne, CDF avant USD
            ->assertJsonPath('data.0.type', 'current')
            ->assertJsonPath('data.0.currency', 'CDF')
            ->assertJsonPath('data.0.balance', '15000.00')
            ->assertJsonPath('data.0.type_label', 'Compte courant')
            ->assertJsonPath('data.1.currency', 'USD')
            ->assertJsonPath('data.1.balance', '42.00')
            ->assertJsonPath('data.2.type_label', 'Compte d\'épargne')
            ->assertJsonPath('data.2.balance', '120.50');
    }

    public function test_le_membre_ne_voit_jamais_les_comptes_d_un_autre(): void
    {
        $member = $this->member();
        $other = $this->member();
        $this->account($member, 'CDF', 'current', 100);
        $this->account($other, 'CDF', 'current', 5000000);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::ACCOUNTS)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.balance', '100.00');
    }

    public function test_les_soldes_masques_par_l_agence_ne_sont_pas_envoyes(): void
    {
        $member = $this->member(['visible_account' => false]);
        $account = $this->account($member, 'CDF', 'current', 15000);
        $this->transaction($account, 'depot', 5000, 15000);

        $token = $this->tokenFor($member);

        $this->withToken($token)
            ->getJson(self::ACCOUNTS)
            ->assertOk()
            ->assertJsonPath('balance_hidden', true)
            ->assertJsonPath('data.0.balance', null)
            ->assertJsonPath('data.0.balance_hidden', true);

        // L'opération reste visible, mais pas le solde après opération
        $this->freshRequest()->withToken($token)
            ->getJson(self::TRANSACTIONS)
            ->assertOk()
            ->assertJsonPath('data.0.amount', '5000.00')
            ->assertJsonPath('data.0.balance_after', null);
    }

    public function test_un_jeton_limite_ne_donne_pas_acces_aux_soldes(): void
    {
        $member = $this->member(['must_change_password' => true]);
        $token = $member->createToken('Tecno', ['password:change'])->plainTextToken;

        $this->withToken($token)->getJson(self::ACCOUNTS)
            ->assertStatus(403)
            ->assertJsonPath('code', 'password_change_required');

        $this->freshRequest()->withToken($token)->getJson(self::TRANSACTIONS)->assertStatus(403);
    }

    // ----------------------------------------------------------- Historique

    public function test_l_historique_regroupe_les_comptes_du_plus_recent_au_plus_ancien(): void
    {
        $member = $this->member();
        $cdf = $this->account($member, 'CDF');
        $usd = $this->account($member, 'USD');

        $this->transaction($cdf, 'depot', 1000, 1000, ['created_at' => now()->subDays(3)]);
        $this->transaction($usd, 'depot', 20, 20, ['created_at' => now()->subDays(1)]);
        $this->transaction($cdf, 'retrait', 400, 600, ['created_at' => now()->subDays(2)]);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS)
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.currency', 'USD')
            ->assertJsonPath('data.1.type', 'retrait')
            ->assertJsonPath('data.1.direction', 'debit')
            ->assertJsonPath('data.1.label', 'Retrait')
            ->assertJsonPath('data.1.balance_after', '600.00')
            ->assertJsonPath('data.2.direction', 'credit');
    }

    public function test_l_historique_n_inclut_ni_les_autres_membres_ni_les_operations_d_agent(): void
    {
        $member = $this->member();
        $other = $this->member();
        $mine = $this->account($member);
        $theirs = $this->account($other);

        $this->transaction($mine, 'depot', 100, 100);
        $this->transaction($theirs, 'depot', 777, 777);
        // Opération faite PAR ce membre comme user_id, mais sur une caisse d'agent
        $this->transaction($mine, 'encaissement_mise', 50, 50, ['account_id' => null, 'agent_account_id' => 3, 'user_id' => $member->id]);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.amount', '100.00');
    }

    public function test_filtrer_sur_un_compte(): void
    {
        $member = $this->member();
        $cdf = $this->account($member, 'CDF');
        $usd = $this->account($member, 'USD');
        $this->transaction($cdf, 'depot', 1000, 1000);
        $this->transaction($usd, 'depot', 20, 20);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS . '?account_id=' . $usd->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.account_id', $usd->id);
    }

    public function test_le_compte_d_un_autre_membre_ou_ferme_repond_404(): void
    {
        $member = $this->member();
        $closed = $this->account($member, 'CDF', status: 'Inactif');
        $theirs = $this->account($this->member(), 'CDF', 'current', 5000);
        $this->transaction($theirs, 'depot', 5000, 5000);

        $token = $this->tokenFor($member);

        foreach ([$theirs->id, $closed->id, 999999] as $id) {
            $this->freshRequest()->withToken($token)
                ->getJson(self::TRANSACTIONS . '?account_id=' . $id)
                ->assertStatus(404)
                ->assertJsonPath('code', 'account_not_found')
                ->assertJsonMissingPath('data');
        }
    }

    public function test_le_sens_et_le_libelle_de_chaque_type(): void
    {
        $member = $this->member();
        $acc = $this->account($member);

        $cases = [
            ['dépôt', 'credit', 'Dépôt'],
            ['depot', 'credit', 'Dépôt'],
            ['mise_quotidienne', 'credit', 'Mise quotidienne'],
            ['transfert_entrant', 'credit', 'Transfert reçu'],
            ['retrait', 'debit', 'Retrait'],
            ['remboursement_de_credit', 'debit', 'Remboursement de crédit'],
            ['conversion_sortie_client', 'debit', 'Conversion de devise (sortie)'],
            // Types relevés en production le 2026-11-16
            ['penalite_de_credit', 'debit', 'Pénalité de retard sur crédit'],
            ['virement_caisse_entrant', 'credit', 'Virement reçu'],
            ['mise_quotidienne_multiple', 'credit', 'Mise quotidienne'],
            ['frais_adhesion', null, 'Frais d\'adhésion (payés au guichet)'],
            ['regularisation', null, 'Régularisation'],
            ['type_inconnu', null, 'Type inconnu'],
        ];

        foreach ($cases as $i => [$type]) {
            $this->transaction($acc, $type, 10, 10, ['created_at' => now()->subMinutes(100 - $i)]);
        }

        $data = $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS)
            ->assertOk()
            ->json('data');

        // Réponse du plus récent au plus ancien : on inverse pour suivre $cases
        foreach (array_reverse($data) as $i => $row) {
            [$type, $direction, $label] = $cases[$i];
            $this->assertSame($direction, $row['direction'], "Sens de {$type}");
            $this->assertSame($label, $row['label'], "Libellé de {$type}");
        }
    }

    public function test_les_ecritures_de_caisse_internes_ne_sont_jamais_montrees(): void
    {
        $member = $this->member();
        $acc = $this->account($member);
        $this->transaction($acc, 'octroi_de_credit', 500, 500);

        // Anciennes écritures : compte membre + solde de la caisse principale
        $this->transaction($acc, 'Entrée de fonds', 1000, 98765432.10);
        $this->transaction($acc, 'Sortie de fonds', 1000, 98764432.10);
        $this->transaction($acc, 'octroi_de_credit_client', 500, 98763932.10);

        $response = $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'octroi_de_credit')
            ->assertJsonPath('meta.total', 1);

        $this->assertStringNotContainsString('98765432', $response->getContent());
    }

    public function test_une_correction_de_solde_prend_le_sens_de_sa_description(): void
    {
        $member = $this->member();
        $acc = $this->account($member);
        $this->transaction($acc, 'rectification_solde', 500, 1500, [
            'description' => 'RECTIFICATION MANUELLE DU SOLDE: + 500 CDF. Raison: erreur',
            'created_at' => now()->subMinute(),
        ]);
        $this->transaction($acc, 'rectification_solde', 200, 1300, [
            'description' => 'RECTIFICATION MANUELLE DU SOLDE: - 200 CDF. Raison: doublon',
        ]);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS)
            ->assertOk()
            ->assertJsonPath('data.0.direction', 'debit')
            ->assertJsonPath('data.0.label', 'Correction de solde')
            ->assertJsonPath('data.1.direction', 'credit');
    }

    public function test_filtrer_par_sens(): void
    {
        $member = $this->member();
        $acc = $this->account($member);
        $this->transaction($acc, 'dépôt', 1000, 1000);
        $this->transaction($acc, 'retrait', 300, 700);
        $this->transaction($acc, 'rectification_solde', 50, 650, ['description' => 'RECTIFICATION MANUELLE DU SOLDE: - 50 CDF.']);
        $this->transaction($acc, 'rectification_solde', 80, 730, ['description' => 'RECTIFICATION MANUELLE DU SOLDE: + 80 CDF.']);

        $token = $this->tokenFor($member);

        $debits = $this->withToken($token)->getJson(self::TRANSACTIONS . '?direction=debit')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(['300.00', '50.00'], array_column($debits, 'amount'));

        $credits = $this->freshRequest()->withToken($token)->getJson(self::TRANSACTIONS . '?direction=credit')->assertOk()->json('data');
        $this->assertEqualsCanonicalizing(['1000.00', '80.00'], array_column($credits, 'amount'));
    }

    public function test_filtrer_par_dates(): void
    {
        $member = $this->member();
        $acc = $this->account($member);
        $this->transaction($acc, 'depot', 1, 1, ['created_at' => '2026-09-30 23:59:00']);
        $this->transaction($acc, 'depot', 2, 3, ['created_at' => '2026-10-01 00:00:00']);
        $this->transaction($acc, 'depot', 3, 6, ['created_at' => '2026-10-31 23:59:59']);
        $this->transaction($acc, 'depot', 4, 10, ['created_at' => '2026-11-01 00:00:01']);

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS . '?from=2026-10-01&to=2026-10-31')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.amount', '3.00')
            ->assertJsonPath('data.1.amount', '2.00');
    }

    public function test_les_filtres_invalides_sont_refuses(): void
    {
        $member = $this->member();

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS . '?from=2026-10-31&to=2026-10-01&direction=tout&per_page=500')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['to', 'direction', 'per_page']);
    }

    public function test_l_historique_est_pagine(): void
    {
        $member = $this->member();
        $acc = $this->account($member);
        for ($i = 1; $i <= 5; $i++) {
            $this->transaction($acc, 'depot', $i, $i, ['created_at' => now()->subMinutes(10 - $i)]);
        }

        $this->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS . '?per_page=2&page=2&direction=credit')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.amount', '3.00')
            ->assertJsonPath('meta.total', 5)
            ->assertJsonPath('meta.last_page', 3)
            ->assertJsonPath('meta.current_page', 2);

        // Les liens de pagination gardent les filtres
        $next = $this->freshRequest()->withToken($this->tokenFor($member))
            ->getJson(self::TRANSACTIONS . '?per_page=2&direction=credit')
            ->json('links.next');
        $this->assertStringContainsString('direction=credit', $next);
        $this->assertStringContainsString('per_page=2', $next);
    }
}
