<?php

namespace Tests\Feature;

use App\Livewire\Agent\AgentDashboard;
use App\Models\AgentAccount;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Caisse des agents : historique paginé et actions de rectification.
 * Base SQLite en mémoire : aucune donnée réelle n'est utilisée.
 */
class AgentDashboardTest extends TestCase
{
    private User $admin;

    private User $agent;

    private AgentAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('postnom')->nullable();
            $table->string('prenom')->nullable();
            $table->string('telephone')->nullable();
            $table->string('email')->nullable();
            $table->string('role')->default('admin');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('agent_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('currency');
            $table->decimal('balance', 15, 2)->default(0);
            $table->boolean('is_visible_dashboard')->default(true);
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('membership_card_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('agent_account_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('credit_id')->nullable();
            $table->unsignedBigInteger('disbursement_type_id')->nullable();
            $table->string('type');
            $table->string('currency');
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_after', 15, 2)->nullable();
            $table->text('description')->nullable();
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

        (require database_path('migrations/2025_06_29_094036_create_permission_tables.php'))->up();

        foreach (['afficher-caisse-agent', 'modifier-solde-compte'] as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        $this->admin = User::create(['name' => 'Admin', 'postnom' => 'Test', 'telephone' => '1', 'role' => 'admin']);
        $this->admin->givePermissionTo(['afficher-caisse-agent', 'modifier-solde-compte']);

        $this->agent = User::create(['name' => 'Agent', 'postnom' => 'Caisse', 'telephone' => '2', 'role' => 'recouvreur']);
        $this->account = AgentAccount::create(['user_id' => $this->agent->id, 'currency' => 'CDF', 'balance' => 100000]);

        // 120 transactions aujourd'hui pour l'agent (3 pages de 50)
        $rows = [];
        for ($i = 1; $i <= 120; $i++) {
            $rows[] = [
                'user_id' => $this->agent->id,
                'type' => 'mise_quotidienne',
                'currency' => $i <= 100 ? 'CDF' : 'USD',
                'amount' => 1000,
                'balance_after' => 1000 * $i,
                'description' => "Opération {$i}",
                'created_at' => now()->startOfDay()->addMinutes($i),
                'updated_at' => now(),
            ];
        }
        Transaction::insert($rows);

        $this->actingAs($this->admin);
    }

    public function test_history_is_paginated_and_totals_cover_the_whole_period(): void
    {
        $component = Livewire::test(AgentDashboard::class)
            ->call('showTransactions', $this->agent->id, 'day');

        $transactions = $component->viewData('transactions');

        $this->assertSame(50, $transactions->count(), 'Une seule page de 50 transactions est chargée');
        $this->assertSame(120, $component->viewData('transactionCount'));
        $this->assertEquals(100000, $component->viewData('totalByCurrency')['CDF']);
        $this->assertEquals(20000, $component->viewData('totalByCurrency')['USD']);
        // La plus récente en premier
        $this->assertSame('Opération 120', $transactions->first()->description);
        $component->assertSee('Transactions 1 à 50 sur 120');

        // La liste n'est plus stockée dans l'état Livewire renvoyé à chaque clic
        $this->assertArrayNotHasKey('transactions', $component->instance()->all());
    }

    public function test_balance_rectification_saves_and_closes_the_modal(): void
    {
        Livewire::test(AgentDashboard::class)
            ->call('showTransactions', $this->agent->id, 'day')
            ->call('confirmUpdateBalance', $this->account->id)
            ->assertSet('openModifyBalance', true)
            ->set('newBalance', 150000)
            ->set('modificationReason', 'Correction après comptage')
            ->call('updateBalance')
            ->assertHasNoErrors()
            ->assertSet('openModifyBalance', false);

        $this->assertEquals(150000, $this->account->fresh()->balance);
        $this->assertDatabaseHas('transactions', [
            'user_id' => $this->agent->id,
            'type' => 'rectification_solde_agent',
            'amount' => 50000,
        ]);
    }

    public function test_transaction_edit_and_delete_work_from_the_history(): void
    {
        $transaction = Transaction::where('description', 'Opération 120')->first();

        $component = Livewire::test(AgentDashboard::class)
            ->call('showTransactions', $this->agent->id, 'day')
            ->call('confirmEditTransaction', $transaction->id)
            ->assertSet('openEditTransaction', true)
            ->set('editAmount', 2500)
            ->set('editDescription', 'Montant corrigé')
            ->call('updateTransaction')
            ->assertHasNoErrors()
            ->assertSet('openEditTransaction', false);

        $this->assertEquals(2500, $transaction->fresh()->amount);
        $component->assertSee('Montant corrigé');

        $component->call('confirmDeleteTransaction', $transaction->id)
            ->set('deleteReason', 'Doublon constaté')
            ->call('deleteTransaction')
            ->assertHasNoErrors()
            ->assertSet('openDeleteTransaction', false);

        $this->assertNull($transaction->fresh());
        $this->assertSame(119, $component->viewData('transactionCount'));
    }
}
