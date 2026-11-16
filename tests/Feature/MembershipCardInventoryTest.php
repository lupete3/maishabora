<?php

namespace Tests\Feature;

use App\Http\Controllers\MembershipCardInventoryController;
use App\Models\CompanyInformation;
use App\Models\MembershipCard;
use App\Models\MembershipCardInventoryItem;
use App\Models\User;
use App\Services\MembershipCardInventoryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MembershipCardInventoryTest extends TestCase
{
    private MembershipCardInventoryService $service;

    private User $agent;

    private User $member;

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
            $table->string('code')->nullable();
            $table->string('telephone')->nullable();
            $table->string('role');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });

        Schema::create('membership_cards', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('member_id')->constrained('users');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('currency')->default('CDF');
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('subscription_amount', 15, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->string('card_type');
            $table->boolean('is_active')->default(true);
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        (require database_path('migrations/2025_06_22_155658_create_daily_contributions_table.php'))->up();
        (require database_path('migrations/2025_06_29_094036_create_permission_tables.php'))->up();

        DB::table('roles')->insert([
            'name' => 'admin',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        (require database_path('migrations/2026_11_12_140000_create_membership_card_inventories.php'))->up();
        (require database_path('migrations/2026_11_12_151000_add_draft_support_to_membership_card_inventories.php'))->up();

        $this->agent = User::create(['name' => 'Agent', 'postnom' => 'Un', 'role' => 'recouvreur', 'status' => true]);
        $this->member = User::create(['name' => 'Client', 'postnom' => 'Un', 'code' => 'CL-001', 'role' => 'membre', 'status' => true]);
        $this->service = new MembershipCardInventoryService;
    }

    public function test_inventory_uses_only_paid_contributions_and_preserves_surplus_and_shortage(): void
    {
        $shortageCard = $this->card('EP-001');
        $surplusCard = $this->card('EP-002');

        foreach ([$shortageCard, $surplusCard] as $card) {
            for ($i = 0; $i < 10; $i++) {
                $card->contributions()->create([
                    'contribution_date' => today()->toDateString(),
                    'amount' => 10000,
                    'is_paid' => true,
                ]);
            }
            $card->contributions()->create([
                'contribution_date' => today()->toDateString(),
                'amount' => 5000,
                'is_paid' => false,
            ]);
        }

        $inventory = $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$shortageCard->id, $surplusCard->id],
        );

        $this->service->saveLine($inventory, $inventory->items()->where('membership_card_id', $shortageCard->id)->value('id'), '90000.00');
        $this->service->saveLine($inventory, $inventory->items()->where('membership_card_id', $surplusCard->id)->value('id'), '110000.00');
        $this->service->finalize($inventory);

        $items = MembershipCardInventoryItem::query()->where('inventory_id', $inventory->id)->get()->keyBy('card_code');

        $this->assertSame('recorded', $inventory->fresh()->status);
        $this->assertSame('100000.00', $items['EP-001']->theoretical_total);
        $this->assertSame('90000.00', $items['EP-001']->physical_total);
        $this->assertSame('-10000.00', $items['EP-001']->difference);
        $this->assertSame('100000.00', $items['EP-002']->theoretical_total);
        $this->assertSame('110000.00', $items['EP-002']->physical_total);
        $this->assertSame('10000.00', $items['EP-002']->difference);
        $this->assertSame(10, $items['EP-001']->paid_contributions_count);
        $this->assertSame($this->member->code, $items['EP-001']->member_code);
        $this->assertSame('10000.00', $items['EP-001']->subscription_amount);
        $this->assertSame('Agent Un', $inventory->agent_name);
    }

    public function test_inventory_excludes_inactive_simple_and_other_agents_cards(): void
    {
        $eligible = $this->card('EP-001');
        $inactive = $this->card('EP-002', ['is_active' => false]);
        $simple = $this->card('EP-003', ['card_type' => 'simple']);
        $otherAgent = User::create(['name' => 'Agent', 'postnom' => 'Deux', 'role' => 'recouvreur', 'status' => true]);
        $assignedElsewhere = $this->card('EP-004', ['user_id' => $otherAgent->id]);

        $cards = $this->service->eligibleCards($this->agent->id);

        $this->assertSame([$eligible->id], $cards->modelKeys());
        $this->assertNotContains($inactive->id, $cards->modelKeys());
        $this->assertNotContains($simple->id, $cards->modelKeys());
        $this->assertNotContains($assignedElsewhere->id, $cards->modelKeys());
    }

    public function test_inventory_can_be_limited_to_a_search_result(): void
    {
        $matching = $this->card('EP-001');
        $otherMember = User::create(['name' => 'Autre', 'postnom' => 'Client', 'code' => 'CL-002', 'role' => 'membre', 'status' => true]);
        $this->card('EP-002', ['member_id' => $otherMember->id]);

        $this->assertSame([$matching->id], $this->service->eligibleCards($this->agent->id, 'EP-001')->modelKeys());
        $this->assertSame([$matching->id], $this->service->eligibleCards($this->agent->id, $this->member->code)->modelKeys());

        $inventory = $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$matching->id],
        );

        $this->assertSame(1, $inventory->items()->count());
        $this->assertSame('pending', $inventory->items()->first()->status);
        $this->service->saveLine($inventory, $inventory->items()->value('id'), '0.00');
        $this->service->finalize($inventory);
        $this->assertDatabaseCount('membership_card_inventories', 1);
    }

    public function test_inventory_rejects_a_card_not_assigned_to_the_agent(): void
    {
        $this->card('EP-001');
        $otherAgent = User::create(['name' => 'Agent', 'postnom' => 'Deux', 'role' => 'recouvreur', 'status' => true]);
        $otherCard = $this->card('EP-002', ['user_id' => $otherAgent->id]);

        $this->expectException(ValidationException::class);
        $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$otherCard->id],
        );
    }

    public function test_empty_line_is_saved_as_not_inventoried_and_draft_can_be_resumed(): void
    {
        $presentCard = $this->card('EP-001');
        $missingCard = $this->card('EP-002');
        $inventory = $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$presentCard->id, $missingCard->id],
        );

        $presentItemId = $inventory->items()->where('membership_card_id', $presentCard->id)->value('id');
        $missingItemId = $inventory->items()->where('membership_card_id', $missingCard->id)->value('id');
        $this->service->saveLine($inventory, $presentItemId, '50000.00');
        $this->assertSame('draft', $inventory->fresh()->status);
        $this->assertSame('pending', $inventory->items()->where('membership_card_id', $missingCard->id)->value('status'));
        $this->assertSame('50000.00', $inventory->items()->where('membership_card_id', $presentCard->id)->value('physical_total'));

        $this->service->saveLine($inventory, $missingItemId, null);
        $missingItem = $inventory->items()->where('membership_card_id', $missingCard->id)->firstOrFail();
        $this->assertSame('not_inventoried', $missingItem->status);
        $this->assertNull($missingItem->physical_total);
        $this->assertNull($missingItem->difference);

        $this->service->finalize($inventory);
        $this->assertSame('recorded', $inventory->fresh()->status);
    }

    public function test_inventory_cannot_be_finalized_while_lines_are_pending(): void
    {
        $card = $this->card('EP-001');
        $inventory = $this->service->createDraft($this->agent->id, today()->toDateString(), [$card->id]);

        $this->expectException(ValidationException::class);
        $this->service->finalize($inventory);
    }

    public function test_inventory_pdf_renders_the_shared_report_header_and_card_details(): void
    {
        $card = $this->card('EP-001');
        $inventory = $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$card->id],
        );
        $this->service->saveLine($inventory, $inventory->items()->value('id'), '0.00');
        $this->service->finalize($inventory);
        $inventory->refresh();
        $inventory->load('items');
        $company = new CompanyInformation(['name' => 'Maisha Bora']);

        $html = view('pdf.membership-card-inventory', compact('inventory', 'company'))->render();

        $this->assertStringContainsString('MAISHA BORA', $html);
        $this->assertStringContainsString('Inventaire physique des carnets d’épargne', $html);
        $this->assertStringContainsString('Code membre', $html);
        $this->assertStringContainsString('Mise du carnet', $html);
        $this->assertStringContainsString('Arial, sans-serif', $html);
    }

    public function test_draft_pdf_shows_partial_status_and_only_inventoried_difference_totals(): void
    {
        $surplusCard = $this->card('EP-401');
        $shortageCard = $this->card('EP-402');
        $missingCard = $this->card('EP-403');
        $pendingCard = $this->card('EP-404');

        foreach ([$surplusCard, $shortageCard, $missingCard, $pendingCard] as $card) {
            $card->contributions()->create([
                'contribution_date' => today()->toDateString(),
                'amount' => 10000,
                'is_paid' => true,
            ]);
        }

        $inventory = $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$surplusCard->id, $shortageCard->id, $missingCard->id, $pendingCard->id],
        );
        $items = $inventory->items()->get()->keyBy('card_code');
        $this->service->saveLine($inventory, $items['EP-401']->id, '12000.00');
        $this->service->saveLine($inventory, $items['EP-402']->id, '7000.00');
        $this->service->saveLine($inventory, $items['EP-403']->id, null);
        $inventory->load('items');
        $company = new CompanyInformation(['name' => 'Maisha Bora']);

        $html = view('pdf.membership-card-inventory', compact('inventory', 'company'))->render();

        $this->assertStringContainsString('Situation provisoire', $html);
        $this->assertStringContainsString('1 ligne(s) à traiter', $html);
        $this->assertStringContainsString('1 carnet(s) non inventorié(s)', $html);
        $this->assertStringContainsString('À traiter', $html);
        $this->assertStringContainsString('Non inventorié', $html);
        $this->assertStringContainsString('Surplus (+)', $html);
        $this->assertStringContainsString('Manquant (-)', $html);
        $this->assertStringContainsString('2 000,00', $html);
        $this->assertStringContainsString('-3 000,00', $html);
        $this->assertStringContainsString('-1 000,00', $html);
    }

    public function test_inventory_detail_search_matches_card_code_member_code_and_member_name(): void
    {
        $actor = User::create(['name' => 'Admin', 'role' => 'admin', 'status' => true]);
        $this->actingAs($actor);
        Gate::define('gerer-inventaire-carnets', fn (User $user) => true);

        $cardOne = $this->card('EP-101');
        $memberTwo = User::create(['name' => 'Aline', 'postnom' => 'Bora', 'code' => 'CL-002', 'role' => 'membre', 'status' => true]);
        $cardTwo = $this->card('EP-202', ['member_id' => $memberTwo->id]);
        $memberThree = User::create(['name' => 'Jean', 'postnom' => 'Mbuyi', 'prenom' => 'Paul', 'code' => 'CL-003', 'role' => 'membre', 'status' => true]);
        $cardThree = $this->card('EP-303', ['member_id' => $memberThree->id]);

        $inventory = $this->service->createDraft(
            $this->agent->id,
            today()->toDateString(),
            [$cardOne->id, $cardTwo->id, $cardThree->id],
        );
        $controller = app(MembershipCardInventoryController::class);
        view()->share('errors', new \Illuminate\Support\ViewErrorBag);

        foreach ([
            'EP-101' => $cardOne->id,
            'CL-002' => $cardTwo->id,
            'Jean Mbuyi' => $cardThree->id,
        ] as $search => $expectedCardId) {
            $response = $controller->show(
                Request::create('/inventaires-carnets/'.$inventory->id, 'GET', ['search' => $search]),
                $inventory,
            );

            $this->assertSame(
                [$expectedCardId],
                $response->getData()['inventoryRows']->getCollection()->pluck('membership_card_id')->all(),
            );
        }

        $emptyResponse = $controller->show(
            Request::create('/inventaires-carnets/'.$inventory->id, 'GET', ['search' => 'NOT-FOUND']),
            $inventory,
        );
        $this->assertTrue($emptyResponse->getData()['inventoryRows']->isEmpty());
    }

    public function test_last_contribution_date_is_the_current_payment_time_of_paid_mises(): void
    {
        $this->travelTo('2026-11-16 09:00:00');

        $card = $this->card('EP-010');
        $neverPaid = $this->card('EP-011');

        // Mise du 20/10 encaissée le 01/11 10:00, mise du 21/10 encaissée le 05/11 14:30
        $this->paidMise($card, '2026-10-20', '2026-11-01 10:00:00');
        $this->paidMise($card, '2026-10-21', '2026-11-05 14:30:00');
        // Une mise NON payée modifiée plus tard ne doit pas compter
        $unpaid = $card->contributions()->create(['contribution_date' => '2026-10-22', 'amount' => 10000, 'is_paid' => false]);
        DB::table('daily_contributions')->where('id', $unpaid->id)->update(['updated_at' => '2026-11-10 08:00:00']);

        // Écran de création : la date vient du carnet
        $cards = $this->service->eligibleCards($this->agent->id)->keyBy('code');
        $this->assertSame('2026-11-05 14:30:00', (string) $cards['EP-010']->last_contribution_at);
        $this->assertNull($cards['EP-011']->last_contribution_at);

        // Inventaire (y compris un inventaire créé avant cette fonctionnalité) : date calculée en direct
        $inventory = $this->service->createDraft($this->agent->id, '2026-11-16', [$card->id, $neverPaid->id]);
        $items = $inventory->items()->get();
        $this->service->attachLastContributionDates($items);
        $items = $items->keyBy('card_code');

        $this->assertSame('05/11/2026 14:30', $items['EP-010']->last_contribution_at->format('d/m/Y H:i'));
        $this->assertNull($items['EP-011']->last_contribution_at);
        $this->assertSame(11, $items['EP-010']->daysSinceLastContribution(now()));
        $this->assertSame('warning', $items['EP-010']->lastContributionLevel(now()));
        $this->assertSame('danger', $items['EP-011']->lastContributionLevel(now()));

        // Rien n'est enregistré en base : ce n'est pas une colonne
        $this->assertFalse(Schema::hasColumn('membership_card_inventory_items', 'last_contribution_at'));

        // Un encaissement fait après l'inventaire est pris en compte (date actuelle)
        $this->paidMise($card, '2026-10-23', '2026-11-15 17:45:00');
        $fresh = $inventory->items()->get();
        $this->service->attachLastContributionDates($fresh);
        $this->assertSame('15/11/2026 17:45', $fresh->firstWhere('card_code', 'EP-010')->last_contribution_at->format('d/m/Y H:i'));
        $this->assertSame(1, $fresh->firstWhere('card_code', 'EP-010')->daysSinceLastContribution(now()));
    }

    public function test_pdf_shows_the_last_contribution_column(): void
    {
        $this->travelTo('2026-11-16 09:00:00');

        $card = $this->card('EP-020');
        $this->paidMise($card, '2026-10-01', '2026-10-10 11:15:00');
        $inventory = $this->service->createDraft($this->agent->id, '2026-11-16', [$card->id]);
        $inventory->load('items');
        $this->service->attachLastContributionDates($inventory->items);
        $company = new CompanyInformation(['name' => 'Maisha Bora']);

        $html = view('pdf.membership-card-inventory', compact('inventory', 'company'))->render();

        $this->assertStringContainsString('Dernière mise', $html);
        $this->assertStringContainsString('10/10/2026 11:15', $html);
        $this->assertStringContainsString('(37 j)', $html);
    }

    /** Crée une mise payée dont l'encaissement (updated_at) a eu lieu au moment donné. */
    private function paidMise(MembershipCard $card, string $coveredDay, string $paidAt): void
    {
        $mise = $card->contributions()->create(['contribution_date' => $coveredDay, 'amount' => 10000, 'is_paid' => true]);
        DB::table('daily_contributions')->where('id', $mise->id)->update(['updated_at' => $paidAt]);
    }

    private function card(string $code, array $attributes = []): MembershipCard
    {
        return MembershipCard::create(array_merge([
            'code' => $code,
            'member_id' => $this->member->id,
            'user_id' => $this->agent->id,
            'currency' => 'CDF',
            'price' => 10000,
            'subscription_amount' => 10000,
            'start_date' => today()->toDateString(),
            'end_date' => today()->addMonth()->toDateString(),
            'card_type' => 'epargne',
            'is_active' => true,
        ], $attributes));
    }
}
