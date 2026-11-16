<?php

namespace Tests\Unit;

use App\Support\MemberCode;
use PHPUnit\Framework\TestCase;

/**
 * Tests des fonctions de formatage du code membre (sans base de données).
 */
class MemberCodeTest extends TestCase
{
    public function test_extrait_le_numero_sequentiel_du_code_complet(): void
    {
        $this->assertSame(258, MemberCode::sequence('3420250000000258'));
        $this->assertSame(1234567, MemberCode::sequence('3420260001234567'));
        $this->assertNull(MemberCode::sequence('ABC123'));
        $this->assertNull(MemberCode::sequence(null));
    }

    public function test_formate_le_code_court(): void
    {
        $this->assertSame('MB000258', MemberCode::short('3420250000000258'));
        $this->assertSame('MB000001', MemberCode::short('3420250000000001'));
        // Au-delà de 999 999 membres, le code court s'allonge simplement
        $this->assertSame('MB1234567', MemberCode::short('3420260001234567'));
        // Code non standard : pas de code court
        $this->assertNull(MemberCode::short('CODE-MANUEL-12'));
    }

    public function test_toutes_les_ecritures_donnent_la_meme_forme_canonique(): void
    {
        foreach (['258', '000258', 'MB000258', 'mb 258', ' MB-000-258 ', '3420250000000258'] as $saisie) {
            $this->assertSame('MB000258', MemberCode::canonical($saisie), "Saisie : {$saisie}");
        }
    }
}
