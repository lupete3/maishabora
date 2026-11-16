<?php

namespace App\Http\Requests\Api\V1\Client;

use App\Rules\ClientPassword;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation du changement de mot de passe depuis l'application mobile.
 */
class ChangePasswordRequest extends FormRequest
{
    /** Longueur minimale du nouveau mot de passe. */
    public const MIN_LENGTH = 6;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Mot de passe actuel, ou code provisoire remis en agence
            'current_password' => ['required', 'string', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:' . self::MIN_LENGTH,
                'max:64',
                'confirmed',                 // exige password_confirmation identique
                'different:current_password',
                new ClientPassword($this->user()),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => 'Le mot de passe actuel est obligatoire.',
            'password.required' => 'Le nouveau mot de passe est obligatoire.',
            'password.min' => 'Le mot de passe doit contenir au moins ' . self::MIN_LENGTH . ' caractères.',
            'password.max' => 'Le mot de passe est trop long (64 caractères maximum).',
            'password.confirmed' => 'La confirmation ne correspond pas au nouveau mot de passe.',
            'password.different' => 'Le nouveau mot de passe doit être différent de l\'actuel.',
        ];
    }
}
