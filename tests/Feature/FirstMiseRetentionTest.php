<?php

namespace Tests\Feature;

use App\Models\MembershipCard;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Retenu de la première mise d'un carnet : décidé avant de marquer les mises payées,
 * quel que soit le nombre de mises du premier versement. Base SQLite en mémoire.
 */
class FirstMiseRetentionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('membership_cards', function (Blueprint $t) {
            $t->id();
            $t->string('code');
            $t->unsignedBigInteger('member_id');
            $t->string('currency')->default('CDF');
            $t->decimal('subscription_amount', 15, 2)->default(1000);
            $t->boolean('is_active')->default(true);
            $t->boolean('first_mise_retained')->default(false);
            $t->unsignedBigInteger('card_stock_item_id')->nullable();
            $t->timestamp('cancelled_at')->nullable();
            $t->timestamps();
        });
        Schema::create('daily_contributions', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('membership_card_id');
            $t->date('contribution_date');
            $t->decimal('amount', 15, 2);
            $t->boolean('is_paid')->default(false);
            $t->timestamps();
        });
    }

    private function carnet(): MembershipCard
    {
        $card = MembershipCard::withoutEvents(fn () => MembershipCard::forceCreate(['code' => 'K1', 'member_id' => 1]));
        for ($i = 0; $i < 31; $i++) {
            $card->contributions()->create(['contribution_date' => now()->addDays($i), 'amount' => 1000, 'is_paid' => false]);
        }

        return $card;
    }

    /** Même opération que MemberDetails::contribute() : les mises du lot reçoivent la date du jour. */
    private function verser(MembershipCard $card, int $mises): bool
    {
        $retenir = $card->fresh()->doitRetenirPremiereMise();

        $card->contributions()->where('is_paid', false)->orderBy('contribution_date')->take($mises)->get()
            ->each->update(['is_paid' => true, 'contribution_date' => now()]);

        if ($retenir) {
            $card->forceFill(['first_mise_retained' => true])->saveQuietly();
        }

        return $retenir;
    }

    public function test_premier_versement_d_une_seule_mise(): void
    {
        $card = $this->carnet();

        $this->assertTrue($this->verser($card, 1));
        $this->assertFalse($this->verser($card, 1)); // le versement suivant ne retient rien
    }

    public function test_premier_versement_de_plusieurs_mises(): void
    {
        foreach ([2, 5, 31] as $mises) {
            DB::table('daily_contributions')->delete();
            DB::table('membership_cards')->delete();
            $card = $this->carnet();

            $this->assertTrue($this->verser($card, $mises), "Premier versement de {$mises} mises : retenu attendu");
            $this->assertFalse($this->verser($card, 2));
        }
    }

    public function test_pas_de_retenu_si_deja_fait_ou_si_des_mises_sont_deja_payees(): void
    {
        $card = $this->carnet();
        $card->forceFill(['first_mise_retained' => true])->saveQuietly();
        $this->assertFalse($card->fresh()->doitRetenirPremiereMise());

        // Carnet antérieur à la règle : mises déjà payées sans retenu → on ne retient pas après coup
        $ancien = $this->carnet();
        $ancien->contributions()->orderBy('id')->first()->update(['is_paid' => true]);
        $this->assertFalse($ancien->fresh()->doitRetenirPremiereMise());
    }
}
