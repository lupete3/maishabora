<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MembershipCard extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'member_id',
        'user_id',
        'currency',
        'price',
        'subscription_amount',
        'start_date',
        'end_date',
        'is_active',
        'first_mise_retained',
        'card_type'
    ];

    // Membre propriétaire du carnet
    public function member()
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    // Agent responsable du carnet
    public function agent()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function contributions()
    {
        return $this->hasMany(DailyContribution::class);
    }

    public function getRemainingDaysAttribute()
    {
        return max(0, now()->diffInDays($this->end_date));
    }

    public function getTotalSavedAttribute()
    {
        return $this->contributions->where('is_paid', true)->sum('amount');
    }

    public function getUnpaidContributionsAttribute()
    {
        return $this->contributions->where('is_paid', false);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Récupère les carnets actifs présentant une anomalie de solde.
     * Une anomalie est détectée si (Total épargné - 1ère mise) > Solde disponible (Epargne ou Courant).
     */
    public static function getAnomalies($search = null)
    {
        $query = self::with([
            'member.accounts',
            'contributions' => function ($q) {
                $q->where('is_paid', true);
            }
        ])->active();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhereHas('member', function ($sub) use ($search) {
                        $sub->where('name', 'like', '%' . $search . '%')
                            ->orWhere('postnom', 'like', '%' . $search . '%')
                            ->orWhere('prenom', 'like', '%' . $search . '%')
                            ->orWhere('code', 'like', '%' . $search . '%');
                    });
            });
        }

        return $query->get()->filter(function ($card) {
            $totalSaved = $card->contributions->sum('amount');

            // On soutire la première mise (qui est pour la maison)
            $firstContribution = $card->contributions->sortBy('created_at')->first();
            if ($firstContribution) {
                $totalSaved -= $firstContribution->amount;
            }

            // On cherche le compte correspondant (priorité epargne/savings car c'est lié aux carnets)
            $account = $card->member->accounts
                ->where('currency', $card->currency)
                ->where('type', 'savings')
                ->first()
                ?? $card->member->accounts
                    ->where('currency', $card->currency)
                    ->where('type', 'current')
                    ->first();

            $balance = $account ? $account->balance : 0;

            // L'anomalie : montant déposé > solde disponible
            return $totalSaved > $balance;
        });
    }
}
