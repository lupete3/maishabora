<?php

namespace Tests\Feature;

use App\Livewire\Repports\ClassementTitulaires;
use App\Livewire\Repports\RapportCompteClients;
use App\Models\User;
use App\Support\ClientBalanceRanking;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Rapport /comptes-membres : « ce que nous devons aux clients », catégories de comptes,
 * nature du titulaire et écran de classement. Base SQLite en mémoire.
 */
class ComptesMembresTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        // MySQL dispose de GREATEST(), pas SQLite : équivalent pour les tests
        DB::connection()->getPdo()->sqliteCreateFunction('GREATEST', fn (...$v) => max($v), -1);

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable();
            $table->string('name');
            $table->string('postnom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->default('membre');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        // Tables des permissions d'abord : la migration du 20/11/2026 y ajoute sa permission
        (require database_path('migrations/2025_06_29_094036_create_permission_tables.php'))->up();
        // Colonne client_category + permission classer-titulaires-comptes
        (require database_path('migrations/2026_11_20_100000_add_client_category_to_users_table.php'))->up();

        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('currency');
            $table->decimal('balance', 15, 2)->default(0);
            $table->string('type')->default('savings');
            $table->string('status')->default('Actif');
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('type')->default('dépôt');
            $table->string('currency')->default('CDF');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('from_currency');
            $table->string('to_currency');
            $table->decimal('rate', 18, 8);
            $table->timestamp('applied_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action');
            $table->text('description')->nullable();
            $table->string('device')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });

        Permission::firstOrCreate(['name' => 'classer-titulaires-comptes', 'guard_name' => 'web']);

        $this->admin = User::create(['name' => 'Admin', 'telephone' => '0', 'role' => 'admin']);
        $this->admin->givePermissionTo('classer-titulaires-comptes');

        // Clients réels
        $this->membre('Alice', 'client', [['CDF', 100000], ['USD', 50]], recent: true);
        $this->membre('Bob', 'client', [['CDF', 20000]], recent: false);         // dormant
        $this->membre('Carine', 'client', [['CDF', -10], ['USD', -5]], recent: true); // soldes négatifs
        $this->membre('Denis', 'client', [['CDF', 3000]], recent: true, status: 'Inactif');
        // Comptes internes et de test : exclus de ce que nous devons
        $this->membre('COMPTE RETENU', 'interne', [['CDF', 900000]], recent: true);
        $this->membre('CLIENT TEST', 'test', [['USD', 1000]], recent: true);
        // Client sans aucun solde : masqué par défaut
        $this->membre('Zoe', 'client', [['CDF', 0], ['USD', 0]], recent: true);
        // Compte vide sans opération depuis longtemps : PAS dormant (rien n'est dû)
        $this->membre('Eric', 'client', [['USD', 0]], recent: false);

        $this->actingAs($this->admin);
    }

    private function membre(string $name, string $nature, array $comptes, bool $recent, string $status = 'Actif'): User
    {
        static $n = 0;
        $user = User::create(['name' => $name, 'code' => 'M' . (++$n), 'telephone' => (string) $n, 'role' => 'membre', 'client_category' => $nature]);

        foreach ($comptes as [$currency, $balance]) {
            $account = $user->accounts()->create(['currency' => $currency, 'balance' => $balance, 'status' => $status]);
            DB::table('transactions')->insert([
                'account_id' => $account->id,
                'created_at' => $recent ? now()->subDays(5) : now()->subMonths(18),
                'updated_at' => now(),
            ]);
        }

        return $user;
    }

    public function test_liabilities_count_only_positive_balances_of_real_clients(): void
    {
        $l = ClientBalanceRanking::liabilities();

        // Alice 100 000 + Bob 20 000 (dormant, toujours dû) + Denis 3 000 (inactif, toujours dû)
        $this->assertEquals(123000, $l['CDF']['du']);
        $this->assertSame(3, $l['CDF']['nb_du']);
        $this->assertEquals(50, $l['USD']['du']);
        // Les soldes négatifs sont à part, pas soustraits
        $this->assertEquals(-10, $l['CDF']['negatifs']);
        $this->assertSame(1, $l['CDF']['nb_negatifs']);
        // Comptes internes et de test exclus
        $this->assertEquals(900000, $l['CDF']['exclus']);
        $this->assertEquals(1000, $l['USD']['exclus']);
    }

    public function test_report_shows_only_real_clients_by_default_and_filters_categories(): void
    {
        $noms = fn ($c) => $c->viewData('members')->getCollection()->pluck('name')->sort()->values()->all();

        $component = Livewire::test(RapportCompteClients::class);
        $this->assertSame(['Alice', 'Bob', 'Carine', 'Denis'], $noms($component));
        $component->assertSee('Ce que nous devons aux clients');

        // Bob (20 000 sans opération) est dormant ; Eric (compte vide ancien) ne l'est pas,
        // même sans masquer les membres sans solde
        $this->assertSame(['Bob'], $noms($component->set('category', 'dormants')));
        $this->assertSame(['Bob'], $noms($component->set('masquerSansSolde', false)));
        $component->set('masquerSansSolde', true);
        $this->assertSame(['Carine'], $noms($component->set('category', 'negatifs')));
        $this->assertSame(['Denis'], $noms($component->set('category', 'inactifs')));

        $component->set('category', 'tous')->set('nature', 'interne');
        $this->assertSame(['COMPTE RETENU'], $noms($component));

        $component->set('nature', 'tous');
        $this->assertCount(6, $component->viewData('members')->getCollection());
    }

    public function test_classification_screen_suggests_and_reclassifies_with_a_log(): void
    {
        $suspect = User::where('name', 'COMPTE RETENU')->first();
        $suspect->update(['client_category' => 'client']);

        $component = Livewire::test(ClassementTitulaires::class);
        $this->assertSame(['COMPTE RETENU'], $component->viewData('members')->getCollection()->pluck('name')->all());

        $component->call('changerNature', $suspect->id, 'interne');

        $this->assertSame('interne', $suspect->fresh()->client_category);
        $this->assertDatabaseHas('user_logs', ['action' => 'Classement_titulaire']);

        // Valeur inconnue refusée
        $component->call('changerNature', $suspect->id, 'nimporte')->assertHasErrors('nature');
        $this->assertSame('interne', $suspect->fresh()->client_category);
    }

    public function test_recap_reconciles_net_negatives_and_amount_due(): void
    {
        $recap = Livewire::test(RapportCompteClients::class)->viewData('recap');

        // CDF : 100 000 + 20 000 - 10 + 3 000 + 0 (Zoé) = 122 990 net ; négatifs -10 ; dû 123 000
        $this->assertEquals(122990, $recap['CDF']['net']);
        $this->assertEquals(-10, $recap['CDF']['negatifs']);
        $this->assertEquals(123000, $recap['CDF']['du']);
        $this->assertEquals($recap['CDF']['net'] - $recap['CDF']['negatifs'], $recap['CDF']['du']);
        $this->assertEquals($recap['USD']['net'] - $recap['USD']['negatifs'], $recap['USD']['du']);
    }

    public function test_members_without_balance_are_hidden_by_default(): void
    {
        $noms = fn ($c) => $c->viewData('members')->getCollection()->pluck('name')->all();

        $component = Livewire::test(RapportCompteClients::class);
        $this->assertNotContains('Zoe', $noms($component));

        $component->set('masquerSansSolde', false);
        $this->assertContains('Zoe', $noms($component));
    }

    public function test_french_number_format_and_dash_for_zero(): void
    {
        $this->assertSame('1 234 567,50', ClientBalanceRanking::fr(1234567.5));
        $this->assertSame('—', ClientBalanceRanking::fr(0, true));
        $this->assertSame('0,00', ClientBalanceRanking::fr(0));
        $this->assertSame('-25,00', ClientBalanceRanking::fr(-25, true));
    }

    public function test_pdf_has_recap_lot_subtotal_and_negative_annex(): void
    {
        \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->andReturnUsing(function ($view, $data) {
            $html = view($view, $data + ['company' => null])->render();

            $this->assertStringContainsString('Dû aux clients', $html);
            $this->assertStringContainsString('123 000,00', $html);        // dû CDF, format français
            $this->assertStringContainsString('Sous-total du lot', $html);
            $this->assertStringContainsString('Annexe — Soldes négatifs à régulariser', $html);
            $this->assertStringContainsString('Carine', $html);           // compte négatif listé en annexe
            $this->assertStringNotContainsString('<td>Zoe</td>', $html);  // membre sans solde masqué

            return \Mockery::mock(\Barryvdh\DomPDF\PDF::class)
                ->shouldReceive('setPaper')->andReturnSelf()
                ->shouldReceive('stream')->andReturn(response('ok'))
                ->getMock();
        });

        app(\App\Http\Controllers\ClientStatReportController::class)
            ->compteClientsPdf(\Illuminate\Http\Request::create('/x', 'GET', ['lotSize' => 100, 'lot' => 1]));
    }

    public function test_classification_screen_requires_the_permission(): void
    {
        $this->actingAs(User::create(['name' => 'Sans droit', 'telephone' => '99', 'role' => 'caissier']));

        Livewire::test(ClassementTitulaires::class)->assertForbidden();
    }
}
