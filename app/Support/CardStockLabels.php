<?php

namespace App\Support;

class CardStockLabels
{
    // Libelles communs aux tableaux, fiches et exports ; les codes internes restent stables.
    public const STATUSES = [
        'in_stock' => 'En stock', 'with_collector' => 'Chez un agent', 'sold' => 'Vendu',
        'lost' => 'Perdu', 'damaged' => 'Détérioré', 'cancelled' => 'Annulé (ancien statut)',
    ];

    public const ACTIONS = [
        'update_batch' => 'Modification du lot',
        'update_number' => 'Correction du numéro',
        'receive' => 'Réception', 'assign' => 'Attribution', 'return' => 'Retour au stock',
        'transfer' => 'Transfert', 'lost' => 'Déclaration de perte', 'damaged' => 'Déclaration de détérioration',
        'sell' => 'Vente', 'cancel_sale' => 'Annulation de vente', 'restore_stock' => 'Remise en stock',
    ];

    public static function status(?string $status): string
    {
        return $status === null ? '—' : (self::STATUSES[$status] ?? 'Statut inconnu');
    }

    public static function badge(?string $status): string
    {
        return match ($status) {
            'in_stock' => 'bg-label-primary', 'with_collector' => 'bg-label-info', 'sold' => 'bg-label-success',
            'lost' => 'bg-label-danger', 'damaged' => 'bg-label-warning', default => 'bg-label-secondary',
        };
    }

    public static function action(string $action): string
    {
        return self::ACTIONS[$action] ?? 'Mouvement';
    }
}
