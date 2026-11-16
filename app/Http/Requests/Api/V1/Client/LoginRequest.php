<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valide les données envoyées par l'application mobile à la connexion.
 * Si une règle échoue, Laravel répond tout seul en JSON avec le code 422.
 */
class LoginRequest extends FormRequest
{
    /**
     * Tout le monde peut tenter de se connecter : le contrôle se fait
     * ensuite sur le code membre et le mot de passe.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Code membre complet (3420250000000258) ou court (258, MB000258)
            'code' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:4', 'max:255'],
            // Nom du téléphone, ex. "Tecno Spark 10" : un jeton par appareil
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code membre est obligatoire.',
            'password.required' => 'Le mot de passe est obligatoire.',
            'password.min' => 'Mot de passe invalide.',
            'device_name.required' => 'Le nom de l\'appareil est obligatoire.',
        ];
    }

    /**
     * Nettoie le code avant validation (espaces en trop).
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->code)) {
            $this->merge(['code' => trim($this->code)]);
        }
    }
}
