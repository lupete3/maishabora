<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AIReportService
{
    protected $baseUrl = 'https://openrouter.ai/api/v1/chat/completions';

    public function summarizeTransactions($transactions, $type = 'général')
    {
        $text = $transactions->map(function ($t) {
            return "{$t->type} de {$t->amount} {$t->currency} par {$t->user->name} le {$t->created_at->format('d/m/Y')}";
        })->join('. ');

        $prompt = match ($type) {
            'depots' => "Fais un résumé clair et synthétique des dépôts suivants : $text",
            'retraits' => "Fais un résumé clair et synthétique des retraits suivants : $text",
            'credits' => "Analyse ces crédits et résume les montants, durées et situations de remboursement : $text",
            default => "Résume ces opérations de microfinance : $text"
        };

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . env('OPENROUTER_API_KEY'),
            'Content-Type'  => 'application/json',
        ])->post($this->baseUrl, [
            'model' => 'openai/gpt-4-turbo-preview',
            'messages' => [
                ['role' => 'system', 'content' => 'Tu es un expert en microfinance et comptabilité.'],
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        return $response->json()['choices'][0]['message']['content'] ?? 'Aucun résumé généré.';
    }
}

