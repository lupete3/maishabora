<?php

namespace App\Http\Controllers;

use App\Models\CardBatch;
use App\Models\CardStockItem;
use App\Models\User;
use App\Services\CardStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CardStockController extends Controller
{
    private function query(Request $request)
    {
        $filters = $request->validate([
            'type' => 'nullable|in:epargne,simple',
            'status' => 'nullable|in:in_stock,with_collector,sold,lost,damaged,cancelled',
            'collector' => 'nullable|integer', 'batch' => 'nullable|integer',
            'search' => 'nullable|string|max:100', 'from' => 'nullable|date', 'to' => 'nullable|date|after_or_equal:from',
        ]);

        return CardStockItem::query()
            ->when($filters['type'] ?? null, fn ($q, $v) => $q->where('card_type', $v))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['collector'] ?? null, fn ($q, $v) => $q->where('collector_id', $v))
            ->when($filters['batch'] ?? null, fn ($q, $v) => $q->where('card_batch_id', $v))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereHas('batch', fn ($b) => $b->whereDate('received_at', '>=', $v)))
            ->when($filters['to'] ?? null, fn ($q, $v) => $q->whereHas('batch', fn ($b) => $b->whereDate('received_at', '<=', $v)))
            ->when(trim($filters['search'] ?? '') !== '', function ($query) use ($filters) {
                // Chaque mot peut correspondre au carnet ou a ses relations, sans contourner les filtres.
                foreach (preg_split('/\s+/u', trim($filters['search'])) as $term) {
                    $pattern = "%{$term}%";
                    $person = fn ($q) => $q->where('name', 'like', $pattern)->orWhere('postnom', 'like', $pattern)
                        ->orWhere('prenom', 'like', $pattern)->orWhere('code', 'like', $pattern);
                    $sale = fn ($q) => $q->where('code', 'like', $pattern)->orWhereHas('member', $person);
                    $query->where(function ($q) use ($term, $pattern, $person, $sale) {
                        $q->where('printed_number', 'like', $pattern);
                        if (ctype_digit($term)) {
                            $q->orWhere('id', $term);
                        }
                        $q->orWhereHas('collector', $person)->orWhereHas('sale', $sale)->orWhereHas('cancelledSales', $sale)
                            ->orWhereHas('batch', fn ($q) => $q->where('reference', 'like', $pattern)->orWhere('supplier', 'like', $pattern));
                    });
                }
            });
    }

    public function index(Request $request)
    {
        $report = $request->routeIs('card-stock.report');
        Gate::authorize($report ? 'afficher-rapport-stock-carnets' : 'afficher-stock-carnets');
        $request->validate([
            'section' => 'nullable|in:overview,stock,receive,assign,batches',
            'page' => 'nullable|integer|min:1', 'batches_page' => 'nullable|integer|min:1',
        ]);
        $section = $report ? 'stock' : ($request->input('section') ?: 'stock');
        $query = $this->query($request);
        // Charger uniquement les données de la section ouverte pour alléger la page.
        $summary = $report || $section === 'overview';
        $inventory = $report || $section === 'stock';
        $counts = $summary ? (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status') : collect();
        $salesTotals = $summary ? \App\Models\MembershipCard::whereIn('card_stock_item_id', (clone $query)->select('id'))
            ->whereNull('cancelled_at')->selectRaw('currency, count(*) as total, sum(price) as revenue')->groupBy('currency')->get() : collect();
        $collectorCounts = $summary ? CardStockItem::with('collector:id,name,postnom')->where('status', 'with_collector')
            ->selectRaw('collector_id, count(*) as total')->groupBy('collector_id')->get() : collect();
        $batches = collect();
        if ($section === 'batches' && !$report) {
            $batches = CardBatch::latest('id')->paginate(25, ['*'], 'batches_page')->withQueryString();
        } elseif ($inventory || $section === 'assign') {
            $batches = CardBatch::select('id', 'reference', 'supplier', 'quantity', 'card_type')->latest('id')
                ->when(!$inventory, fn ($q) => $q->whereHas('items', fn ($items) => $items->where('status', 'in_stock')))->get();
        }

        return view('card-stock.index', [
            'items' => $inventory ? $query->with(['batch', 'collector', 'sale.member'])->latest('id')->paginate(50)->withQueryString() : null,
            'counts' => $counts, 'salesTotals' => $salesTotals, 'collectorCounts' => $collectorCounts,
            'collectors' => ($inventory || $section === 'assign') ? User::select('id', 'name', 'postnom', 'role')
                ->whereIn('role', ['recouvreur', 'admin'])->where('status', true)->orderBy('name')->get() : collect(),
            'batches' => $batches, 'report' => $report, 'section' => $section,
        ]);
    }

    public function editNumber(CardStockItem $item)
    {
        Gate::authorize('modifier-numero-carnet');
        abort_unless($item->card_type === 'epargne', 422, 'Les carnets courants utilisent leur identifiant interne.');

        return view('card-stock.edit-number', compact('item'));
    }

    public function updateNumber(Request $request, CardStockItem $item, CardStockService $service)
    {
        $service->updateNumber($item->id, $request->all());

        notyf()->success('Numéro corrigé et modification historisée.');

        return redirect()->route('card-stock.index', ['batch' => $item->card_batch_id, 'section' => 'stock']);
    }

    public function editBatch(CardBatch $batch)
    {
        Gate::authorize('modifier-lot-carnets');

        return view('card-stock.edit-batch', compact('batch'));
    }

    public function updateBatch(Request $request, CardBatch $batch, CardStockService $service)
    {
        $service->updateBatch($batch->id, $request->all());

        notyf()->success('Lot modifié et correction historisée.');

        return redirect()->route('card-stock.index', ['section' => 'batches']);
    }

    public function receive(Request $request, CardStockService $service)
    {
        $service->receive($request->all());

        notyf()->success('Livraison enregistrée et carnets créés.');

        return back();
    }

    public function move(Request $request, CardStockService $service)
    {
        $data = $request->validate(['items' => 'required|array|min:1|max:10000', 'items.*' => 'required|integer|distinct',
            'action' => 'required|in:assign,return,transfer,lost,damaged', 'collector_id' => 'nullable|integer', 'reason' => 'required|string|max:1000']);
        $service->move($data['items'], $data['action'], isset($data['collector_id']) ? (int) $data['collector_id'] : null, $data['reason']);

        // Le retour efface le detenteur : son ancien filtre masquerait les carnets retournes.
        if ($data['action'] === 'return') {
            notyf()->success('Carnets revenus au stock et disponibles pour réaffectation.');

            return redirect()->route('card-stock.index', ['status' => 'in_stock']);
        }

        notyf()->success('Mouvement enregistré.');

        return back();
    }

    public function assignBatch(Request $request, CardStockService $service)
    {
        $data = $request->validate(['batch_id' => 'required|integer', 'collector_id' => 'required|integer',
            'range_start' => 'nullable|integer|min:1|max:999999999', 'range_end' => 'nullable|integer|min:1|max:999999999',
            'assign_quantity' => 'nullable|integer|min:1|max:10000', 'reason' => 'required|string|max:1000']);
        $service->assignFromBatch((int) $data['batch_id'], (int) $data['collector_id'],
            isset($data['range_start']) ? (int) $data['range_start'] : null,
            isset($data['range_end']) ? (int) $data['range_end'] : null,
            isset($data['assign_quantity']) ? (int) $data['assign_quantity'] : null, $data['reason']);

        notyf()->success('Attribution du lot enregistrée.');

        return back();
    }

    public function show(CardStockItem $item)
    {
        abort_unless(Gate::allows('afficher-stock-carnets') || Gate::allows('afficher-rapport-stock-carnets'), 403);
        $item->load(['batch', 'collector', 'sale.member', 'cancelledSales.member', 'movements.actor', 'movements.fromCollector', 'movements.toCollector']);

        return view('card-stock.show', compact('item'));
    }

    public function cancel(Request $request, CardStockItem $item, CardStockService $service)
    {
        $data = $request->validate(['reason' => 'required|string|max:1000', 'recovered' => 'nullable|boolean']);
        $service->cancelSale($item->id, $data['reason'], $request->boolean('recovered'));

        if ($request->boolean('recovered')) {
            notyf()->success('Vente annulée et carnet récupéré disponible pour réaffectation.');

            return redirect()->route('card-stock.index', ['status' => 'in_stock']);
        }

        notyf()->success('Vente annulée par écritures de compensation.');

        return back();
    }

    public function export(Request $request)
    {
        Gate::authorize('afficher-rapport-stock-carnets');
        $query = $this->query($request)->with(['batch', 'collector', 'sale.member', 'cancelledSales']);

        return response()->streamDownload(function () use ($query) {
            $stream = fopen('php://output', 'w');
            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, ['ID', 'Référence', 'Type', 'Lot', 'Fournisseur', 'Statut', 'Agent', 'Membre', 'Code vendu', 'Prix', 'Devise', 'Mise', 'Date vente', 'Annulée', 'Codes des ventes annulees'], ';');
            foreach ($query->orderBy('id')->lazy(500) as $item) {
                $row = [$item->id, $item->reference, $item->card_type, $item->batch->reference, $item->batch->supplier,
                    \App\Support\CardStockLabels::status($item->status), $item->collector?->name, $item->sale?->member?->name, $item->sale?->code,
                    $item->sale?->price, $item->sale?->currency, $item->sale?->subscription_amount,
                    $item->sale?->created_at, $item->sale?->cancelled_at, $item->cancelledSales->pluck('code')->implode(' | ')];
                // Neutraliser les formules lors de l'ouverture de l'export dans Excel.
                $row = array_map(fn ($v) => preg_match('/^[=+@\-\t\r]/', (string) $v) ? "'".$v : $v, $row);
                fputcsv($stream, $row, ';');
            }
            fclose($stream);
        }, 'stock-carnets.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
