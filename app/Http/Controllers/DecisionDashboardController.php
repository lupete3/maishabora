<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AgentAccount;
use App\Models\Credit;
use App\Models\MainCashRegister;
use App\Models\Repayment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DecisionDashboardController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::user()->isActive()) {
            return view('not-found');
        }

        $today = now()->startOfDay();
        $monthStart = now()->startOfMonth();
        $depositTypes = ['dépôt', 'mise_quotidienne'];
        $withdrawalTypes = ['retrait', 'retrait_carte_adhesion'];
        $selectedCurrency = in_array($request->query('currency'), ['USD', 'CDF'], true) ? $request->query('currency') : null;
        $selectedAgentId = $request->integer('agent_id') ?: null;
        $selectedAgentId = User::whereKey($selectedAgentId)->exists() ? $selectedAgentId : null;
        $agentOptions = User::where('role', '!=', 'membre')
            ->where('status', true)
            ->orderBy('name')
            ->get(['id', 'name', 'postnom', 'prenom', 'role']);

        $members = User::where('role', 'membre');
        $activeMembers = (clone $members)->where('status', true)->count();
        $inactiveMembers = (clone $members)->where('status', false)->count();

        $summary = [
            ['label' => 'Total membres', 'value' => (clone $members)->count(), 'icon' => 'bx-group', 'url' => route('rapports.clients'), 'source' => 'Nombre d’utilisateurs dont le rôle est membre.'],
            ['label' => 'Membres actifs', 'value' => $activeMembers, 'icon' => 'bx-user-check', 'url' => route('rapports.clients'), 'source' => 'Membres avec le statut actif.'],
            ['label' => 'Membres inactifs', 'value' => $inactiveMembers, 'icon' => 'bx-user-x', 'url' => route('rapports.clients'), 'source' => 'Membres avec le statut inactif.'],
            ['label' => 'Nouveaux membres du mois', 'value' => (clone $members)->where('created_at', '>=', $monthStart)->count(), 'icon' => 'bx-user-plus', 'url' => route('rapports.clients'), 'source' => 'Membres créés depuis le premier jour du mois en cours.'],
            ['label' => 'Total épargne', 'value' => $this->moneyByCurrency(Account::where('type', 'savings'), 'balance'), 'icon' => 'bx-piggy-bank', 'url' => route('member.accounts'), 'is_money' => true, 'source' => 'Somme des soldes des comptes de type épargne, regroupée par devise.'],
            ['label' => 'Solde caisse', 'value' => $this->moneyByCurrency(MainCashRegister::query(), 'balance'), 'icon' => 'bx-wallet', 'url' => route('cash.register'), 'is_money' => true, 'source' => 'Somme des soldes enregistrés dans la caisse centrale, regroupée par devise.'],
            ['label' => 'Solde agents', 'value' => $this->moneyByCurrency(AgentAccount::query(), 'balance'), 'icon' => 'bx-briefcase-alt-2', 'url' => route('agent.dashboard'), 'is_money' => true, 'source' => 'Somme des soldes disponibles dans les caisses agents, regroupée par devise.'],
            ['label' => 'Encours crédits', 'value' => $this->moneyByCurrency(Credit::where('is_paid', false)), 'icon' => 'bx-credit-card', 'url' => route('report.credit.overview'), 'is_money' => true, 'source' => 'Somme des montants des crédits non soldés, regroupée par devise.'],
            ['label' => 'Crédits actifs', 'value' => Credit::where('is_paid', false)->count(), 'icon' => 'bx-list-check', 'url' => route('report.credit.overview'), 'source' => 'Nombre de crédits dont le champ soldé est encore non.'],
            ['label' => 'Collecté aujourd’hui', 'value' => $this->moneyByCurrency(Transaction::whereIn('type', $depositTypes)->whereDate('created_at', $today)), 'icon' => 'bx-trending-up', 'url' => route('rapports.transactions'), 'is_money' => true, 'source' => 'Somme des transactions de dépôt et mise quotidienne créées aujourd’hui.'],
            ['label' => 'Remboursé aujourd’hui', 'value' => $this->repaymentMoneyByCurrency(Repayment::whereDate('paid_date', $today)), 'icon' => 'bx-refresh', 'url' => route('report.repayments'), 'is_money' => true, 'source' => 'Somme des échéances marquées payées aujourd’hui, regroupée par devise du crédit.'],
            ['label' => 'Agents actifs', 'value' => User::where('role', 'recouvreur')->where('status', true)->count(), 'icon' => 'bx-user-voice', 'url' => route('reports.agent-performance'), 'source' => 'Nombre d’utilisateurs recouvreurs avec le statut actif.'],
        ];

        $depositQuery = $this->applyTransactionFilters(
            Transaction::whereIn('type', $depositTypes)->whereDate('created_at', $today),
            $selectedCurrency,
            $selectedAgentId
        );
        $withdrawalQuery = $this->applyTransactionFilters(
            Transaction::whereIn('type', $withdrawalTypes)->whereDate('created_at', $today),
            $selectedCurrency,
            $selectedAgentId
        );
        $transferQuery = $this->applyTransactionFilters(
            Transaction::whereIn('type', ['transfert', 'virement_caisse'])->whereDate('created_at', $today),
            $selectedCurrency,
            $selectedAgentId
        );
        $repaymentQuery = $this->applyRepaymentFilters(
            Repayment::whereDate('paid_date', $today),
            $selectedCurrency,
            $selectedAgentId
        );
        $creditGrantedQuery = $this->applyCreditFilters(
            Credit::whereDate('created_at', $today),
            $selectedCurrency,
            $selectedAgentId
        );
        $newMembersQuery = (clone $members)->whereDate('created_at', $today);

        if ($selectedAgentId) {
            $newMembersQuery->where('agent_id', $selectedAgentId);
        }

        $activity = [
            ['label' => 'Dépôts', 'count' => (clone $depositQuery)->count(), 'amount' => $this->moneyByCurrency($depositQuery)],
            ['label' => 'Retraits', 'count' => (clone $withdrawalQuery)->count(), 'amount' => $this->moneyByCurrency($withdrawalQuery)],
            ['label' => 'Transferts', 'count' => (clone $transferQuery)->count(), 'amount' => $this->moneyByCurrency($transferQuery)],
            ['label' => 'Remboursements', 'count' => (clone $repaymentQuery)->count(), 'amount' => $this->repaymentMoneyByCurrency($repaymentQuery)],
            ['label' => 'Crédits accordés', 'count' => (clone $creditGrantedQuery)->count(), 'amount' => $this->moneyByCurrency($creditGrantedQuery)],
            ['label' => 'Nouveaux membres', 'count' => $newMembersQuery->count(), 'amount' => null],
        ];

        $inactiveBuckets = $this->inactiveMemberBuckets();
        $creditAlerts = $this->creditAlerts();
        $agents = $this->agentPerformance($depositTypes);
        $trends = $this->trends($depositTypes, $withdrawalTypes);
        $financialAlerts = $this->financialAlerts($depositTypes, $withdrawalTypes);
        $analysis = $this->analysis($trends, $inactiveBuckets, $creditAlerts, $financialAlerts);
        $priorities = $this->priorities($inactiveBuckets, $creditAlerts, $financialAlerts);

        return view('decision-dashboard', compact(
            'summary',
            'activity',
            'inactiveBuckets',
            'creditAlerts',
            'agents',
            'trends',
            'financialAlerts',
            'analysis',
            'priorities',
            'selectedCurrency',
            'selectedAgentId',
            'agentOptions'
        ));
    }

    private function moneyByCurrency($query, string $column = 'amount'): array
    {
        return (clone $query)
            ->select('currency', DB::raw("SUM({$column}) as total"))
            ->groupBy('currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount) => (float) $amount)
            ->toArray();
    }

    private function repaymentMoneyByCurrency($query, string $column = 'total_due'): array
    {
        return (clone $query)
            ->join('credits', 'repayments.credit_id', '=', 'credits.id')
            ->select('credits.currency', DB::raw("SUM(repayments.{$column}) as total"))
            ->groupBy('credits.currency')
            ->pluck('total', 'currency')
            ->map(fn ($amount) => (float) $amount)
            ->toArray();
    }

    private function applyTransactionFilters($query, ?string $currency, ?int $agentId)
    {
        $query->whereNotNull('account_id');

        if ($currency) {
            $query->where('currency', $currency);
        }

        if ($agentId) {
            $query->whereHas('account.user', fn ($user) => $user->where('agent_id', $agentId));
        }

        return $query;
    }

    private function applyCreditFilters($query, ?string $currency, ?int $agentId)
    {
        if ($currency) {
            $query->where('currency', $currency);
        }

        if ($agentId) {
            $query->where('agent_id', $agentId);
        }

        return $query;
    }

    private function applyRepaymentFilters($query, ?string $currency, ?int $agentId)
    {
        if ($currency || $agentId) {
            $query->whereHas('credit', function ($credit) use ($currency, $agentId) {
                if ($currency) {
                    $credit->where('currency', $currency);
                }

                if ($agentId) {
                    $credit->where('agent_id', $agentId);
                }
            });
        }

        return $query;
    }

    private function inactiveMemberBuckets(): array
    {
        $base = User::where('role', 'membre')->where('status', true);
        $buckets = [
            '30 jours' => [30, 59],
            '60 jours' => [60, 89],
            '90 jours' => [90, 179],
            '+180 jours' => [180, null],
        ];

        return collect($buckets)->map(function ($range, $label) use ($base) {
            [$min, $max] = $range;
            $query = (clone $base)->where(function ($memberQuery) use ($min, $max) {
                $memberQuery->where(function ($q) use ($min, $max) {
                    $q->whereNotNull('last_transaction_at')
                        ->where('last_transaction_at', '<=', now()->subDays($min));

                    if ($max) {
                        $q->where('last_transaction_at', '>', now()->subDays($max + 1));
                    }
                })->orWhere(function ($q) use ($min, $max) {
                    $q->whereNull('last_transaction_at')
                        ->where('created_at', '<=', now()->subDays($min));

                    if ($max) {
                        $q->where('created_at', '>', now()->subDays($max + 1));
                    }
                });
            });

            return [
                'label' => $label,
                'count' => (clone $query)->count(),
                'members' => $query->with('agent')->latest('last_transaction_at')->limit(6)->get(),
            ];
        })->values()->all();
    }

    private function creditAlerts(): array
    {
        $todayDue = Repayment::with(['credit.user', 'credit.agent'])
            ->whereDate('due_date', today())
            ->where('is_paid', false)
            ->limit(8)
            ->get();

        $overdueBase = Repayment::with(['credit.user', 'credit.agent'])
            ->where('due_date', '<', today())
            ->where('is_paid', false);

        $ranges = [
            '1 à 7 jours' => [1, 7],
            '8 à 30 jours' => [8, 30],
            '31 à 90 jours' => [31, 90],
            '+90 jours' => [91, null],
        ];

        $overdue = collect($ranges)->map(function ($range, $label) use ($overdueBase) {
            [$min, $max] = $range;
            $from = today()->subDays($max ?: 3650);
            $to = today()->subDays($min);
            $query = (clone $overdueBase)->whereBetween('due_date', [$from, $to]);

            return [
                'label' => $label,
                'count' => (clone $query)->count(),
                'items' => $query->orderBy('due_date')->limit(6)->get(),
            ];
        })->values()->all();

        return compact('todayDue', 'overdue');
    }

    private function financialAlerts(array $depositTypes, array $withdrawalTypes): array
    {
        $alerts = [];
        $cashBalances = MainCashRegister::all();

        foreach ($cashBalances as $cash) {
            if ((float) $cash->balance < 0) {
                $alerts[] = "Caisse {$cash->currency} insuffisante.";
            }
        }

        $todayCollections = Transaction::whereIn('type', $depositTypes)->whereDate('created_at', today())->sum('amount');
        $previousCollections = Transaction::whereIn('type', $depositTypes)
            ->whereBetween('created_at', [now()->subDays(8), now()->subDay()])
            ->sum('amount') / 7;

        if ($previousCollections > 0 && $todayCollections < ($previousCollections * 0.7)) {
            $alerts[] = 'Baisse inhabituelle des collectes par rapport aux 7 derniers jours.';
        }

        $todayWithdrawals = Transaction::whereIn('type', $withdrawalTypes)->whereDate('created_at', today())->sum('amount');
        $previousWithdrawals = Transaction::whereIn('type', $withdrawalTypes)
            ->whereBetween('created_at', [now()->subDays(8), now()->subDay()])
            ->sum('amount') / 7;

        if ($previousWithdrawals > 0 && $todayWithdrawals > ($previousWithdrawals * 1.4)) {
            $alerts[] = 'Augmentation anormale des retraits aujourd’hui.';
        }

        return $alerts;
    }

    private function agentPerformance(array $depositTypes)
    {
        return User::where('role', 'recouvreur')
            ->withCount([
                'clients',
                'clients as active_clients_count' => fn ($q) => $q->where('status', true),
                'clients as inactive_clients_count' => fn ($q) => $q->where('status', false),
            ])
            ->withSum(['transactions as collection_today' => fn ($q) => $q->whereIn('type', $depositTypes)->whereDate('created_at', today())], 'amount')
            ->withSum(['transactions as collection_month' => fn ($q) => $q->whereIn('type', $depositTypes)->where('created_at', '>=', now()->startOfMonth())], 'amount')
            ->withCount([
                'managedCredits as credits_followed_count' => fn ($q) => $q->where('is_paid', false),
                'managedCredits as overdue_credits_count' => fn ($q) => $q->where('is_paid', false)->where('due_date', '<', today()),
            ])
            ->orderByDesc('collection_month')
            ->limit(10)
            ->get();
    }

    private function trends(array $depositTypes, array $withdrawalTypes): array
    {
        $labels = collect(range(29, 0))->map(fn ($days) => now()->subDays($days)->format('d/m'))->values();

        $seriesFor = function ($query) {
            $rows = (clone $query)
                ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
                ->where('created_at', '>=', now()->subDays(29)->startOfDay())
                ->groupBy('day')
                ->pluck('total', 'day');

            return collect(range(29, 0))->map(function ($days) use ($rows) {
                $key = now()->subDays($days)->toDateString();

                return round((float) ($rows[$key] ?? 0), 2);
            })->values();
        };

        $repaymentRows = Repayment::query()
            ->selectRaw('DATE(paid_date) as day, SUM(total_due) as total')
            ->whereNotNull('paid_date')
            ->where('paid_date', '>=', now()->subDays(29)->startOfDay())
            ->groupBy('day')
            ->pluck('total', 'day');

        $repaymentSeries = collect(range(29, 0))->map(function ($days) use ($repaymentRows) {
            $key = now()->subDays($days)->toDateString();

            return round((float) ($repaymentRows[$key] ?? 0), 2);
        })->values();

        return [
            'labels' => $labels,
            'collectes' => $seriesFor(Transaction::whereIn('type', $depositTypes)),
            'retraits' => $seriesFor(Transaction::whereIn('type', $withdrawalTypes)),
            'remboursements' => $repaymentSeries,
            'credits' => $seriesFor(Transaction::where('type', 'octroi_de_credit')),
        ];
    }

    private function analysis(array $trends, array $inactiveBuckets, array $creditAlerts, array $financialAlerts): array
    {
        $messages = [];
        $last7 = collect($trends['collectes'])->slice(-7)->sum();
        $previous7 = collect($trends['collectes'])->slice(-14, 7)->sum();

        if ($previous7 > 0) {
            $change = round((($last7 - $previous7) / $previous7) * 100, 1);
            $messages[] = $change < 0
                ? "La collecte baisse de {$change}% par rapport aux 7 jours précédents."
                : "La collecte progresse de {$change}% par rapport aux 7 jours précédents.";
        }

        $inactiveTotal = collect($inactiveBuckets)->sum('count');
        if ($inactiveTotal > 0) {
            $messages[] = "{$inactiveTotal} membres actifs administrativement n’ont pas réalisé d’opération récente.";
        }

        $overdueTotal = collect($creditAlerts['overdue'])->sum('count');
        if ($overdueTotal > 0) {
            $messages[] = "{$overdueTotal} échéances de crédit sont en retard et demandent un suivi.";
        }

        foreach ($financialAlerts as $alert) {
            $messages[] = $alert;
        }

        return $messages ?: ['Aucune anomalie majeure détectée avec les données actuellement disponibles.'];
    }

    private function priorities(array $inactiveBuckets, array $creditAlerts, array $financialAlerts): array
    {
        $priorities = [];
        $inactive90 = collect($inactiveBuckets)->whereIn('label', ['90 jours', '+180 jours'])->sum('count');
        $todayDue = $creditAlerts['todayDue']->count();
        $overdueTotal = collect($creditAlerts['overdue'])->sum('count');

        if ($inactive90 > 0) {
            $priorities[] = "Visiter {$inactive90} clients inactifs depuis au moins 90 jours.";
        }

        if ($overdueTotal > 0) {
            $priorities[] = "Contacter {$overdueTotal} clients avec échéances en retard.";
        }

        if ($todayDue > 0) {
            $priorities[] = "Relancer {$todayDue} échéances arrivant aujourd’hui.";
        }

        foreach ($financialAlerts as $alert) {
            $priorities[] = $alert;
        }

        return $priorities ?: ['Continuer le suivi courant : aucune action critique automatique détectée.'];
    }
}
