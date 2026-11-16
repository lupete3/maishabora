<?php

namespace App\Rules;

use App\Models\User;
use App\Support\MemberCode;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Refuse les mots de passe trop faciles à deviner pour un compte financier :
 * caractère répété (111111), suite (123456, 654321, abcdef), mots courants,
 * date de naissance, téléphone ou code membre du client.
 *
 * La longueur minimale est vérifiée à part, dans ChangePasswordRequest.
 */
class ClientPassword implements ValidationRule
{
    private const COMMON = [
        'password', 'motdepasse', 'azerty', 'azertyuiop', 'qwerty', 'maishabora',
        '123123', '112233', '121212', '123321', '102030', '000000', '1234',
    ];

    public function __construct(private ?User $user = null)
    {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $value === '') {
            return;
        }

        if (preg_match('/^(.)\1+$/u', $value)) {
            $fail('Le mot de passe ne peut pas être un même caractère répété (ex. 111111).');

            return;
        }

        if (self::isSequence($value)) {
            $fail('Le mot de passe ne peut pas être une suite (ex. 123456 ou 654321).');

            return;
        }

        if (in_array(mb_strtolower($value), self::COMMON, true)) {
            $fail('Ce mot de passe est trop courant. Choisissez-en un autre.');

            return;
        }

        if ($this->user && $this->containsPersonalData($value)) {
            $fail('Le mot de passe ne doit contenir ni votre date de naissance, ni votre téléphone, ni votre code membre.');
        }
    }

    /**
     * 123456, 654321, abcdef, fedcba → vrai
     */
    private static function isSequence(string $value): bool
    {
        $value = strtolower($value);

        if (strlen($value) < 3 || (! ctype_digit($value) && ! ctype_alpha($value))) {
            return false;
        }

        $ascending = $descending = true;

        for ($i = 1; $i < strlen($value); $i++) {
            $step = ord($value[$i]) - ord($value[$i - 1]);
            $ascending = $ascending && $step === 1;
            $descending = $descending && $step === -1;
        }

        return $ascending || $descending;
    }

    private function containsPersonalData(string $value): bool
    {
        $digits = preg_replace('/\D/', '', $value);
        $forbidden = [];

        // Date de naissance : 15031990, 19900315, 150390
        if ($this->user->date_naissance) {
            try {
                $birth = Carbon::parse($this->user->date_naissance);
                array_push($forbidden, $birth->format('dmY'), $birth->format('Ymd'), $birth->format('dmy'));
            } catch (\Throwable) {
                // date mal saisie en base : on ignore ce contrôle
            }
        }

        // 6 derniers chiffres du téléphone
        $phone = preg_replace('/\D/', '', (string) $this->user->telephone);
        if (strlen($phone) >= 6) {
            $forbidden[] = substr($phone, -6);
        }

        // Code membre complet, et numéro court sur 6 chiffres (000258)
        if ($this->user->code) {
            $forbidden[] = $this->user->code;

            $sequence = MemberCode::sequence($this->user->code);
            if ($sequence !== null) {
                $forbidden[] = str_pad((string) $sequence, MemberCode::SHORT_DIGITS, '0', STR_PAD_LEFT);
            }
        }

        if ($digits !== '') {
            foreach ($forbidden as $item) {
                if ($item !== '' && str_contains($digits, $item)) {
                    return true;
                }
            }
        }

        $short = MemberCode::short($this->user->code);

        return $short !== null && str_contains(strtoupper($value), $short);
    }
}
