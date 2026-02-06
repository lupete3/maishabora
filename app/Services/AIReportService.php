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

        $text = $transactions->map(function ($t) {
            $account = optional($t->account)->name ?? "Compte #{$t->account_id}";
            return "Compte {$account} : total {$t->total_amount} {$t->currency}";
        })->join('. ');

        $prompt = match ($type) {
            'depots' => "Fais un résumé clair des dépôts journaliers suivants, en indiquant les montants totaux et tendances : $text",
            'retraits' => "Fais un résumé clair des retraits journaliers suivants, en indiquant les montants totaux et observations : $text",
            'credits' => "Fais un résumé clair des crédits octroyés aujourd'hui, en précisant les comptes et montants : $text",
            default => "Résume ces opérations de microfinance : $text"
        };

        return $this->callAI($prompt);
    }

    public function summarizeGlobal($deposits, $withdrawals, $credits)
    {
        $prompt = "En tant qu'expert en microfinance, fais une analyse globale de la journée basée sur ces données :\n" .
            "- Dépôts : " . $this->formatDataForGlobal($deposits) . "\n" .
            "- Retraits : " . $this->formatDataForGlobal($withdrawals) . "\n" .
            "- Crédits : " . $this->formatDataForGlobal($credits) . "\n\n" .
            "Donne une vue d'ensemble, identifie si la journée est positive en termes de liquidités et donne un conseil de gestion.";

        return $this->callAI($prompt);
    }

    protected function formatDataForGlobal($data)
    {
        if ($data->isEmpty())
            return "Aucune opération.";
        return $data->map(function ($t) {
            return "{$t->total_amount} {$t->currency}"; })->join(', ');
    }

    protected function callAI($prompt)
    {
        $response = Http::withOptions(['verify' => false])
            ->withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->baseUrl, [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un expert en gestion de microfinance. Rédige des résumés simples et professionnels.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

        if ($response->failed()) {
            return "Désolé, l'IA n'est pas disponible pour le moment.";
        }

        return $response->json()['choices'][0]['message']['content'] ?? 'Résumé non généré.';
    }
}

