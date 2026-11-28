<?php

namespace Tests\Feature;

use App\Livewire\Members\MemberDetails;
use App\Services\FirstMiseRetentionAudit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Contrôle et régularisation des retenus de première mise manquants :
 * classement des carnets, simulation par défaut, seuls les carnets retirés avec retenu pris
 * sont marqués, sans toucher updated_at ni les carnets actifs. Base SQLite en mémoire.
 */
class FirstMiseRetentionAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('code')->nullable();
            $t->string('name');
            $t->string('postnom')->nullable();
            $t->timestamps();
        });
        Schema::create('membership_cards', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->unsignedBigInteger('member_id');
            $t->string('currency')->default('CDF');
            $t->string('card_type')->default('epargne');
            $t->decimal('subscription_amount', 15, 2)->default(1000);
            $t->boolean('is_active')->default(true);
            $t->boolean('first_mise_retained')->default(false);
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('daily_contributions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('membership_card_id');
            $t->date('contribution_date');
            $t->decimal('amount', 15, 2);
            $t->boolean('is_paid')->default(false);
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('account_id')->nullable();
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('type');
            $t->decimal('amount', 15, 2)->default(0);
            $t->text('description')->nullable();
            $t->timestamps();
        });
        Schema::create('system_logs', function (Blueprint $t) {
            $t->id();
            $t->string('level');
            $t->text('message');
            $t->text('trace')->nullable();
            $t->timestamps();
        });

        DB::table('users')->insert(['id' => 10, 'code' => 'C10', 'name' => 'Alice']);

        $carnet = fn (int $id, bool $actif, bool $retenu = false, string $versement = '2026-10-20 10:00:00') => [
            'id' => $id, 'code' => "K{$id}", 'member_id' => 10, 'currency' => 'CDF', 'card_type' => 'epargne',
            'subscription_amount' => 1000, 'is_active' => $actif, 'first_mise_retained' => $retenu,
            'cancelled_at' => null, 'created_at' => '2026-10-01', 'updated_at' => $actif ? $versement : '2026-11-10 09:00:00',
        ];
        DB::table('membership_cards')->insert([
            $carnet(1, true),                           // actif sans retenu
            $carnet(2, false),                          // retiré, retenu pris au retrait
            $carnet(3, false),                          // retiré, retenu NON pris
            $carnet(4, false),                          // inactif, aucune opération de retrait
            $carnet(5, false, true),                    // déjà correct
            $carnet(6, false, false, '2026-09-01 10:00:00'), // avant la période contrôlée
        ]);
        foreach ([1, 2, 3, 4, 5] as $id) {
            DB::table('daily_contributions')->insert(['membership_card_id' => $id, 'contribution_date' => '2026-10-20', 'amount' => 1000, 'is_paid' => true, 'updated_at' => '2026-10-20 10:00:00']);
        }
        DB::table('daily_contributions')->insert(['membership_card_id' => 6, 'contribution_date' => '2026-09-01', 'amount' => 1000, 'is_paid' => true, 'updated_at' => '2026-09-01 10:00:00']);

        $retrait = fn (int $id) => ['account_id' => 99, 'user_id' => 10, 'type' => 'retrait_carte_adhesion', 'amount' => 0, 'description' => "Retrait carnet #{$id} Client: C10 Alice"];
        DB::table('transactions')->insert([
            $retrait(2),
            ['account_id' => null, 'user_id' => MemberDetails::RETAINED_ACCOUNT_USER_ID, 'type' => 'depot', 'amount' => 1000, 'description' => 'Entree Retenu de la carte #2 du compte C10 Client: Alice par X'],
            // Retenu d'un autre carnet (#30) : ne doit pas être pris pour celui du carnet #3
            ['account_id' => null, 'user_id' => MemberDetails::RETAINED_ACCOUNT_USER_ID, 'type' => 'depot', 'amount' => 1000, 'description' => 'Entree Retenu de la carte #30 du compte C10'],
            $retrait(3),
        ]);
    }

    public function test_classement_des_carnets(): void
    {
        $statuts = app(FirstMiseRetentionAudit::class)->carnets('2026-10-12')->pluck('statut', 'id')->all();

        $this->assertSame([
            1 => FirstMiseRetentionAudit::ACTIF,
            2 => FirstMiseRetentionAudit::RETIRE_RETENU_PRIS,
            3 => FirstMiseRetentionAudit::RETIRE_SANS_RETENU,
            4 => FirstMiseRetentionAudit::INACTIF_SANS_RETRAIT,
        ], $statuts); // #5 déjà correct, #6 avant la période
    }

    public function test_simulation_par_defaut_n_ecrit_rien(): void
    {
        Artisan::call('carnets:regulariser-retenus');

        $this->assertStringContainsString('SIMULATION', Artisan::output());
        $this->assertSame(1, DB::table('membership_cards')->where('first_mise_retained', true)->count()); // seulement #5
        $this->assertSame(0, DB::table('system_logs')->count());
    }

    public function test_appliquer_ne_marque_que_les_carnets_retires_avec_retenu_pris(): void
    {
        Artisan::call('carnets:regulariser-retenus', ['--appliquer' => true]);

        $marques = DB::table('membership_cards')->where('first_mise_retained', true)->orderBy('id')->pluck('id')->all();
        $this->assertSame([2, 5], $marques);
        // La date du retrait (updated_at) n'est pas modifiée
        $this->assertSame('2026-11-10 09:00:00', DB::table('membership_cards')->where('id', 2)->value('updated_at'));
        $this->assertStringContainsString('#2', DB::table('system_logs')->value('message'));

        // Deuxième passage : plus rien à faire
        Artisan::call('carnets:regulariser-retenus', ['--appliquer' => true]);
        $this->assertStringContainsString('Aucun carnet à régulariser', Artisan::output());
    }

    public function test_controle_affiche_les_groupes(): void
    {
        Artisan::call('carnets:controle-retenus');
        $sortie = Artisan::output();

        $this->assertStringContainsString('4 carnet(s) payé(s) sans retenu', $sortie);
        $this->assertStringContainsString('Actif : retenu prélevé automatiquement au retrait (rien à faire) : 1 carnet(s)', $sortie);
        $this->assertStringContainsString('Retiré : retenu pris au retrait (marqueur à corriger) : 1 carnet(s)', $sortie);
    }
}
