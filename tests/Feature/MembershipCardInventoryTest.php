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
        $inventory->load('items');
        $company = new CompanyInformation(['name' => 'Maisha Bora']);

        $html = view('pdf.membership-card-inventory', compact('inventory', 'company'))->render();

        $this->assertStringContainsString('MAISHA BORA', $html);
        $this->assertStringContainsString('Inventaire physique des carnets d’épargne', $html);
        $this->assertStringContainsString('Code membre', $html);
        $this->assertStringContainsString('Mise du carnet', $html);
        $this->assertStringContainsString('Arial, sans-serif', $html);
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
