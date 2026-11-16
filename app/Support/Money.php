<?php

namespace App\Support;

/**
 * Format des montants envoyés à l'application mobile.
 *
 * Les montants partent en CHAÎNE avec 2 décimales ("1500.00"), jamais en
 * nombre à virgule : un float peut devenir 1499.9999999 côté téléphone.
 */
class Money
{
    public static function format(int|float|string|null $amount): ?string
    {
        if ($amount === null) {
            return null;
        }

        return number_format((float) $amount, 2, '.', '');
    }
}
