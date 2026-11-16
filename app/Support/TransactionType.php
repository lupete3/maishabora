<?php

namespace App\Support;

use App\Models\Transaction;
use Illuminate\Support\Str;

/**
 * Libellé et sens (crédit / débit) des opérations, vus par le CLIENT.
 *
 * Les types sont des chaînes libres en base, parfois avec accents
 * (« dépôt » / « depot ») : on les compare sans accents ni majuscules.
 *
 * Le montant est toujours enregistré en positif ; c'est le type qui dit
 * si l'argent entre (credit) ou sort (debit) du compte du membre.
 * Table établie à partir du code qui crée les transactions sur les
 * comptes membres (MemberDetails, GrantCredit, ManageRepayments,
 * MemberTransfer, CurrencyConversion, PayrollComponent, TermDepositService…).
 */
class TransactionType
{
    public const CREDIT = 'credit';

    public const DEBIT = 'debit';

    /**
     * type normalisé => [libellé, sens]
     * Vérifiée le 2026-11-16 contre la liste des types présents en
     * production sur des comptes membres.
     */
    private const TYPES = [
        'depot' => ['Dépôt', self::CREDIT],
        'mise_quotidienne' => ['Mise quotidienne', self::CREDIT],
        'transfert_entrant' => ['Transfert reçu', self::CREDIT],
        'octroi_de_credit' => ['Crédit reçu', self::CREDIT],
        'paie_entrant' => ['Salaire reçu', self::CREDIT],
        'conversion_entree_client' => ['Conversion de devise (entrée)', self::CREDIT],
        'echeance_compte_terme' => ['Échéance du dépôt à terme', self::CREDIT],
        'retrait_anticipe_compte_terme' => ['Retrait anticipé du dépôt à terme', self::CREDIT],
        'virement_caisse_entrant' => ['Virement reçu', self::CREDIT],
        // Ancien type, plus écrit par le code actuel (1 ligne en production)
        'mise_quotidienne_multiple' => ['Mise quotidienne', self::CREDIT],

        'retrait' => ['Retrait', self::DEBIT],
        'retrait_carte_adhesion' => ['Retrait sur carnet', self::DEBIT],
        'transfert_sortant' => ['Transfert envoyé', self::DEBIT],
        'remboursement_de_credit' => ['Remboursement de crédit', self::DEBIT],
        'frais_mutuelle' => ['Frais de mutuelle', self::DEBIT],
        'commission_credit' => ['Commission sur crédit', self::DEBIT],
        'conversion_sortie_client' => ['Conversion de devise (sortie)', self::DEBIT],
        'placement_compte_terme' => ['Placement à terme', self::DEBIT],
        'annulation_paie' => ['Annulation de salaire', self::DEBIT],
        // Ancien prélèvement automatique de pénalité (balance -= montant)
        'penalite_de_credit' => ['Pénalité de retard sur crédit', self::DEBIT],

        // Sans effet sur le solde du compte : le membre paie ces frais au
        // guichet, la ligne est seulement inscrite sur son compte.
        'frais_adhesion' => ['Frais d\'adhésion (payés au guichet)', null],
        // Origine inconnue (2 lignes en production) : affiché sans signe
        'regularisation' => ['Régularisation', null],
    ];

    /**
     * Écritures internes à l'IMF, JAMAIS montrées au client.
     *
     * L'ancien code enregistrait ces opérations de caisse avec un account_id
     * de compte membre et le solde de la CAISSE PRINCIPALE dans balance_after :
     * - « Entrée de fonds » / « Sortie de fonds » : account_id = id de la caisse
     *   principale, donc rattaché par erreur au compte membre de même numéro ;
     * - octroi_de_credit_client : doublon côté caisse de octroi_de_credit.
     */
    private const INTERNAL = [
        'Entrée de fonds',
        'Sortie de fonds',
        'octroi_de_credit_client',
    ];

    /** Correction manuelle : le sens est écrit dans la description. */
    private const CORRECTION = 'rectification_solde';

    public static function normalize(string $type): string
    {
        return Str::lower(Str::ascii(trim($type)));
    }

    public static function label(string $type): string
    {
        $key = self::normalize($type);

        if ($key === self::CORRECTION) {
            return 'Correction de solde';
        }

        return self::TYPES[$key][0] ?? Str::ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * credit, debit, ou null si le type est inconnu (affiché sans signe).
     */
    public static function direction(Transaction $transaction): ?string
    {
        $key = self::normalize((string) $transaction->type);

        if ($key === self::CORRECTION) {
            // Description écrite par MemberDetails::updateBalance() :
            // "RECTIFICATION MANUELLE DU SOLDE: + 500 CDF. Raison: ..."
            if (preg_match('/SOLDE:\s*([+-])/u', (string) $transaction->description, $m)) {
                return $m[1] === '+' ? self::CREDIT : self::DEBIT;
            }

            return null;
        }

        return self::TYPES[$key][1] ?? null;
    }

    /**
     * Types bruts (avec et sans accents) d'un sens donné, pour filtrer en SQL.
     *
     * @return list<string>
     */
    public static function typesFor(string $direction): array
    {
        $types = [];

        foreach (self::TYPES as $key => [, $dir]) {
            if ($dir === $direction) {
                $types[] = $key;
            }
        }

        // Variante accentuée encore utilisée en base
        if ($direction === self::CREDIT) {
            $types[] = 'dépôt';
        }

        return $types;
    }

    /**
     * Préfixe de description d'une correction de solde dans un sens donné.
     */
    public static function correctionPattern(string $direction): string
    {
        return '%SOLDE: ' . ($direction === self::CREDIT ? '+' : '-') . '%';
    }

    public static function correctionType(): string
    {
        return self::CORRECTION;
    }

    /**
     * Types exclus de l'historique client (filtre SQL).
     *
     * @return list<string>
     */
    public static function internalTypes(): array
    {
        return self::INTERNAL;
    }
}
