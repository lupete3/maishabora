<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIReportService
{
    protected $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';

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

        $response = Http::withOptions(['verify' => false]) // ⚠️ désactive SSL seulement en local
            ->withHeaders([
                'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
                'Content-Type'  => 'application/json',
            ])->post($this->baseUrl, [
                'model' => 'openai/gpt-4-turbo-preview',
                'messages' => [
                    ['role' => 'system', 'content' => 'Tu es un expert en gestion de microfinance. Rédige des résumés simples et professionnels.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

        return $response->json()['choices'][0]['message']['content'] ?? 'Résumé non généré.';
    }

}

