<?php

namespace Tests\Feature;

use App\Services\CardRenewalService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Carnets non renouvelés : retrait, renouvellement (même anticipé), autre carnet actif,
 * un client par ligne, jours depuis le retrait, filtres et taux par agent.
 * Base SQLite en mémoire, « aujourd'hui » = 31/03/2026.
 */
class CardRenewalServiceTest extends TestCase
{
    private CardRenewalService $service;

    private array $mars;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Carbon::setTestNow('2026-03-31 12:00:00');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('name');
            $t->string('postnom')->nullable();
            $t->string('prenom')->nullable();
            $t->string('telephone')->nullable();
            $t->string('role')->default('membre');
            $t->timestamps();
        });
        Schema::create('membership_cards', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->unsignedBigInteger('member_id');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('currency')->default('CDF');
            $t->string('card_type')->default('epargne');
            $t->decimal('subscription_amount', 15, 2)->default(1000);
            $t->boolean('is_active')->default(true);
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('daily_contributions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('membership_card_id');
            $t->decimal('amount', 15, 2);
            $t->boolean('is_paid')->default(false);
            $t->timestamps();
        });

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Agent A', 'role' => 'recouvreur', 'code' => null, 'telephone' => null],
            ['id' => 2, 'name' => 'Agent B', 'role' => 'recouvreur', 'code' => null, 'telephone' => null],
            ['id' => 10, 'name' => 'Alice', 'role' => 'membre', 'code' => 'C10', 'telephone' => '+243810000010'],
            ['id' => 11, 'name' => 'Bruno', 'role' => 'membre', 'code' => 'C11', 'telephone' => '+243810000011'],
            ['id' => 12, 'name' => 'Chantal', 'role' => 'membre', 'code' => 'C12', 'telephone' => null],
            ['id' => 13, 'name' => 'David', 'role' => 'membre', 'code' => 'C13', 'telephone' => null],
            ['id' => 14, 'name' => 'Esther', 'role' => 'membre', 'code' => 'C14', 'telephone' => null],
        ]);

        $carnet = fn (int $id, int $membre, int $agent, string $pris, ?string $retire, array $extra = []) => array_merge([
            'id' => $id, 'code' => "K{$id}", 'member_id' => $membre, 'user_id' => $agent, 'currency' => 'CDF',
            'card_type' => 'epargne', 'subscription_amount' => 1000, 'is_active' => $retire === null,
            'cancelled_at' => null, 'created_at' => $pris, 'updated_at' => $retire ?? $pris,
        ], $extra);

        DB::table('membership_cards')->insert([
            // Alice : retire le 05/03, ne reprend rien → à relancer (26 jours)
            $carnet(1, 10, 1, '2026-02-01', '2026-03-05 10:00:00'),
            // Bruno : retire le 10/03 puis reprend un carnet le 12/03 → renouvelé
            $carnet(2, 11, 1, '2026-02-05', '2026-03-10 10:00:00'),
            $carnet(3, 11, 1, '2026-03-12', null),
            // Chantal : achète son nouveau carnet le 20/03 AVANT de retirer l'ancien le 21/03 → renouvelé
            $carnet(4, 12, 2, '2026-02-10', '2026-03-21 09:00:00'),
            $carnet(5, 12, 2, '2026-03-20', null),
            // David : retire le 28/03, a encore un carnet plus ancien actif → à relancer, « autre carnet actif »
            $carnet(6, 13, 2, '2026-01-01', null),
            $carnet(7, 13, 2, '2026-02-15', '2026-03-28 15:00:00'),
            // Esther : deux carnets tenus en parallèle, retirés en mars. Le 1er compte comme renouvelé
            // (elle détenait déjà le 2e, plus récent) ; le 2e ne l'est pas → une seule ligne à relancer
            $carnet(8, 14, 1, '2026-01-10', '2026-03-02 08:00:00'),
            $carnet(9, 14, 1, '2026-02-01', '2026-03-15 08:00:00'),
            // Hors périmètre : retrait en février, carnet USD, carnet annulé, carnet simple
            $carnet(10, 10, 1, '2026-01-01', '2026-02-20 10:00:00'),
            $carnet(11, 11, 2, '2026-02-01', '2026-03-08 10:00:00', ['currency' => 'USD']),
            $carnet(12, 12, 2, '2026-02-01', '2026-03-09 10:00:00', ['cancelled_at' => '2026-03-09 11:00:00']),
            $carnet(13, 13, 1, '2026-02-01', '2026-03-11 10:00:00', ['card_type' => 'simple']),
        ]);
        DB::table('daily_contributions')->insert([
            ['membership_card_id' => 1, 'amount' => 1000, 'is_paid' => true],
            ['membership_card_id' => 1, 'amount' => 1000, 'is_paid' => true],
            ['membership_card_id' => 1, 'amount' => 1000, 'is_paid' => false],
        ]);

        $this->service = app(CardRenewalService::class);
        $this->mars = ['start' => Carbon::parse('2026-03-01')->startOfDay(), 'end' => Carbon::parse('2026-03-31')->endOfDay()];
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_retraits_de_la_periode_et_renouvellement(): void
    {
        // Épargne CDF + USD (sans filtre devise), hors annulé, hors simple, hors février
        $retraits = $this->service->retraits($this->mars, ['devise' => 'CDF']);
        $parCarnet = $retraits->keyBy('id');

        $this->assertEqualsCanonicalizing([1, 2, 4, 7, 8, 9], $retraits->pluck('id')->all());
        $this->assertFalse($parCarnet[1]->renouvele);
        $this->assertTrue($parCarnet[2]->renouvele);   // nouveau carnet après le retrait
        $this->assertTrue($parCarnet[4]->renouvele);   // nouveau carnet pris la veille du retrait
        $this->assertFalse($parCarnet[7]->renouvele);  // seul un carnet plus ancien reste actif
        $this->assertTrue($parCarnet[7]->autre_actif);
        $this->assertEquals(2000, $parCarnet[1]->mises); // seules les mises payées
        $this->assertSame(26, $parCarnet[1]->jours);
    }

    public function test_un_client_par_ligne_tries_par_retard(): void
    {
        $retraits = $this->service->retraits($this->mars, ['devise' => 'CDF']);
        $clients = $this->service->aRelancer($retraits);

        // Alice (26 j), Esther (16 j, son retrait le plus récent), David (3 j)
        $this->assertSame([10, 14, 13], $clients->pluck('member_id')->all());
        $this->assertSame(9, $clients[1]->id);
        $this->assertSame([26, 16, 3], $clients->pluck('jours')->all());

        $this->assertSame([10, 14], $this->service->aRelancer($retraits, 7)->pluck('member_id')->all());
        $this->assertSame([10, 14], $this->service->aRelancer($retraits, 0, true)->pluck('member_id')->all());
    }

    public function test_resume_et_taux_par_agent(): void
    {
        $retraits = $this->service->retraits($this->mars, ['devise' => 'CDF']);
        $clients = $this->service->aRelancer($retraits);
        $resume = $this->service->resume($retraits, $clients);

        $this->assertSame(6, $resume['retraits']);
        $this->assertSame(3, $resume['renouveles']);   // carnets 2, 4 et 8
        $this->assertSame(3, $resume['non_renouveles']);
        $this->assertEquals(50.0, $resume['taux']);
        $this->assertSame(3, $resume['clients']);
        $this->assertSame(['Moins de 7 jours' => 1, '7 à 30 jours' => 2, 'Plus de 30 jours' => 0], $resume['tranches']);

        $agents = collect($this->service->parAgent($retraits, $clients))->keyBy('agent_id');
        // Agent A : carnets 1, 2, 8, 9 → 2 renouvelés sur 4 ; Agent B : carnets 4, 7 → 1 sur 2
        $this->assertEquals(50.0, $agents[1]['taux']);
        $this->assertSame(2, $agents[1]['a_relancer']);
        $this->assertEquals(50.0, $agents[2]['taux']);
        $this->assertSame(1, $agents[2]['a_relancer']);

        // Taux le plus faible d'abord : le retrait de Bruno n'est plus renouvelé si son nouveau carnet est annulé
        DB::table('membership_cards')->where('id', 3)->update(['cancelled_at' => now()]);
        $retraits = $this->service->retraits($this->mars, ['devise' => 'CDF']);
        $classement = $this->service->parAgent($retraits, $this->service->aRelancer($retraits));
        $this->assertSame(1, $classement[0]['agent_id']);
        $this->assertEquals(25.0, $classement[0]['taux']);
    }

    public function test_filtres_agent_type_et_devise(): void
    {
        $this->assertEqualsCanonicalizing([4, 7], $this->service->retraits($this->mars, ['agent_id' => 2, 'devise' => 'CDF'])->pluck('id')->all());
        $this->assertSame([11], $this->service->retraits($this->mars, ['devise' => 'USD'])->pluck('id')->all());
        $this->assertSame([13], $this->service->retraits($this->mars, ['type' => 'simple'])->pluck('id')->all());
        $this->assertCount(8, $this->service->retraits($this->mars, ['type' => 'tous']));
    }
}
