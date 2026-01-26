<?php

namespace Database\Seeders;

use App\Models\Compte;
use Illuminate\Database\Seeder;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Seed du plan comptable IMF selon normes OHADA/Microfinance
     * Structure: Classe -> Sous-classe -> Comptes détaillés
     */
    public function run(): void
    {
        // Vérifier si le plan comptable existe déjà
        if (Compte::count() > 10) {
            $this->command->warn('⚠️  Plan comptable déjà existant. Seuls les comptes manquants seront ajoutés.');
        }

        $this->seedClass1Capitaux();
        $this->seedClass2Immobilisations();
        $this->seedClass4Tiers();
        $this->seedClass5Tresorerie();
        $this->seedClass6Charges();
        $this->seedClass7Produits();

        $this->command->info('✅ Plan comptable IMF créé avec succès!');
    }

    /**
     * CLASSE 1 - CAPITAUX PERMANENTS
     */
    private function seedClass1Capitaux(): void
    {
        $classe1 = $this->createOrUpdate('1', 'CAPITAUX PERMANENTS', 'Actif', 1, null);

        // 10 - Capital
        $sc10 = $this->createOrUpdate('10', 'Capital', 'Actif', 2, $classe1->id, '10');
        $this->createOrUpdate('101', 'Capital social', 'Actif', 3, $sc10->id, '10');
        $this->createOrUpdate('109', 'Actionnaires - Capital souscrit non appelé', 'Actif', 3, $sc10->id, '10');

        // 11 - Réserves
        $sc11 = $this->createOrUpdate('11', 'Réserves', 'Actif', 2, $classe1->id, '11');
        $this->createOrUpdate('111', 'Réserve légale', 'Actif', 3, $sc11->id, '11');
        $this->createOrUpdate('112', 'Réserves statutaires', 'Actif', 3, $sc11->id, '11');
        $this->createOrUpdate('118', 'Autres réserves', 'Actif', 3, $sc11->id, '11');

        // 12 - Report à nouveau
        $this->createOrUpdate('12', 'Report à nouveau', 'Actif', 2, $classe1->id, '12');
        $this->createOrUpdate('121', 'Report à nouveau créditeur', 'Actif', 3, null, '12');
        $this->createOrUpdate('129', 'Report à nouveau débiteur', 'Actif', 3, null, '12');

        // 13 - Résultat de l'exercice
        $this->createOrUpdate('13', 'Résultat de l\'exercice', 'Actif', 2, $classe1->id, '13');
        $this->createOrUpdate('131', 'Résultat net : Bénéfice', 'Actif', 3, null, '13');
        $this->createOrUpdate('139', 'Résultat net : Perte', 'Actif', 3, null, '13');

        // 16 - Emprunts et dettes assimilées
        $sc16 = $this->createOrUpdate('16', 'Emprunts et dettes assimilées', 'Passif', 2, $classe1->id, '16');
        $this->createOrUpdate('161', 'Emprunts obligataires', 'Passif', 3, $sc16->id, '16');
        $this->createOrUpdate('162', 'Emprunts auprès des établissements de crédit', 'Passif', 3, $sc16->id, '16');
    }

    /**
     * CLASSE 2 - IMMOBILISATIONS
     */
    private function seedClass2Immobilisations(): void
    {
        $classe2 = $this->createOrUpdate('2', 'IMMOBILISATIONS', 'Actif', 1, null);

        // 21 - Immobilisations incorporelles
        $sc21 = $this->createOrUpdate('21', 'Immobilisations incorporelles', 'Actif', 2, $classe2->id, '21');
        $this->createOrUpdate('211', 'Frais de développement et de prospection', 'Actif', 3, $sc21->id, '21');
        $this->createOrUpdate('213', 'Logiciels et sites internet', 'Actif', 3, $sc21->id, '21');
        $this->createOrUpdate('218', 'Autres immobilisations incorporelles', 'Actif', 3, $sc21->id, '21');

        // 24 - Immobilisations corporelles
        $sc24 = $this->createOrUpdate('24', 'Immobilisations corporelles', 'Actif', 2, $classe2->id, '24');
        $this->createOrUpdate('241', 'Terrains', 'Actif', 3, $sc24->id, '24');
        $this->createOrUpdate('242', 'Bâtiments', 'Actif', 3, $sc24->id, '24');
        $this->createOrUpdate('244', 'Matériel et mobilier', 'Actif', 3, $sc24->id, '24');
        $this->createOrUpdate('245', 'Matériel informatique', 'Actif', 3, $sc24->id, '24');
        $this->createOrUpdate('2451', 'Ordinateurs et équipements', 'Actif', 3, $sc24->id, '24');
        $this->createOrUpdate('2452', 'Serveurs et infrastructure réseau', 'Actif', 3, $sc24->id, '24');

        // 28 - Amortissements
        $sc28 = $this->createOrUpdate('28', 'Amortissements des immobilisations', 'Actif', 2, $classe2->id, '28');
        $this->createOrUpdate('281', 'Amortissements immobilisations incorporelles', 'Actif', 3, $sc28->id, '28');
        $this->createOrUpdate('284', 'Amortissements immobilisations corporelles', 'Actif', 3, $sc28->id, '28');
    }

    /**
     * CLASSE 4 - COMPTES DE TIERS
     */
    private function seedClass4Tiers(): void
    {
        $classe4 = $this->createOrUpdate('4', 'COMPTES DE TIERS', 'Actif', 1, null);

        // 27 - CRÉDITS À LA CLIENTÈLE (compte spécifique IMF - parfois en classe 2 ou 4)
        $sc27 = $this->createOrUpdate('27', 'Crédits à la clientèle', 'Actif', 2, $classe4->id, '27');
        $this->createOrUpdate('271', 'Crédits en cours', 'Actif', 3, $sc27->id, '27', 'multi', 'Crédits actifs non échus');
        $this->createOrUpdate('272', 'Crédits échus impayés', 'Actif', 3, $sc27->id, '27', 'multi', 'Crédits dont l\'échéance est dépassée');
        $this->createOrUpdate('278', 'Intérêts courus à recevoir', 'Actif', 3, $sc27->id, '27', 'multi', 'Intérêts courus non encore encaissés');

        // 42 - ÉPARGNE DES MEMBRES (compte spécifique IMF)
        $sc42 = $this->createOrUpdate('42', 'Épargne des membres', 'Passif', 2, $classe4->id, '42');
        $this->createOrUpdate('421', 'Comptes d\'épargne courants', 'Passif', 3, $sc42->id, '42', 'multi', 'Épargne libre');
        $this->createOrUpdate('422', 'Comptes d\'épargne à terme', 'Passif', 3, $sc42->id, '42', 'multi', 'Épargne bloquée');
        $this->createOrUpdate('428', 'Intérêts courus à payer', 'Passif', 3, $sc42->id, '42', 'multi', 'Intérêts dus non encore versés');

        // 40 - Fournisseurs
        $sc40 = $this->createOrUpdate('40', 'Fournisseurs et comptes rattachés', 'Passif', 2, $classe4->id, '40');
        $this->createOrUpdate('401', 'Fournisseurs', 'Passif', 3, $sc40->id, '40');
        $this->createOrUpdate('408', 'Fournisseurs - Factures non parvenues', 'Passif', 3, $sc40->id, '40');

        // 42 - Personnel
        $sc42 = $this->createOrUpdate('42', 'Personnel', 'Passif', 2, $classe4->id, '42');
        $this->createOrUpdate('421', 'Personnel - Rémunérations dues', 'Passif', 3, $sc42->id, '42');
        $this->createOrUpdate('422', 'Personnel - Avances et acomptes', 'Actif', 3, $sc42->id, '42');

        // 43 - État
        $sc43 = $this->createOrUpdate('43', 'État et collectivités publiques', 'Passif', 2, $classe4->id, '43');
        $this->createOrUpdate('431', 'État - Impôts sur les bénéfices', 'Passif', 3, $sc43->id, '43');
        $this->createOrUpdate('432', 'État - Taxes sur le chiffre d\'affaires', 'Passif', 3, $sc43->id, '43');
        $this->createOrUpdate('437', 'État - Charges sociales', 'Passif', 3, $sc43->id, '43');

        // 49 - PROVISIONS POUR CRÉANCES DOUTEUSES (spécifique IMF)
        $sc49 = $this->createOrUpdate('49', 'Provisions pour dépréciation', 'Passif', 2, $classe4->id, '49');
        $this->createOrUpdate('491', 'Provisions pour créances douteuses', 'Passif', 3, $sc49->id, '49', 'multi', 'Provisions sur crédits à risque');
        $this->createOrUpdate('4911', 'Provisions - Crédits 1-30 jours', 'Passif', 3, $sc49->id, '49', 'multi');
        $this->createOrUpdate('4912', 'Provisions - Crédits 31-60 jours', 'Passif', 3, $sc49->id, '49', 'multi');
        $this->createOrUpdate('4913', 'Provisions - Crédits 61-90 jours', 'Passif', 3, $sc49->id, '49', 'multi');
        $this->createOrUpdate('4914', 'Provisions - Crédits >90 jours', 'Passif', 3, $sc49->id, '49', 'multi');
    }

    /**
     * CLASSE 5 - TRÉSORERIE
     */
    private function seedClass5Tresorerie(): void
    {
        $classe5 = $this->createOrUpdate('5', 'COMPTES DE TRÉSORERIE', 'Actif', 1, null);

        // 52 - Banques
        $sc52 = $this->createOrUpdate('52', 'Banques', 'Actif', 2, $classe5->id, '52');
        $this->createOrUpdate('521', 'Banques USD', 'Actif', 3, $sc52->id, '52', 'USD', 'Comptes bancaires en dollars américains');
        $this->createOrUpdate('522', 'Banques CDF', 'Actif', 3, $sc52->id, '52', 'CDF', 'Comptes bancaires en francs congolais');

        // 57 - Caisses
        $sc57 = $this->createOrUpdate('57', 'Caisses', 'Actif', 2, $classe5->id, '57');
        $this->createOrUpdate('571', 'Caisse centrale USD', 'Actif', 3, $sc57->id, '57', 'USD', 'Caisse principale en USD');
        $this->createOrUpdate('572', 'Caisse centrale CDF', 'Actif', 3, $sc57->id, '57', 'CDF', 'Caisse principale en CDF');
        $this->createOrUpdate('5731', 'Caisses agents USD', 'Actif', 3, $sc57->id, '57', 'USD', 'Caisses des agents de terrain USD');
        $this->createOrUpdate('5732', 'Caisses agents CDF', 'Actif', 3, $sc57->id, '57', 'CDF', 'Caisses des agents de terrain CDF');

        // 58 - Virements internes
        $this->createOrUpdate('58', 'Virements internes', 'Actif', 2, $classe5->id, '58');
        $this->createOrUpdate('580', 'Compte de passage', 'Actif', 3, null, '58', 'multi', 'Compte transitoire pour opérations internes');
    }

    /**
     * CLASSE 6 - CHARGES
     */
    private function seedClass6Charges(): void
    {
        $classe6 = $this->createOrUpdate('6', 'COMPTES DE CHARGES', 'Charge', 1, null);

        // 60 - Achats
        $sc60 = $this->createOrUpdate('60', 'Achats', 'Charge', 2, $classe6->id, '60');
        $this->createOrUpdate('601', 'Fournitures de bureau', 'Charge', 3, $sc60->id, '60');
        $this->createOrUpdate('602', 'Carburants et lubrifiants', 'Charge', 3, $sc60->id, '60');

        // 61 - Transports
        $this->createOrUpdate('61', 'Transports', 'Charge', 2, $classe6->id, '61');
        $this->createOrUpdate('611', 'Frais de transport', 'Charge', 3, null, '61');

        // 62 - Autres services extérieurs
        $sc62 = $this->createOrUpdate('62', 'Autres services extérieurs', 'Charge', 2, $classe6->id, '62');
        $this->createOrUpdate('622', 'Locations', 'Charge', 3, $sc62->id, '62');
        $this->createOrUpdate('623', 'Entretien et réparations', 'Charge', 3, $sc62->id, '62');
        $this->createOrUpdate('625', 'Frais de télécommunications', 'Charge', 3, $sc62->id, '62');
        $this->createOrUpdate('626', 'Frais bancaires', 'Charge', 3, $sc62->id, '62');

        // 64 - Charges de personnel
        $sc64 = $this->createOrUpdate('64', 'Charges de personnel', 'Charge', 2, $classe6->id, '64');
        $this->createOrUpdate('641', 'Salaires et traitements', 'Charge', 3, $sc64->id, '64');
        $this->createOrUpdate('645', 'Charges sociales', 'Charge', 3, $sc64->id, '64');

        // 65 - Autres charges
        $sc65 = $this->createOrUpdate('65', 'Autres charges', 'Charge', 2, $classe6->id, '65');
        $this->createOrUpdate('651', 'Pertes sur créances irrécouvrables', 'Charge', 3, $sc65->id, '65', 'multi', 'Créances définitivement perdues');
        $this->createOrUpdate('6593', 'Dotations aux provisions pour créances douteuses', 'Charge', 3, $sc65->id, '65', 'multi', 'Augmentation des provisions');

        // 68 - Dotations aux amortissements
        $sc68 = $this->createOrUpdate('68', 'Dotations aux amortissements', 'Charge', 2, $classe6->id, '68');
        $this->createOrUpdate('681', 'Dotations amortissements immobilisations incorporelles', 'Charge', 3, $sc68->id, '68');
        $this->createOrUpdate('684', 'Dotations amortissements immobilisations corporelles', 'Charge', 3, $sc68->id, '68');
    }

    /**
     * CLASSE 7 - PRODUITS
     */
    private function seedClass7Produits(): void
    {
        $classe7 = $this->createOrUpdate('7', 'COMPTES DE PRODUITS', 'Produit', 1, null);

        // 70 - PRODUITS D'INTÉRÊTS (principal produit IMF)
        $sc70 = $this->createOrUpdate('70', 'Produits d\'intérêts', 'Produit', 2, $classe7->id, '70');
        $this->createOrUpdate('701', 'Intérêts sur crédits', 'Produit', 3, $sc70->id, '70', 'multi', 'Intérêts perçus sur prêts');
        $this->createOrUpdate('702', 'Commissions sur crédits', 'Produit', 3, $sc70->id, '70', 'multi', 'Frais de dossier et commissions');
        $this->createOrUpdate('703', 'Pénalités de retard', 'Produit', 3, $sc70->id, '70', 'multi', 'Pénalités sur retards de paiement');

        // 706 - COTISATIONS ET ADHÉSIONS (spécifique IMF)
        $sc706 = $this->createOrUpdate('706', 'Cotisations et adhésions', 'Produit', 2, $classe7->id, '706');
        $this->createOrUpdate('7061', 'Frais d\'adhésion', 'Produit', 3, $sc706->id, '706', 'multi', 'Frais d\'achat de cartes membres');
        $this->createOrUpdate('7062', 'Cotisations quotidiennes', 'Produit', 3, $sc706->id, '706', 'multi', 'Cotisations journalières des membres');

        // 72 - Production immobilisée
        $this->createOrUpdate('72', 'Production immobilisée', 'Produit', 2, $classe7->id, '72');

        // 75 - Autres produits
        $sc75 = $this->createOrUpdate('75', 'Autres produits', 'Produit', 2, $classe7->id, '75');
        $this->createOrUpdate('758', 'Produits divers', 'Produit', 3, $sc75->id, '75');
        $this->createOrUpdate('7593', 'Reprises de provisions', 'Produit', 3, $sc75->id, '75', 'multi', 'Diminution des provisions');

        // 77 - Produits financiers
        $sc77 = $this->createOrUpdate('77', 'Produits financiers', 'Produit', 2, $classe7->id, '77');
        $this->createOrUpdate('771', 'Intérêts sur placements', 'Produit', 3, $sc77->id, '77');
        $this->createOrUpdate('776', 'Gains de change', 'Produit', 3, $sc77->id, '77', 'multi', 'Gains sur opérations de change');
    }

    /**
     * Helper: Crée ou met à jour un compte (évite les doublons)
     */
    private function createOrUpdate(
        string $code,
        string $intitule,
        string $type,
        int $level,
        ?int $parentId = null,
        ?string $sousClasse = null,
        string $currencyType = 'multi',
        ?string $description = null
    ): Compte {
        return Compte::updateOrCreate(
            ['code' => $code],
            [
                'intitule' => $intitule,
                'type' => $type,
                'level' => $level,
                'parent_id' => $parentId,
                'sous_classe' => $sousClasse,
                'currency_type' => $currencyType,
                'description' => $description,
                'is_active' => true,
            ]
        );
    }
}
