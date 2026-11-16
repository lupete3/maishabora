<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Filtres de l'historique des transactions.
 *
 * Exemple : /transactions?account_id=12&from=2026-10-01&to=2026-10-31&direction=debit&page=2
 */
class TransactionIndexRequest extends FormRequest
{
    public const DEFAULT_PER_PAGE = 20;

    public const MAX_PER_PAGE = 50;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Un compte précis ; sans ce filtre, tous les comptes du membre.
            // L'appartenance du compte au membre est vérifiée dans le contrôleur.
            'account_id' => ['nullable', 'integer'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'direction' => ['nullable', Rule::in(['credit', 'debit'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:' . self::MAX_PER_PAGE],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'La date de début doit avoir le format AAAA-MM-JJ.',
            'to.date_format' => 'La date de fin doit avoir le format AAAA-MM-JJ.',
            'to.after_or_equal' => 'La date de fin doit être postérieure à la date de début.',
            'direction.in' => 'Le sens doit être « credit » ou « debit ».',
            'per_page.max' => 'Au maximum ' . self::MAX_PER_PAGE . ' opérations par page.',
        ];
    }

    public function perPage(): int
    {
        return (int) ($this->validated('per_page') ?? self::DEFAULT_PER_PAGE);
    }
}
