<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIReportService
{
    protected $baseUrl;
    protected $apiKey;
    protected $model;

    public function __construct()
    {
        $this->baseUrl = env('AI_BASE_URL', 'https://openrouter.ai/api/v1/chat/completions');
        $this->apiKey = env('AI_API_KEY', env('OPENROUTER_API_KEY'));
        $this->model = env('AI_MODEL', 'llama-3.3-70b-versatile');
    }

    public function summarizeTransactions($transactions, $type = 'général')
    {
        if ($transactions->isEmpty()) {
            return "Aucune opération de type $type enregistrée pour cette période.";
        }

        $totals = $this->sumByCurrency($transactions, 'total_amount');

        $text = $transactions->map(function ($t) {
            $account = optional($t->account)->name ?? "Compte #{$t->account_id}";
            return "Compte {$account} : total {$t->total_amount} {$t->currency}";
        })->join('. ');

        $prompt = match ($type) {
            'depots' => "Fais un résumé clair des dépôts suivants.\nTotaux globaux : $totals.\nDétails par compte : $text.\nAnalyse les volumes par devise (CDF vs USD).",
            'retraits' => "Fais un résumé clair des retraits suivants.\nTotaux globaux : $totals.\nDétails par compte : $text.\nIdentifie les devises les plus sollicitées.",
            'credits' => "Fais un résumé des crédits octroyés.\nTotaux globaux : $totals.\nDétails : $text.\nCommente la répartition entre devises.",
            default => "Résume ces opérations ($totals) : $text"
        };

        return $this->callAI($prompt);
    }

    public function summarizeGlobal($deposits, $withdrawals, $credits)
    {
        $prompt = "En tant qu'expert en microfinance, fais une analyse globale de la santé financière actuelle basée sur ces flux :\n" .
            "- Dépôts : " . $this->sumByCurrency($deposits, 'total_amount') . "\n" .
            "- Retraits : " . $this->sumByCurrency($withdrawals, 'total_amount') . "\n" .
            "- Crédits : " . $this->sumByCurrency($credits, 'total_amount') . "\n\n" .
            "Analyse la liquidité par devise (USD et CDF), la balance des flux et donne 3 conseils stratégiques adaptés.";

        return $this->callAI($prompt, "Tu es un CFO de microfinance expert en stratégie.");
    }

    public function summarizeCreditPerformance($credits, $delays)
    {
        $prompt = "Analyse la performance du portefeuille crédit :\n" .
            "- Crédits en cours : " . $credits->count() . " dossiers pour un total de " . $this->sumByCurrency($credits, 'amount') . "\n" .
            "- Retards détectés : " . $delays->count() . " remboursements en retard pour un montant total de " . $this->sumByCurrency($delays, 'total_due') . "\n\n" .
            "Évalue le risque du portefeuille (PAR) séparément pour USD et CDF. Propose des mesures de recouvrement.";

        return $this->callAI($prompt, "Tu es un gestionnaire de recouvrement et analyste de risque de crédit.");
    }

    public function summarizeClientInsights($newClients, $accounts)
    {
        $prompt = "Analyse l'activité des membres et des comptes :\n" .
            "- Nouveaux membres : " . $newClients->count() . "\n" .
            "- État des comptes : " . $accounts->count() . " comptes actifs.\n" .
            "- Soldes totaux : " . $this->sumByCurrency($accounts, 'balance') . "\n\n" .
            "Analyse la croissance et les tendances d'épargne par devise.";

        return $this->callAI($prompt, "Tu es un expert en marketing et relation client pour le secteur bancaire.");
    }

    public function summarizeSalesPerformance($cards)
    {
        $prompt = "Analyse les ventes de carnets/cartes membres :\n" .
            "- Cartes vendues : " . $cards->count() . " pour un revenu total de " . $this->sumByCurrency($cards, 'price') . "\n\n" .
            "Évalue l'efficacité commerciale par devise et propose des idées pour augmenter les adhésions.";

        return $this->callAI($prompt, "Tu es un consultant en développement commercial.");
    }

    protected function sumByCurrency($collection, $column)
    {
        if ($collection->isEmpty())
            return "Aucun montant.";

        return $collection->groupBy('currency')->map(function ($items, $currency) use ($column) {
            return number_format($items->sum($column), 2) . " " . $currency;
        })->join(' | ');
    }

    protected function formatDataForGlobal($data)
    {
        return $this->sumByCurrency($data, 'total_amount');
    }

    protected function callAI($prompt, $systemPrompt = 'Tu es un expert en gestion de microfinance. Rédige des résumés simples et professionnels.')
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

        if ($response->failed()) {
            return "Désolé, l'IA n'est pas disponible pour le moment.";
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'Résumé non généré.';
    }
}

