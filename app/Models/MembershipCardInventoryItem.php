<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class MembershipCardInventoryItem extends Model
{
    protected $fillable = [
        'membership_card_id',
        'card_code',
        'member_code',
        'member_name',
        'currency',
        'subscription_amount',
        'paid_contributions_count',
        'theoretical_total',
        'physical_total',
        'difference',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'paid_contributions_count' => 'integer',
            // Pas une colonne : valeur ajoutée à l'affichage par
            // MembershipCardInventoryService::attachLastContributionDates()
            'last_contribution_at' => 'datetime',
            'subscription_amount' => 'decimal:2',
            'theoretical_total' => 'decimal:2',
            'physical_total' => 'decimal:2',
            'difference' => 'decimal:2',
        ];
    }

    /** Au-delà de ces durées (en jours) sans mise, la ligne est signalée. */
    public const DAYS_WARNING = 7;

    public const DAYS_DANGER = 30;

    /**
     * Nombre de jours entre la dernière mise encaissée et la date de référence.
     * Null si le carnet n'a aucune mise payée.
     */
    public function daysSinceLastContribution(?\DateTimeInterface $reference): ?int
    {
        return self::daysBetween($this->last_contribution_at, $reference);
    }

    public function lastContributionLevel(?\DateTimeInterface $reference): string
    {
        return self::levelForDays($this->daysSinceLastContribution($reference));
    }

    /**
     * Jours écoulés entre une date de dernière mise et une date de référence
     * (aujourd'hui en pratique). Utilisable aussi pour un carnet, avant le brouillon.
     */
    public static function daysBetween($lastContributionAt, $reference): ?int
    {
        if (empty($lastContributionAt) || empty($reference)) {
            return null;
        }

        $days = Carbon::parse($lastContributionAt)->startOfDay()
            ->diffInDays(Carbon::parse($reference)->startOfDay(), false);

        // Une mise encaissée après la date de référence compte comme « le jour même »
        return max(0, (int) $days);
    }

    /**
     * Couleur du badge : success (récent), warning (plus de 7 jours),
     * danger (plus de 30 jours ou aucune mise payée).
     */
    public static function levelForDays(?int $days): string
    {
        return match (true) {
            $days === null => 'danger',
            $days > self::DAYS_DANGER => 'danger',
            $days > self::DAYS_WARNING => 'warning',
            default => 'success',
        };
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(MembershipCardInventory::class, 'inventory_id');
    }

    public function card(): BelongsTo
    {
        return $this->belongsTo(MembershipCard::class, 'membership_card_id');
    }
}
