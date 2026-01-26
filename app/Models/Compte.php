<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Compte extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'intitule',
        'type',
        'parent_id',
        'level',
        'sous_classe',
        'currency_type',
        'is_active',
        'description'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== RELATIONS ====================

    /**
     * Compte parent (hiérarchie)
     */
    public function parent()
    {
        return $this->belongsTo(Compte::class, 'parent_id');
    }

    /**
     * Comptes enfants (sous-comptes)
     */
    public function children()
    {
        return $this->hasMany(Compte::class, 'parent_id');
    }

    /**
     * Tous les journaux liés à ce compte
     */
    public function journals()
    {
        return $this->hasMany(Journal::class, 'compte_id');
    }

    /**
     * Journaux en USD uniquement
     */
    public function journalsUSD()
    {
        return $this->journals()->where('devise', 'USD');
    }

    /**
     * Journaux en CDF uniquement
     */
    public function journalsCDF()
    {
        return $this->journals()->where('devise', 'CDF');
    }

    // ==================== SCOPES ====================

    /**
     * Scope: Comptes actifs uniquement
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Par niveau hiérarchique
     */
    public function scopeByLevel(Builder $query, int $level): Builder
    {
        return $query->where('level', $level);
    }

    /**
     * Scope: Par type de compte
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Comptes de classe (niveau 1)
     */
    public function scopeClasses(Builder $query): Builder
    {
        return $query->where('level', 1);
    }

    /**
     * Scope: Comptes détaillés (niveau 3)
     */
    public function scopeDetailed(Builder $query): Builder
    {
        return $query->where('level', 3);
    }

    /**
     * Scope: Comptes mono-devise spécifique
     */
    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where(function ($q) use ($currency) {
            $q->where('currency_type', $currency)
                ->orWhere('currency_type', 'multi');
        });
    }

    // ==================== MÉTHODES ====================

    /**
     * Calculer le solde du compte pour une devise et période données
     * 
     * @param string $devise USD ou CDF
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return float
     */
    public function getSolde(string $devise, ?string $dateDebut = null, ?string $dateFin = null): float
    {
        $query = $this->journals()->where('devise', $devise);

        if ($dateDebut && $dateFin) {
            $query->whereBetween('date_operation', [$dateDebut, $dateFin]);
        } elseif ($dateDebut) {
            $query->where('date_operation', '>=', $dateDebut);
        } elseif ($dateFin) {
            $query->where('date_operation', '<=', $dateFin);
        }

        $totalDebit = $query->sum('montant_debit');
        $totalCredit = $query->sum('montant_credit');

        // Le sens du solde dépend du type de compte
        if (in_array($this->type, ['Actif', 'Charge'])) {
            return $totalDebit - $totalCredit; // Solde débiteur
        } else {
            return $totalCredit - $totalDebit; // Solde créditeur
        }
    }

    /**
     * Obtenir le chemin hiérarchique complet du compte
     * Exemple: "5 > 57 > 571 Caisse centrale USD"
     * 
     * @return string
     */
    public function getHierarchyPath(): string
    {
        $path = [];
        $current = $this;

        while ($current) {
            array_unshift($path, $current->code . ' ' . $current->intitule);
            $current = $current->parent;
        }

        return implode(' > ', $path);
    }

    /**
     * Vérifier si le compte peut être utilisé pour des écritures
     * (généralement, seuls les comptes de niveau 3 sont utilisables)
     * 
     * @return bool
     */
    public function isUsable(): bool
    {
        return $this->level === 3 && $this->is_active;
    }

    /**
     * Obtenir tous les descendants (récursif)
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAllDescendants()
    {
        $descendants = collect();

        foreach ($this->children as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }

        return $descendants;
    }

    /**
     * Calculer le solde consolidé (compte + tous ses descendants)
     * 
     * @param string $devise
     * @param string|null $dateDebut
     * @param string|null $dateFin
     * @return float
     */
    public function getSoldeConsolide(string $devise, ?string $dateDebut = null, ?string $dateFin = null): float
    {
        $solde = $this->getSolde($devise, $dateDebut, $dateFin);

        foreach ($this->getAllDescendants() as $descendant) {
            $solde += $descendant->getSolde($devise, $dateDebut, $dateFin);
        }

        return $solde;
    }

    /**
     * Formater pour affichage dans un select (avec hiérarchie visuelle)
     * 
     * @return string
     */
    public function getDisplayName(): string
    {
        $indent = str_repeat('—', $this->level - 1);
        return $indent . ' ' . $this->code . ' - ' . $this->intitule;
    }
}
