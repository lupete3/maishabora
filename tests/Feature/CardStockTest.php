<?php

namespace Tests\Feature;

use App\Models\AgentAccount;
use App\Models\MembershipCard;
use App\Models\Transaction;
use App\Models\User;
use App\Services\CardStockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CardStockTest extends TestCase
{
    private CardStockService $service;

    private User $collector;

    private bool $authorized = true;

    protected function setUp(): void
    {
        parent::setUp();
        // Base SQLite isolee : aucun rafraichissement de la base applicative.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('postnom')->nullable();
            $t->string('prenom')->nullable();
            $t->string('code')->nullable();
            $t->string('role');
            $t->boolean('status')->default(true);
            $t->timestamps();
        });
        Schema::create('membership_cards', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->foreignId('member_id')->constrained('users');
            $t->unsignedBigInteger('user_id')->nullable();
            $t->string('currency');
            $t->decimal('price', 15, 2);
            $t->decimal('subscription_amount', 15, 2);
            $t->date('start_date');
            $t->date('end_date');
            $t->string('card_type');
            $t->boolean('is_active')->default(true);
            $t->timestamps();
        });
        Schema::create('agent_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('users');
            $t->string('currency');
            $t->decimal('balance', 15, 2);
            $t->timestamps();
        });
        Schema::create('transactions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('agent_account_id');
            $t->unsignedBigInteger('user_id');
            $t->string('type');
            $t->string('currency');
            $t->decimal('amount', 15, 2);
            $t->decimal('balance_after', 15, 2);
            $t->text('description');
            $t->timestamps();
        });
        (require database_path('migrations/2026_10_04_100000_create_card_stock_tables.php'))->up();
        (require database_path('migrations/2026_10_04_110000_archive_cancelled_card_stock_sales.php'))->up();
        (require database_path('migrations/2025_06_22_155658_create_daily_contributions_table.php'))->up();
        (require database_path('migrations/2025_06_29_094036_create_permission_tables.php'))->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $actor = User::create(['name' => 'Operator', 'role' => 'admin']);
        $this->collector = User::create(['name' => 'Collector', 'role' => 'recouvreur']);
        $this->actingAs($actor);
        foreach (['modifier-numero-carnet', 'modifier-lot-carnets', 'afficher-stock-carnets', 'recevoir-stock-carnets', 'distribuer-stock-carnets', 'corriger-stock-carnets', 'ajouter-carnet', 'annuler-vente-carnet'] as $permission) {
            Gate::define($permission, fn () => $this->authorized);
        }
        $this->service = new CardStockService;
    }

    public function test_number_correction_preserves_existing_sale_and_logs_changes(): void
    {
        $item = $this->receive()->items()->first();
        $item->update(['status' => 'sold', 'collector_id' => $this->collector->id]);
        $sale = MembershipCard::create(['card_stock_item_id' => $item->id, 'member_id' => auth()->id(),
            'code' => '1000/X', 'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 100,
            'start_date' => today(), 'end_date' => today()->addMonth(), 'card_type' => 'epargne']);
        $this->service->updateNumber($item->id, ['printed_number' => '1645', 'original_number' => '1000', 'reason' => 'Ancien carnet']);
        $item->refresh();
        $this->assertSame('1645', $item->printed_number);
        $this->assertSame('sold', $item->status);
        $this->assertSame($this->collector->id, $item->collector_id);
        $this->assertSame('1000/X', $sale->fresh()->code);
        $this->assertSame($item->id, $sale->fresh()->card_stock_item_id);
        $this->assertSame('1645/Y', $this->service->composeCode($item, 'Y'));
        $movement = $item->movements()->where('action', 'update_number')->firstOrFail();
        $this->assertStringContainsString('1000', $movement->reason);
        $this->assertStringContainsString('1645', $movement->reason);
        $this->assertSame(auth()->id(), $movement->created_by);
        $this->assertSame(1000, $item->batch->number_start);
    }

    public function test_number_correction_rejects_duplicate_stale_and_current_account_numbers(): void
    {
        $item = $this->receive()->items()->first();
        foreach ([['1001', '1000'], ['1645', '999']] as [$number, $original]) {
            try {
                $this->service->updateNumber($item->id, ['printed_number' => $number, 'original_number' => $original, 'reason' => 'Correction']);
                $this->fail('Invalid correction accepted');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('printed_number', $exception->errors());
            }
        }
        $this->assertSame('1000', $item->fresh()->printed_number);
        $this->assertSame(0, $item->movements()->where('action', 'update_number')->count());
        $simple = $this->receive('SIMPLE', 'simple')->items()->first();
        $this->expectException(ValidationException::class);
        $this->service->updateNumber($simple->id, ['printed_number' => '1645', 'original_number' => '1', 'reason' => 'Correction']);
    }

    public function test_number_correction_requires_permission(): void
    {
        $item = $this->receive()->items()->first();
        $this->authorized = false;
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->service->updateNumber($item->id, ['printed_number' => '1645', 'original_number' => '1000', 'reason' => 'Correction']);
    }

    public function test_sections_load_only_their_required_data(): void
    {
        $batch = $this->receive();
        $controller = app(\App\Http\Controllers\CardStockController::class);
        foreach (['overview', 'receive', 'assign', 'batches'] as $section) {
            $data = $controller->index(\Illuminate\Http\Request::create('/carnets-stock', 'GET', ['section' => $section]))->getData();
            $this->assertSame($section, $data['section']);
            $data['statuses'] = \App\Support\CardStockLabels::STATUSES;
            $this->assertNotEmpty(view('card-stock.sections.'.$section, $data)->render());
            $this->assertNull($data['items']);
            if ($section !== 'overview') {
                $this->assertTrue($data['collectorCounts']->isEmpty());
                $this->assertTrue($data['salesTotals']->isEmpty());
            }
            if ($section === 'batches') {
                $this->assertSame(1, $data['batches']->total());
                $this->assertSame(25, $data['batches']->perPage());
            }
        }
        $inventory = $controller->index(\Illuminate\Http\Request::create('/carnets-stock'))->getData();
        $inventory['statuses'] = \App\Support\CardStockLabels::STATUSES;
        $this->assertNotEmpty(view('card-stock.sections.stock', $inventory)->render());
        $inventory['report'] = true;
        $this->assertStringNotContainsString('Enregistrer le mouvement', view('card-stock.sections.stock', $inventory)->render());
        $this->service->move($batch->items()->pluck('id')->all(), 'assign', $this->collector->id, 'Delivery');
        $data = $controller->index(\Illuminate\Http\Request::create('/carnets-stock', 'GET', ['section' => 'assign']))->getData();
        $this->assertTrue($data['batches']->isEmpty());
    }

    private function receive(string $reference = 'LOT-1', string $type = 'epargne', int $start = 1000, int $end = 1002)
    {
        return $this->service->receive(['reference' => $reference, 'supplier' => 'Supplier', 'received_at' => now()->toDateString(),
            'card_type' => $type, 'number_start' => $type === 'epargne' ? $start : null,
            'number_end' => $type === 'epargne' ? $end : null, 'quantity' => 3]);
    }

    public function test_receipt_creates_inclusive_numbers_and_rejects_overlapping_lots_atomically(): void
    {
        $batch = $this->receive();
        $this->assertSame(3, $batch->quantity);
        $this->assertSame(['1000', '1001', '1002'], $batch->items()->pluck('printed_number')->all());
        $this->assertDatabaseCount('card_stock_movements', 3);
        try {
            $this->receive('LOT-2', 'epargne', 1002, 1004);
            $this->fail('Duplicate accepted');
        } catch (ValidationException) {
            $this->assertDatabaseCount('card_batches', 1);
            $this->assertDatabaseCount('card_stock_items', 3);
        }
    }

    public function test_current_booklets_have_internal_references_and_no_printed_numbers(): void
    {
        $item = $this->receive('CURRENT', 'simple')->items()->first();
        $this->assertNull($item->printed_number);
        $this->assertSame($item->id.'/26.4/1641', $this->service->composeCode($item, ' /26.4/1641/ '));
        $this->assertSame((string) $item->id, $this->service->composeCode($item, null));
    }

    public function test_distribution_transfer_return_and_sale_are_traced(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Prospection');
        $other = User::create(['name' => 'Other', 'role' => 'recouvreur']);
        $this->service->move([$item->id], 'transfer', $other->id, 'Transfer');
        $this->service->move([$item->id], 'return', null, 'Return');
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Prospection');
        DB::transaction(function () use ($item) {
            $locked = $this->service->lockForSale($item->id, $this->collector->id, 'epargne');
            $this->assertSame('1000/26.4/1641', $this->service->composeCode($locked, '26.4/1641'));
            $this->service->markSold($locked);
        });
        $this->assertSame('sold', $item->fresh()->status);
        $this->assertSame(6, $item->movements()->count());
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => $this->service->lockForSale($item->id, $this->collector->id, 'epargne'));
    }

    public function test_sale_rejects_wrong_collector_and_type(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Assign');
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => $this->service->lockForSale($item->id, $this->collector->id, 'simple'));
    }

    public function test_invalid_movement_rolls_back_all_selected_items(): void
    {
        $items = $this->receive()->items()->get();
        $this->service->move([$items[1]->id], 'lost', null, 'Missing');
        try {
            $this->service->move([$items[0]->id, $items[1]->id], 'assign', $this->collector->id, 'Assign');
            $this->fail('Invalid move accepted');
        } catch (ValidationException) {
            $this->assertSame('in_stock', $items[0]->fresh()->status);
            $this->assertSame('lost', $items[1]->fresh()->status);
        }
    }

    public function test_stock_mutations_require_permissions(): void
    {
        $this->authorized = false;
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->receive();
    }

    public function test_batch_assignment_uses_quantity_or_exact_inclusive_range(): void
    {
        $batch = $this->receive('CURRENT', 'simple');
        $this->service->assignFromBatch($batch->id, $this->collector->id, null, null, 2, 'Assign');
        $this->assertSame(2, $batch->items()->where('status', 'with_collector')->count());
        $this->assertSame(1, $batch->items()->where('status', 'in_stock')->count());
        $savings = $this->receive('SAVINGS');
        $this->service->assignFromBatch($savings->id, $this->collector->id, 1000, 1001, null, 'Assign');
        $this->assertSame(['1000', '1001'], $savings->items()->where('status', 'with_collector')->pluck('printed_number')->all());
        try {
            $this->service->assignFromBatch($savings->id, $this->collector->id, 1001, 1002, null, 'Assign');
            $this->fail('Partially unavailable range accepted');
        } catch (ValidationException) {
            $this->assertSame(1, $savings->items()->where('status', 'in_stock')->count());
        }
    }

    public function test_inactive_or_non_collector_cannot_receive_booklets(): void
    {
        $item = $this->receive()->items()->first();
        $inactive = User::create(['name' => 'Inactive', 'role' => 'recouvreur', 'status' => false]);
        foreach ([$inactive->id, User::create(['name' => 'Member', 'role' => 'membre'])->id] as $id) {
            try {
                $this->service->move([$item->id], 'assign', $id, 'Assign');
                $this->fail('Invalid collector accepted');
            } catch (ValidationException) {
                $this->assertSame('in_stock', $item->fresh()->status);
            }
        }
    }

    public function test_wrong_collector_cannot_sell_a_booklet(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Assign');
        $other = User::create(['name' => 'Other', 'role' => 'recouvreur']);
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => $this->service->lockForSale($item->id, $other->id, 'epargne'));
    }

    public function test_paid_contributions_prevent_sale_cancellation(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Assign');
        $sale = MembershipCard::create(['card_stock_item_id' => $item->id, 'member_id' => auth()->id(),
            'code' => '1000/X', 'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 100,
            'card_type' => 'epargne', 'start_date' => now(), 'end_date' => now()->addDays(30)]);
        $sale->contributions()->create(['contribution_date' => now(), 'amount' => 100, 'is_paid' => true]);
        $this->service->markSold($item->fresh());
        try {
            $this->service->cancelSale($item->id, 'Mistake', true);
            $this->fail('Paid sale cancelled');
        } catch (ValidationException) {
            $this->assertSame('sold', $item->fresh()->status);
            $this->assertNull($sale->fresh()->cancelled_at);
            $this->assertDatabaseCount('transactions', 0);
        }
    }

    public function test_admin_can_receive_and_sell_current_booklets(): void
    {
        $item = $this->receive('CURRENT', 'simple')->items()->first();
        $this->service->move([$item->id], 'assign', auth()->id(), 'Current account');
        $this->assertSame(auth()->id(), $item->fresh()->collector_id);
        DB::transaction(fn () => $this->service->lockForSale($item->id, auth()->id(), 'simple'));
    }

    public function test_return_clears_holder_and_redirects_to_reassignable_stock(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Assign');
        $request = \Illuminate\Http\Request::create('/carnets-stock/mouvements', 'POST', [
            'items' => [$item->id], 'action' => 'return', 'reason' => 'Unused']);
        $response = (new \App\Http\Controllers\CardStockController)->move($request, $this->service);
        $this->assertSame(route('card-stock.index', ['status' => 'in_stock']), $response->getTargetUrl());
        $this->assertSame('in_stock', $item->fresh()->status);
        $this->assertNull($item->fresh()->collector_id);
        $this->service->assignFromBatch($item->card_batch_id, auth()->id(), 1000, 1000, null, 'Reassign');
        $this->assertSame(auth()->id(), $item->fresh()->collector_id);
    }

    public function test_lost_and_damaged_booklets_are_tracked_by_admin_and_not_available_for_sale(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'damaged', auth()->id(), 'Damaged');
        $this->assertSame(auth()->id(), $item->fresh()->collector_id);
        $this->assertSame('damaged', $item->fresh()->status);
        $this->expectException(ValidationException::class);
        DB::transaction(fn () => $this->service->lockForSale($item->id, auth()->id(), 'epargne'));
    }

    public function test_migration_restores_old_recovered_booklets_without_restoring_lost_ones(): void
    {
        Schema::table('membership_cards', fn (Blueprint $t) => $t->dropConstrainedForeignId('archived_card_stock_item_id'));
        $items = $this->receive()->items()->get();
        foreach (['cancelled', 'lost'] as $index => $status) {
            $items[$index]->update(['status' => $status]);
            MembershipCard::create(['card_stock_item_id' => $items[$index]->id, 'member_id' => auth()->id(),
                'code' => 'OLD-'.$index, 'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 100,
                'card_type' => 'epargne', 'start_date' => now(), 'end_date' => now()->addDays(30),
                'cancelled_at' => now(), 'is_active' => false]);
        }
        (require database_path('migrations/2026_10_04_110000_archive_cancelled_card_stock_sales.php'))->up();
        $this->assertSame('in_stock', $items[0]->fresh()->status);
        $this->assertSame('lost', $items[1]->fresh()->status);
        $this->assertSame(1, $items[0]->movements()->where('action', 'restore_stock')->count());
        $this->assertSame(1, $items[0]->cancelledSales()->count());
        $this->assertSame(1, $items[1]->cancelledSales()->count());
        $this->assertNull($items[0]->fresh()->sale);
        $this->service->move([$items[0]->id], 'assign', auth()->id(), 'Reassign');
        $this->assertSame('with_collector', $items[0]->fresh()->status);
    }

    public function test_stock_search_matches_relations_and_keeps_other_filters(): void
    {
        $batch = $this->receive('LOT-ALPHA');
        $item = $batch->items()->first();
        $this->collector->update(['name' => 'Jean', 'postnom' => 'Kabila']);
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Assign');
        $member = User::create(['name' => 'Alice', 'postnom' => 'Kamau', 'code' => 'MEM-42', 'role' => 'membre']);
        MembershipCard::create(['card_stock_item_id' => $item->id, 'member_id' => $member->id,
            'code' => '1000/26.4/1641', 'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 100,
            'card_type' => 'epargne', 'start_date' => now(), 'end_date' => now()->addDays(30)]);
        $controller = new \App\Http\Controllers\CardStockController;
        foreach (['1000', '26.4/1641', 'MEM-42', 'Alice Kamau', 'Jean Kabila', '  Jean   Kabila  '] as $search) {
            $view = $controller->index(\Illuminate\Http\Request::create('/carnets-stock', 'GET', ['search' => $search]));
            $this->assertSame([$item->id], $view->getData()['items']->pluck('id')->all());
        }
        foreach (['LOT-ALPHA', 'Supplier'] as $search) {
            $view = $controller->index(\Illuminate\Http\Request::create('/carnets-stock', 'GET', ['search' => $search]));
            $this->assertSame(3, $view->getData()['items']->total());
        }
        $view = $controller->index(\Illuminate\Http\Request::create('/carnets-stock', 'GET', ['search' => 'Jean', 'status' => 'in_stock']));
        $this->assertSame(0, $view->getData()['items']->total());
        $view = $controller->index(\Illuminate\Http\Request::create('/carnets-stock', 'GET', ['search' => '   ']));
        $this->assertSame(3, $view->getData()['items']->total());
    }

    public function test_inactivity_filters_include_cutoff_dates_and_apply_to_stats_list_and_csv(): void
    {
        \Carbon\Carbon::setTestNow('2026-10-04 12:00:00');
        try {
            $dates = [now(), now()->subWeek(), now()->subMonthNoOverflow(), now()->subMonthsNoOverflow(3), now()->subMonthsNoOverflow(3)->subSecond()];
            foreach ($dates as $index => $date) {
                MembershipCard::create(['member_id' => auth()->id(), 'code' => 'FOLLOW-'.$index,
                    'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 100, 'card_type' => 'epargne',
                    'start_date' => now(), 'end_date' => now()->addDays(30)])->forceFill(['updated_at' => $date])->save();
            }
            MembershipCard::create(['member_id' => auth()->id(), 'code' => 'CURRENT',
                'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 0, 'card_type' => 'simple',
                'start_date' => now(), 'end_date' => now()->addDays(30)])->forceFill(['updated_at' => now()->subYear()])->save();
            foreach (['' => 5, 'week' => 4, 'month' => 3, 'three_months' => 2, 'over_three_months' => 1] as $period => $expected) {
                $component = new \App\Livewire\Repports\RapportCarnetsComponent;
                $component->cardType = 'epargne';
                $component->inactivityPeriod = $period;
                $component->updateStats();
                $this->assertSame($expected, $component->totalCarnets);
                $this->assertSame($expected, $component->getFilteredCarnetsProperty()->total());
                ob_start();
                $component->exportExcel()->sendContent();
                $csv = ob_get_clean();
                $handle = fopen('php://temp', 'r+');
                fwrite($handle, $csv);
                rewind($handle);
                fgetcsv($handle, 0, ';');
                $headers = fgetcsv($handle, 0, ';');
                $this->assertSame('Derniere Operation', end($headers));
                $rows = [];
                while (($row = fgetcsv($handle, 0, ';')) !== false) {
                    $rows[] = $row;
                }
                fclose($handle);
                $this->assertCount($expected, $rows);
                if ($period === 'over_three_months') {
                    $this->assertSame('FOLLOW-4', $rows[0][0]);
                    $this->assertSame('04/07/2026 11:59', end($rows[0]));
                }
            }
        } finally {
            \Carbon\Carbon::setTestNow();
        }
    }

    public function test_batch_edits_preserve_physical_inventory_and_record_old_values(): void
    {
        $batch = $this->receive();
        $this->service->move([$batch->items()->first()->id], 'assign', $this->collector->id, 'Assign');
        $this->service->updateBatch($batch->id, ['reference' => 'CORRECTED', 'supplier' => 'New supplier',
            'received_at' => now()->toDateString(), 'reason' => 'Correction', 'quantity' => 999, 'card_type' => 'simple']);
        $this->assertSame('CORRECTED', $batch->fresh()->reference);
        $this->assertSame('epargne', $batch->fresh()->card_type);
        $this->assertSame(3, $batch->fresh()->quantity);
        $this->assertSame(['1000', '1001', '1002'], $batch->items()->pluck('printed_number')->all());
        $this->assertSame(3, \App\Models\CardStockMovement::where('action', 'update_batch')->count());
        $this->assertStringContainsString('LOT-1', $batch->items()->first()->movements()->where('action', 'update_batch')->first()->reason);
        $this->authorized = false;
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->service->updateBatch($batch->id, []);
    }

    public function test_permission_seeder_reserves_stock_permissions(): void
    {
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        foreach (['afficher-stock-carnets', 'recevoir-stock-carnets', 'distribuer-stock-carnets', 'corriger-stock-carnets', 'annuler-vente-carnet', 'modifier-lot-carnets', 'modifier-numero-carnet', 'afficher-rapport-stock-carnets'] as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name]);
        }
    }

    public function test_actual_sale_creates_stock_link_contributions_and_currency_specific_transactions(): void
    {
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('type');
            $t->string('currency');
            $t->string('status');
            $t->timestamps();
        });
        Schema::create('user_logs', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('user_id');
            $t->string('action');
            $t->text('description')->nullable();
            $t->text('device')->nullable();
            $t->string('ip_address')->nullable();
            $t->timestamps();
        });
        Schema::table('transactions', fn (Blueprint $t) => $t->unsignedBigInteger('account_id')->nullable());
        DB::table('users')->insert(['id' => 97, 'name' => 'Profit', 'role' => 'admin', 'status' => true]);
        $member = User::create(['name' => 'Member', 'role' => 'membre']);
        DB::table('accounts')->insert(['user_id' => $member->id, 'type' => 'savings', 'currency' => 'USD', 'status' => 'Actif']);
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Prospection');
        $component = new \App\Livewire\PurchaseMembershipCard;
        $component->member_id = $member->id;
        $component->agent_id = $this->collector->id;
        $component->stock_item_id = $item->id;
        $component->card_type = 'epargne';
        $component->currency = 'USD';
        $component->price = 1;
        $component->subscription_amount = 2;
        $component->code = '26.4/1641';
        $component->submit();
        $sale = MembershipCard::firstOrFail();
        $this->assertSame('1000/26.4/1641', $sale->code);
        $this->assertSame($item->id, $sale->card_stock_item_id);
        $this->assertSame(auth()->id(), $sale->sold_by);
        $this->assertSame('sold', $item->fresh()->status);
        $this->assertSame(31, $sale->contributions()->count());
        $this->assertSame(0, $sale->contributions()->where('is_paid', true)->count());
        $this->assertSame(2, Transaction::where('membership_card_id', $sale->id)->where('currency', 'USD')->count());
        $this->assertEquals([1, 1], AgentAccount::pluck('balance')->all());
        $this->assertSame(0, DB::transactionLevel());
    }

    public function test_sale_cancellation_compensates_exact_transactions_and_cannot_repeat(): void
    {
        $item = $this->receive()->items()->first();
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Assign');
        $sale = MembershipCard::create(['card_stock_item_id' => $item->id, 'member_id' => auth()->id(),
            'code' => '1000/X', 'currency' => 'CDF', 'price' => 1000, 'subscription_amount' => 100,
            'card_type' => 'epargne', 'start_date' => now(), 'end_date' => now()->addDays(30)]);
        $this->service->markSold($item->fresh());
        foreach ([auth()->id(), $this->collector->id] as $owner) {
            $account = AgentAccount::create(['user_id' => $owner, 'currency' => 'CDF', 'balance' => 1500]);
            Transaction::create(['membership_card_id' => $sale->id, 'agent_account_id' => $account->id,
                'user_id' => $owner, 'type' => 'vente_carte_adhesion', 'currency' => 'CDF', 'amount' => 1000,
                'balance_after' => 1500, 'description' => 'Sale']);
        }
        $this->service->cancelSale($item->id, 'Mistake', true);
        $this->assertSame('in_stock', $item->fresh()->status);
        $this->assertNotNull($sale->fresh()->cancelled_at);
        $this->assertNull(MembershipCard::find($sale->id));
        $this->assertNull($item->fresh()->sale);
        $this->assertSame($sale->id, $item->fresh()->cancelledSales->first()->id);
        $this->assertNull($sale->fresh()->card_stock_item_id);
        $this->assertFalse((bool) $sale->fresh()->is_active);
        $this->assertEquals([500, 500], AgentAccount::pluck('balance')->all());
        $this->assertSame(2, Transaction::where('type', 'annulation_vente_carte_adhesion')->count());
        $this->service->move([$item->id], 'assign', $this->collector->id, 'Reassign');
        DB::transaction(fn () => $this->service->lockForSale($item->id, $this->collector->id, 'epargne'));
        $nextSale = $sale->fresh()->replicate();
        $nextSale->fill(['code' => '1000/NEW', 'card_stock_item_id' => $item->id,
            'archived_card_stock_item_id' => null, 'cancelled_at' => null, 'is_active' => true]);
        $nextSale->save();
        $this->service->markSold($item->fresh());
        $this->assertSame($nextSale->id, $item->fresh()->sale->id);
        $this->assertSame($sale->id, $item->fresh()->cancelledSales->first()->id);
        $this->expectException(ValidationException::class);
        $this->service->cancelSale($item->id, 'Again', true);
    }
}
