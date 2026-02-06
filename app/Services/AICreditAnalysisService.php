<?php

namespace App\Services;

use App\Models\LoanApplication;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AICreditAnalysisService
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

    public function analyze(LoanApplication $loan)
    {
        $loan->load(['user', 'business', 'cashflow', 'balance', 'ratios', 'securities']);

        // 1. Gathers financial data
        $financialData = [
            'montant_demande' => $loan->montant_demande,
            'duree_mois' => $loan->duree_mois,
            'cashflow' => $loan->cashflow ? $loan->cashflow->toArray() : 'Non renseigné',
            'balance' => $loan->balance ? $loan->balance->toArray() : 'Non renseigné',
            'ratios' => $loan->ratios ? $loan->ratios->toArray() : 'Non renseigné',
            'securities_total' => $loan->securities->sum('valeur_estimee'),
        ];

        // 2. Fetch member transaction history (last 6 months)
        $memberHistory = Transaction::where('user_id', $loan->user_id)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->orderBy('created_at', 'desc')
            ->get(['type', 'amount', 'currency', 'created_at', 'description']);

        // 3. Synthesize history for the prompt
        $historySummary = $memberHistory->take(20)->map(function ($t) {
            return "{$t->created_at->format('d/m/Y')}: {$t->type} de {$t->amount} {$t->currency} ({$t->description})";
        })->join('\n');

        $prompt = "Analyse le risque de crédit pour le dossier suivant en microfinance.\n\n" .
            "Détails du prêt :\n" . json_encode($financialData, JSON_PRETTY_PRINT) . "\n\n" .
            "Historique récent des transactions du membre (extraits) :\n" . $historySummary . "\n\n" .
            "Instructions :\n" .
            "1. Évalue la capacité de remboursement basée sur les flux de trésorerie et les ratios.\n" .
            "2. Analyse le comportement financier du membre (fréquence des dépôts, retraits, stabilité).\n" .
            "3. Identifie les points forts et les points de vigilance.\n" .
            "4. Donne une recommandation finale (Favorable, Favorable sous conditions, ou Défavorable).\n" .
            "Rédige en français, de manière professionnelle et concise.";

        // 4. Call AI
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un expert analyste de risque crédit en microfinance.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

        if ($response->failed()) {
            \Illuminate\Support\Facades\Log::error('OpenRouter API failure', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);
            return "Erreur API : " . ($response->json()['error']['message'] ?? 'Impossible de contacter le service AI.');
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'L’analyse IA n’a pas pu être générée (format de réponse inconnu).';
    }
}
