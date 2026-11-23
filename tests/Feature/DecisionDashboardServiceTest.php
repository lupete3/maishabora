<?php

namespace Tests\Feature;

use App\Services\DecisionAlertsService;
use App\Services\DecisionDashboardService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Cockpit décisionnel : périodes, collecte sans double comptage, devise séparée,
 * encours réel, PAR, liquidité et couverture. Base SQLite en mémoire, « aujourd'hui » = 30/04/2026.
 */
class DecisionDashboardServiceTest extends TestCase
{
    private DecisionDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$v) => max($v), -1);
        DB::connection()->getPdo()->sqliteCreateFunction('LEAST', fn (...$v) => min($v), -1);
        Carbon::setTestNow('2026-04-30 12:00:00');
        Cache::flush();

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('postnom')->nullable();
            $t->string('prenom')->nullable();
            $t->string('role')->default('membre');
            $t->boolean('status')->default(true);
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->string('sexe')->nullable();
            $t->string('client_category')->default('client');
            $t->string('code')->nullable();
            $t->timestamp('last_transaction_at')->nullable();
            $t->timestamps();
        });
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('currency');
            $t->decimal('balance', 15, 2)->default(0);
            $t->string('type')->default('savings');
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('type');
            $t->string('currency')->default('CDF');
            $t->decimal('amount', 15, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('credits', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->unsignedBigInteger('agent_id')->nullable();
            $t->string('currency');
            $t->decimal('amount', 15, 2);
            $t->decimal('frais_credit', 8, 2)->nullable();
            $t->boolean('is_paid')->default(false);
            $t->timestamps();
        });
        Schema::create('repayments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('credit_id');
            $t->date('due_date');
            $t->date('paid_date')->nullable();
            $t->boolean('is_paid')->default(false);
            $t->decimal('expected_amount', 15, 2)->nullable();
            $t->decimal('principal_amount', 15, 2)->nullable();
            $t->decimal('total_due', 15, 2)->nullable();
            $t->decimal('paid_amount', 15, 2)->default(0);
            $t->decimal('paid_principal', 15, 2)->default(0);
            $t->decimal('paid_interest', 15, 2)->default(0);
            $t->decimal('paid_penalty', 15, 2)->default(0);
            $t->timestamps();
        });
        Schema::create('membership_cards', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('card_type')->default('epargne');
            $t->string('currency')->default('CDF');
            $t->decimal('price', 15, 2)->default(0);
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('main_cash_registers', function (Blueprint $t) {
            $t->id();
            $t->string('currency');
            $t->decimal('balance', 15, 2)->default(0);
        });
        Schema::create('agent_accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('currency');
            $t->decimal('balance', 15, 2)->default(0);
        });

        $this->seedDonnees();
        $this->service = app(DecisionDashboardService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function seedDonnees(): void
    {
        $avril = '2026-04-15 10:00:00';
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Agent', 'role' => 'recouvreur', 'agent_id' => null, 'sexe' => null, 'client_category' => 'client', 'created_at' => '2025-01-01', 'last_transaction_at' => null],
            ['id' => 2, 'name' => 'Amina', 'role' => 'membre', 'agent_id' => 1, 'sexe' => 'Féminin', 'client_category' => 'client', 'created_at' => $avril, 'last_transaction_at' => $avril],
            // Paul : aucune opération enregistrée depuis son inscription le 10/03 (51 jours)
            ['id' => 3, 'name' => 'Paul', 'role' => 'membre', 'agent_id' => 1, 'sexe' => 'Masculin', 'client_category' => 'client', 'created_at' => '2026-03-10', 'last_transaction_at' => null],
        ]);
        DB::table('accounts')->insert([
            ['id' => 10, 'user_id' => 2, 'currency' => 'CDF', 'balance' => 50000],
            ['id' => 11, 'user_id' => 3, 'currency' => 'CDF', 'balance' => 30000],
            ['id' => 12, 'user_id' => 3, 'currency' => 'USD', 'balance' => 10],
        ]);
        DB::table('transactions')->insert([
            // Une mise de 10 000 : ligne côté compte membre (user_id = membre) + ligne côté caisse de l'agent
            ['account_id' => 10, 'user_id' => 2, 'type' => 'mise_quotidienne', 'currency' => 'CDF', 'amount' => 10000, 'created_at' => $avril],
            ['account_id' => null, 'user_id' => 1, 'type' => 'encaissement_mise', 'currency' => 'CDF', 'amount' => 10000, 'created_at' => $avril],
            ['account_id' => 11, 'user_id' => 3, 'type' => 'dépôt', 'currency' => 'CDF', 'amount' => 5000, 'created_at' => $avril],
            ['account_id' => 11, 'user_id' => 3, 'type' => 'retrait', 'currency' => 'CDF', 'amount' => 3000, 'created_at' => $avril],
            ['account_id' => 12, 'user_id' => 3, 'type' => 'dépôt', 'currency' => 'USD', 'amount' => 999, 'created_at' => $avril],
            // Mars : base de comparaison
            ['account_id' => 11, 'user_id' => 3, 'type' => 'dépôt', 'currency' => 'CDF', 'amount' => 4000, 'created_at' => '2026-03-12 09:00:00'],
        ]);
        DB::table('credits')->insert([
            ['id' => 1, 'user_id' => 2, 'agent_id' => 1, 'currency' => 'CDF', 'amount' => 100000, 'frais_credit' => null, 'is_paid' => false, 'created_at' => '2026-03-01'],
            ['id' => 2, 'user_id' => 3, 'agent_id' => 1, 'currency' => 'CDF', 'amount' => 40000, 'frais_credit' => 5, 'is_paid' => false, 'created_at' => '2026-04-10'],
            ['id' => 3, 'user_id' => 3, 'agent_id' => 1, 'currency' => 'USD', 'amount' => 500, 'frais_credit' => null, 'is_paid' => false, 'created_at' => '2026-01-10'],
        ]);
        DB::table('repayments')->insert([
            // Crédit 1 : échéance du 20/03 impayée (41 jours de retard), puis une à venir
            ['credit_id' => 1, 'due_date' => '2026-03-20', 'principal_amount' => 50000, 'expected_amount' => 50000, 'total_due' => 50000],
            ['credit_id' => 1, 'due_date' => '2026-05-20', 'principal_amount' => 50000, 'expected_amount' => 50000, 'total_due' => 50000],
            // Crédit 2 : à jour
            ['credit_id' => 2, 'due_date' => '2026-05-10', 'principal_amount' => 40000, 'expected_amount' => 40000, 'total_due' => 40000],
            // Crédit USD en retard : ne doit pas compter en CDF
            ['credit_id' => 3, 'due_date' => '2026-02-10', 'principal_amount' => 500, 'expected_amount' => 500, 'total_due' => 500],
        ]);
        DB::table('main_cash_registers')->insert([['currency' => 'CDF', 'balance' => 20000], ['currency' => 'USD', 'balance' => 100]]);
        DB::table('agent_accounts')->insert([['user_id' => 1, 'currency' => 'CDF', 'balance' => 8000]]);
    }

    public function test_periodes_et_periode_precedente(): void
    {
        $mois = $this->service->periode('month', '2026-04-15');
        $this->assertSame('2026-04-01', $mois['start']->toDateString());
        $this->assertSame('2026-04-30', $mois['end']->toDateString());
        $this->assertSame('2026-03-01', $mois['prev_start']->toDateString());
        $this->assertSame('2026-03-31', $mois['prev_end']->toDateString());

        $semestre = $this->service->periode('semester', '2026-04-15');
        $this->assertSame('2026-01-01', $semestre['start']->toDateString());
        $this->assertSame('2025-07-01', $semestre['prev_start']->toDateString());

        $jour = $this->service->periode('day', '2026-04-15');
        $this->assertSame('2026-04-14', $jour['prev_start']->toDateString());

        // Intervalle de 10 jours : comparé aux 10 jours qui précèdent
        $intervalle = $this->service->periode('custom', null, '2026-04-01', '2026-04-10');
        $this->assertSame('2026-03-22', $intervalle['prev_start']->toDateString());
        $this->assertSame('2026-03-31', $intervalle['prev_end']->toDateString());

        // Type inconnu : mois par défaut ; flèches de navigation
        $this->assertSame('month', $this->service->periode('nimporte', '2026-04-15')['type']);
        $this->assertSame('2026-03-01', $this->service->referenceVoisine($mois, -1));
        $this->assertSame('2026-05-01', $this->service->referenceVoisine($mois, 1));
    }

    public function test_synthese_sans_double_comptage_et_par_devise(): void
    {
        $s = $this->service->synthese($this->service->periode('month', '2026-04-15'), 'CDF');
        $a = $s['activite'];

        // 10 000 (mise, comptée une fois) + 5 000 (dépôt) ; le dépôt USD est exclu
        $this->assertEquals(15000, $a['collecte']['valeur']);
        $this->assertEquals(4000, $a['collecte']['precedent']);
        $this->assertEquals(3000, $a['retraits']['valeur']);
        $this->assertEquals(12000, $a['epargne_nette']['valeur']);
        $this->assertEquals(1, $a['credits_nombre']['valeur']);
        $this->assertEquals(40000, $a['credits_montant']['valeur']);
        $this->assertEquals(2000, $a['produits']['valeur']); // 5 % de frais sur 40 000
        $this->assertEquals(275.0, DecisionDashboardService::variation(15000, 4000));

        $pf = $s['portefeuille'];
        $this->assertEquals(140000, $pf['encours']);            // capital restant dû, pas le montant octroyé
        $this->assertEquals(2, $pf['emprunteurs']);
        $this->assertEquals(1, $pf['en_retard']);                // le crédit USD en retard n'est pas compté
        $this->assertEquals(71.43, $pf['par30']);                // 100 000 / 140 000
        $this->assertEquals(0.0, $pf['par90']);

        $this->assertEquals(80000, $s['epargne']['depots']);
        $this->assertEquals(28000, $s['liquidite']['total']);
        $this->assertEquals(35.0, round($s['liquidite']['couverture'], 1));
        $this->assertEquals(['membres' => 2, 'femmes' => 1, 'part_femmes' => 50.0], $s['portee']);
    }

    public function test_filtre_agent_limite_la_liquidite_a_sa_caisse(): void
    {
        $s = $this->service->synthese($this->service->periode('month', '2026-04-15'), 'CDF', 1);

        $this->assertEquals(0, $s['liquidite']['caisse_centrale']);
        $this->assertEquals(8000, $s['liquidite']['caisses_agents']);
        $this->assertEquals(140000, $s['portefeuille']['encours']);
    }

    public function test_tranches_selon_la_periode(): void
    {
        $mois = $this->service->tranches($this->service->periode('month', '2026-04-15'));
        $this->assertCount(30, $mois);
        $this->assertSame('01/04', $mois[0]['label']);

        $this->assertCount(12, $this->service->tranches($this->service->periode('year', '2026-04-15')));
        $this->assertCount(24, $this->service->tranches($this->service->periode('day', '2026-04-15')));

        // Trimestre découpé en semaines, la première commence au 1er avril (mercredi)
        $trimestre = $this->service->tranches($this->service->periode('quarter', '2026-04-15'));
        $this->assertSame('2026-04-01', $trimestre[0]['start']->toDateString());
        $this->assertSame('2026-04-05', $trimestre[0]['end']->toDateString());
        $this->assertSame('2026-06-30', end($trimestre)['end']->toDateString());
    }

    public function test_graphiques_flux_portefeuille_et_agents(): void
    {
        $g = $this->service->graphiques($this->service->periode('month', '2026-04-15'), 'CDF');

        $this->assertCount(30, $g['labels']);
        $this->assertEquals(15000, array_sum($g['flux']['collecte']));   // même total que la synthèse
        $this->assertEquals(10000 + 5000, $g['flux']['collecte'][14]);    // le 15/04
        $this->assertEquals(3000, array_sum($g['flux']['retraits']));
        $this->assertEquals(40000, $g['flux']['credits'][9]);            // le 10/04

        // Le 01/04 : seul le crédit 1 existe, son échéance du 20/03 n'a pas encore 30 jours de retard
        $this->assertEquals(100000, $g['portefeuille']['encours'][0]);
        $this->assertEquals(0, $g['portefeuille']['par30'][0]);
        // Dernier point = situation actuelle
        $this->assertEquals(140000, end($g['portefeuille']['encours']));
        $this->assertEquals(71.43, end($g['portefeuille']['par30']));

        // Agent : ses écritures côté caisse (la mise), encours et PAR de ses crédits
        $this->assertSame([['nom' => 'Agent', 'collecte' => 10000.0, 'encours' => 140000.0, 'par30' => 71.43]], $g['agents']);
    }

    public function test_encours_reconstitue_tient_compte_des_paiements_et_des_credits_soldes(): void
    {
        // Paiement de 20 000 sur l'échéance du crédit 2, le 20/04
        DB::table('repayments')->where('credit_id', 2)->update(['paid_principal' => 20000, 'paid_date' => '2026-04-20']);
        $this->assertEquals(140000, $this->service->encoursAu(Carbon::parse('2026-04-19'), 'CDF'));
        $this->assertEquals(120000, $this->service->encoursAu(Carbon::parse('2026-04-25'), 'CDF'));

        // Crédit 1 marqué soldé le 28/04 : il ne pèse plus rien ensuite
        DB::table('credits')->where('id', 1)->update(['is_paid' => true, 'updated_at' => '2026-04-28 10:00:00']);
        $this->assertEquals(120000, $this->service->encoursAu(Carbon::parse('2026-04-25'), 'CDF'));
        $this->assertEquals(20000, $this->service->encoursAu(Carbon::parse('2026-04-29'), 'CDF'));
    }

    public function test_credits_a_surveiller_par_anciennete_et_devise(): void
    {
        $c = app(DecisionAlertsService::class)->creditsASurveiller('CDF');

        $this->assertSame(1, $c['nombre']);              // le crédit 2 est à jour, le crédit USD est exclu
        $this->assertEquals(100000, $c['total']);         // capital restant dû du crédit 1
        $this->assertSame(1, $c['tranches'][1]['nombre']); // 41 jours : tranche « 31 à 90 jours »
        $this->assertSame(41, $c['liste'][0]['jours']);
        $this->assertSame(1, $c['liste'][0]['impayees']);
        $this->assertSame('Amina', $c['liste'][0]['client']);
        $this->assertSame(0, $c['a_terme']);

        $this->assertSame(1, app(DecisionAlertsService::class)->creditsASurveiller('USD')['nombre']);
    }

    public function test_clients_inactifs_avec_epargne(): void
    {
        $i = app(DecisionAlertsService::class)->clientsInactifs('CDF');

        // Paul : 51 jours sans opération, 30 000 CDF ; Amina a opéré il y a 15 jours
        $this->assertSame(1, $i['nombre']);
        $this->assertEquals(30000, $i['epargne']);
        $this->assertSame(1, $i['tranches'][0]['nombre']);
        $this->assertSame('Paul', $i['liste'][0]['client']);
        $this->assertSame(51, $i['liste'][0]['jours']);
    }

    public function test_performance_des_agents(): void
    {
        $p = $this->service->periode('month', '2026-04-15');
        $agents = app(DecisionAlertsService::class)->performanceAgents($p, 'CDF');

        $this->assertCount(1, $agents);
        $this->assertSame(2, $agents[0]['clients']);
        $this->assertSame(2, $agents[0]['actifs']);       // mise d'Amina, dépôt de Paul
        $this->assertEquals(10000, $agents[0]['collecte']); // écriture côté caisse de l'agent
        $this->assertEquals(140000, $agents[0]['encours']);
        $this->assertEquals(71.43, $agents[0]['par30']);
        $this->assertSame(1, $agents[0]['retards']);
    }

    public function test_alertes_et_decisions(): void
    {
        $decisions = app(DecisionAlertsService::class);
        $p = $this->service->periode('month', '2026-04-15');
        $calculer = function () use ($decisions, $p) {
            Cache::flush();
            $s = $this->service->synthese($p, 'CDF');

            return $decisions->alertes($s, $p, $decisions->creditsASurveiller('CDF'), $decisions->clientsInactifs('CDF'), $decisions->performanceAgents($p, 'CDF'));
        };

        $alertes = $calculer();
        $titres = array_column($alertes, 'titre');
        $this->assertContains('Portefeuille à risque critique', $titres);       // PAR 30 = 71 %
        $this->assertContains('Encours concentré sur un seul agent', $titres);  // 100 % chez l'agent
        $this->assertContains("Clients inactifs qui ont de l'épargne", $titres);
        $this->assertNotContains('Caisse négative', $titres);
        $this->assertNotContains('Liquidité à surveiller', $titres);           // couverture 35 %
        $this->assertNotContains('Clients sans agent attribué', $titres);
        $this->assertSame('danger', $alertes[0]['niveau']);                    // les plus graves d'abord
        $this->assertNotEmpty($alertes[0]['decision']);

        // Caisse centrale négative : alerte critique et liquidité en tension
        DB::table('main_cash_registers')->where('currency', 'CDF')->update(['balance' => -50000]);
        DB::table('users')->where('id', 3)->update(['agent_id' => null]);
        $titres = array_column($calculer(), 'titre');
        $this->assertContains('Caisse négative', $titres);
        $this->assertContains('Liquidité en tension', $titres);
        $this->assertContains('Clients sans agent attribué', $titres);
    }
}
