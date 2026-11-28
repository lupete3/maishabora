<?php

namespace App\Services;

use App\Livewire\Members\MemberDetails;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Carnets payés sans retenu de la première mise (anomalie du 12/10 au 06/11/2026),
 * classés selon ce qu'il reste à faire. Lecture seule.
 *
 * Au retrait d'un carnet dont le retenu n'a pas été fait, MemberDetails::submitRetraitCarte()
 * le prélève (écriture « Entree Retenu de la carte #id … » sur le compte des retenus),
 * mais ne met pas le marqueur first_mise_retained à 1.
 */
class FirstMiseRetentionAudit
{
    public const ACTIF = 'actif';
    public const RETIRE_RETENU_PRIS = 'retire_retenu_pris';
    public const RETIRE_SANS_RETENU = 'retire_sans_retenu';
    public const INACTIF_SANS_RETRAIT = 'inactif_sans_retrait';

    public const LIBELLES = [
        self::ACTIF => 'Actif : retenu prélevé automatiquement au retrait (rien à faire)',
        self::RETIRE_RETENU_PRIS => 'Retiré : retenu pris au retrait (marqueur à corriger)',
        self::RETIRE_SANS_RETENU => 'Retiré : retenu NON pris (à examiner)',
        self::INACTIF_SANS_RETRAIT => 'Inactif sans opération de retrait enregistrée (à examiner)',
    ];

    /**
     * @return Collection<int, object> carnets avec : id, code, client, currency, subscription_amount,
     *                                  premier_versement, mises_payees, is_active, statut
     */
    public function carnets(string $depuis): Collection
    {
        $carnets = DB::table('membership_cards as c')
            ->join('users as m', 'm.id', '=', 'c.member_id')
            ->whereNull('c.cancelled_at')
            ->where('c.card_type', 'epargne')
            ->where('c.first_mise_retained', false)
            ->select(['c.id', 'c.code', 'c.currency', 'c.subscription_amount', 'c.is_active', 'm.code as client_code', 'm.name', 'm.postnom'])
            ->selectRaw('(SELECT MIN(d.updated_at) FROM daily_contributions d WHERE d.membership_card_id = c.id AND d.is_paid = 1) AS premier_versement')
            ->selectRaw('(SELECT COUNT(*) FROM daily_contributions d WHERE d.membership_card_id = c.id AND d.is_paid = 1) AS mises_payees')
            ->get()
            ->filter(fn ($c) => $c->premier_versement !== null && $c->premier_versement >= $depuis)
            ->sortBy('premier_versement')
            ->values();

        return $carnets->map(function ($c) {
            $c->client = trim("{$c->client_code} {$c->name} {$c->postnom}");
            $c->statut = match (true) {
                (bool) $c->is_active => self::ACTIF,
                $this->retenuPrisAuRetrait($c->id) => self::RETIRE_RETENU_PRIS,
                $this->retraitEnregistre($c->id) => self::RETIRE_SANS_RETENU,
                default => self::INACTIF_SANS_RETRAIT,
            };

            return $c;
        });
    }

    /** Opération « Retrait carnet #id » côté compte du membre. */
    private function retraitEnregistre(int $carteId): bool
    {
        return DB::table('transactions')
            ->where('type', 'retrait_carte_adhesion')
            ->whereNotNull('account_id')
            ->where('description', 'like', "Retrait carnet #{$carteId} %")
            ->exists();
    }

    /** Écriture du retenu sur le compte des retenus pour ce carnet. */
    private function retenuPrisAuRetrait(int $carteId): bool
    {
        return DB::table('transactions')
            ->where('user_id', MemberDetails::RETAINED_ACCOUNT_USER_ID)
            ->where('type', 'depot')
            ->where('description', 'like', "Entree Retenu de la carte #{$carteId} %")
            ->exists();
    }
}
