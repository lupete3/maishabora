<?php

namespace App\Http\Controllers;

use App\Models\CompanyInformation;
use App\Models\MembershipCardInventory;
use App\Models\MembershipCardInventoryItem;
use App\Models\User;
use App\Services\MembershipCardInventoryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MembershipCardInventoryController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $filters = $request->validate([
            'from' => 'nullable|date',
            'to' => 'nullable|date|after_or_equal:from',
            'agent_id' => 'nullable|integer|exists:users,id',
            'page' => 'nullable|integer|min:1',
        ]);

        $inventories = MembershipCardInventory::query()
            ->with(['agent', 'creator'])
            ->withCount('items')
            ->withCount(['items as processed_items_count' => fn ($query) => $query->whereIn('status', ['inventoried', 'not_inventoried'])])
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('inventory_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('inventory_date', '<=', $date))
            ->when($filters['agent_id'] ?? null, fn ($query, $agentId) => $query->where('agent_id', $agentId))
            ->latest('inventory_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('membership-card-inventories.index', [
            'inventories' => $inventories,
            'agents' => User::query()->where('role', 'recouvreur')->orderBy('name')->get(),
            'filters' => $filters,
        ]);
    }

    public function create(Request $request, MembershipCardInventoryService $service)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $validated = $request->validate([
            'agent_id' => 'nullable|integer|exists:users,id',
            'search' => 'nullable|string|max:100',
        ]);

        $agents = User::query()
            ->where('role', 'recouvreur')
            ->where('status', true)
            ->whereHas('membershipCards', fn ($query) => $query->active()->where('card_type', 'epargne'))
            ->orderBy('name')
            ->get();
        $agent = null;
        $cards = collect();

        if ($validated['agent_id'] ?? null) {
            $agent = $agents->firstWhere('id', (int) $validated['agent_id']);
            abort_unless($agent, 404);
            $cards = $service->eligibleCards($agent->id, $validated['search'] ?? null);
        }

        return view('membership-card-inventories.create', [
            'agents' => $agents,
            'agent' => $agent,
            'cards' => $cards,
            'search' => $validated['search'] ?? '',
        ]);
    }

    public function store(Request $request, MembershipCardInventoryService $service)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $validated = $request->validate([
            'inventory_date' => 'required|date',
            'agent_id' => 'required|integer|exists:users,id',
            'card_ids' => 'required|array|min:1',
            'card_ids.*' => 'required|integer|distinct|exists:membership_cards,id',
        ]);

        $inventory = $service->createDraft(
            (int) $validated['agent_id'],
            $validated['inventory_date'],
            $validated['card_ids'],
        );

        notyf()->success('Brouillon créé. Enregistrez chaque ligne puis finalisez l’inventaire.');

        return redirect()->route('card-inventories.show', $inventory);
    }

    public function saveLine(Request $request, MembershipCardInventory $inventory, MembershipCardInventoryItem $item, MembershipCardInventoryService $service)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $validated = $request->validate([
            'physical_total' => 'nullable|numeric|min:0|decimal:0,2',
        ]);

        $service->saveLine($inventory, $item->id, $validated['physical_total'] ?? null);

        notyf()->success(blank($validated['physical_total'] ?? null)
            ? 'Ligne enregistrée comme non inventoriée.'
            : 'Montant de la ligne enregistré.');

        return redirect()->route('card-inventories.show', array_filter([
            'inventory' => $inventory,
            'search' => $request->input('search'),
        ]));
    }

    public function finalize(Request $request, MembershipCardInventory $inventory, MembershipCardInventoryService $service)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $service->finalize($inventory);

        notyf()->success('Inventaire finalisé et enregistré.');

        return redirect()->route('card-inventories.show', array_filter([
            'inventory' => $inventory,
            'search' => $request->input('search'),
        ]));
    }

    public function show(Request $request, MembershipCardInventory $inventory)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $validated = $request->validate([
            'search' => 'nullable|string|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        $inventory->load('creator');
        $allItems = $inventory->items()->orderBy('card_code')->get();
        $itemsQuery = $inventory->items()->orderBy('card_code');
        $search = trim($validated['search'] ?? '');

        if ($search !== '') {
            foreach (preg_split('/\s+/u', $search) as $term) {
                $pattern = "%{$term}%";
                $itemsQuery->where(function ($query) use ($pattern) {
                    $query->where('card_code', 'like', $pattern)
                        ->orWhere('member_code', 'like', $pattern)
                        ->orWhere('member_name', 'like', $pattern);
                });
            }
        }

        $inventoryRows = $itemsQuery->paginate(50)->withQueryString();
        $pendingCount = $inventory->items()->where('status', 'pending')->count();

        return view('membership-card-inventories.show', compact('inventory', 'allItems', 'inventoryRows', 'pendingCount', 'search'));
    }

    public function export(MembershipCardInventory $inventory)
    {
        Gate::authorize('gerer-inventaire-carnets');

        $inventory->load(['items' => fn ($query) => $query->orderBy('card_code')]);
        $company = CompanyInformation::getActive();
        $pdf = Pdf::loadView('pdf.membership-card-inventory', compact('inventory', 'company'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('inventaire-carnets-'.$inventory->inventory_date->format('Y-m-d').'-'.$inventory->id.'.pdf');
    }
}
