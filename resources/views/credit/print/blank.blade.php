<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche de Collecte Terrain Vierge - Maisha Bora</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.4;
            font-size: 11px;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        /* En-tête */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .header-table td {
            border: none !important;
            padding: 0 !important;
        }
        .header-logo {
            width: 80px;
            height: auto;
        }
        .header-title {
            text-align: center;
        }
        .header-title h2 {
            margin: 0;
            font-size: 16px;
            color: #1e3a8a;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 10px;
            color: #666;
        }
        .header-meta {
            text-align: right;
            font-size: 9px;
            color: #555;
        }

        .divider {
            border: 0;
            border-bottom: 2px solid #eab308;
            margin: 10px 0 20px 0;
        }

        .document-title-container {
            background-color: #1e3a8a;
            color: #fff;
            text-align: center;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .document-title {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Sections */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e3a8a;
            text-transform: uppercase;
            border-bottom: 1px solid #1e3a8a;
            padding-bottom: 3px;
            margin-top: 20px;
            margin-bottom: 10px;
            letter-spacing: 0.5px;
        }

        /* Forms / Inputs representation */
        .row-grid {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 10px;
        }
        .col-4 {
            width: 33.33%;
            box-sizing: border-box;
            padding-right: 15px;
        }
        .col-6 {
            width: 50%;
            box-sizing: border-box;
            padding-right: 15px;
        }
        .col-12 {
            width: 100%;
            box-sizing: border-box;
        }
        
        .field-box {
            margin-bottom: 8px;
        }
        .field-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
            font-size: 9px;
            text-transform: uppercase;
        }
        .field-value-blank {
            border: 1px solid #ccc;
            height: 22px;
            border-radius: 3px;
            background-color: #fafafa;
        }

        /* Tables style */
        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #bbb;
            padding: 5px 8px;
            text-align: left;
            vertical-align: middle;
        }
        .table-custom th {
            background-color: #f3f4f6;
            color: #1e3a8a;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
        }

        /* Special balance-sheet layout */
        .balance-sheet-container {
            display: flex;
            margin-bottom: 15px;
            border: 1px solid #bbb;
            border-radius: 4px;
            overflow: hidden;
        }
        .balance-column {
            width: 50%;
        }
        .balance-column:first-child {
            border-right: 1px solid #bbb;
        }
        .balance-header {
            background-color: #1e3a8a;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 5px;
            font-size: 10px;
            text-transform: uppercase;
        }
        .balance-row {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 4px 8px;
            align-items: center;
        }
        .balance-row:last-child {
            border-bottom: none;
        }
        .balance-label {
            width: 60%;
            font-size: 9px;
        }
        .balance-value-blank {
            width: 40%;
            border: 1px solid #ccc;
            height: 18px;
            border-radius: 2px;
            background-color: #fafafa;
        }
        .balance-total-row {
            display: flex;
            background-color: #f3f4f6;
            font-weight: bold;
            padding: 6px 8px;
            border-top: 1px solid #bbb;
        }

        /* Ratios & Ratings section */
        .rating-box {
            width: 25px;
            height: 18px;
            border: 1px solid #bbb;
            display: inline-block;
            vertical-align: middle;
            background-color: #fafafa;
            border-radius: 2px;
        }
        
        /* Floating Button for Screen only */
        .no-print-bar {
            background-color: #1e293b;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px;
            margin-bottom: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }
        .btn-print {
            background-color: #eab308;
            color: #1e293b;
            border: none;
            padding: 6px 15px;
            font-weight: bold;
            border-radius: 4px;
            cursor: pointer;
            text-transform: uppercase;
            font-size: 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-print:hover {
            background-color: #ca8a04;
        }
        .btn-back {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 11px;
        }
        .btn-back:hover {
            color: white;
            text-decoration: underline;
        }

        /* Signatures footer */
        .signatures-container {
            display: flex;
            margin-top: 30px;
            justify-content: space-between;
        }
        .signature-box {
            width: 30%;
            border: 1px dashed #999;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
            min-height: 80px;
            box-sizing: border-box;
        }
        .signature-title {
            font-weight: bold;
            font-size: 9px;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 40px;
        }

        /* Print media query optimizations */
        @media print {
            body {
                background-color: #fff;
                padding: 0;
                font-size: 10px;
            }
            .container {
                box-shadow: none;
                padding: 0;
                max-width: 100%;
            }
            .no-print-bar {
                display: none !important;
            }
            .page-break {
                page-break-before: always;
            }
            .divider {
                border-bottom-color: #000;
            }
            .document-title-container {
                background-color: #000 !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .section-title {
                color: #000 !important;
                border-bottom-color: #000 !important;
            }
            .table-custom th {
                background-color: #eaeaea !important;
                color: #000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .balance-header {
                background-color: #000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    <!-- Bar d'actions hors impression -->
    <div class="no-print-bar">
        <a href="{{ route('credit.applications.list') }}" class="btn-back">
            &larr; Retour à la liste
        </a>
        <div style="font-weight: bold; font-size: 12px; color: #e2e8f0;">Fiche de Collecte Terrain Vierge</div>
        <button class="btn-print" onclick="window.print()">
            Imprimer la Fiche
        </button>
    </div>

    <div class="container">
        <!-- En-tête -->
        <table class="header-table">
            <tr>
                <td style="width: 15%;">
                    @php
                        $logoPath = public_path('assets/img/logo.jpg');
                        $logoData = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';
                    @endphp
                    @if($logoData)
                        <img src="data:image/png;base64,{{ $logoData }}" class="header-logo" alt="Logo">
                    @else
                        <div style="font-weight: bold; font-size: 16px; color: #1e3a8a;">MAISHA BORA</div>
                    @endif
                </td>
                <td style="width: 65%;" class="header-title">
                    <h2>{{ strtoupper($company->name ?? config('app.name', 'MAISHA BORA')) }}</h2>
                    <p>Adresse : {{ $company->address ?? env('APP_ADRESS', 'Goma, RDC') }}</p>
                    <p>Tél : {{ $company->phone ?? env('APP_PHONE', '+243...') }} | Email : {{ $company->email ?? env('APP_EMAIL', 'info@maishabora.com') }}</p>
                    <p>RCCM : {{ $company->rccm ?? env('APP_RCCM', 'RDC/GOM/...') }}</p>
                </td>
                <td style="width: 20%;" class="header-meta">
                    <strong>Date Imp. :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure Imp. :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong> {{ Auth::user()->name }} {{ Auth::user()->postnom }}
                </td>
            </tr>
        </table>

        <hr class="divider">

        <div class="document-title-container">
            <h3 class="document-title">Fiche de Collecte & d'Analyse de Crédit (Terrain)</h3>
        </div>

        <!-- Section 1: Informations Générales -->
        <div class="section-title">1. Informations Générales du Demandeur</div>
        <div class="row-grid">
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Code Membre / Emprunteur</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
            <div class="col-6">
                <div class="field-box">
                    <div class="field-label">Nom complet de l'Emprunteur</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
        </div>
        <div class="row-grid">
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Montant Sollicité</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Devise (USD / CDF)</div>
                    <div class="field-value-blank" style="position: relative;">
                        <span style="position: absolute; left: 8px; top: 4px; font-size: 8px;">[ ] USD &nbsp;&nbsp;&nbsp; [ ] CDF</span>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Durée Souhaitée (en Mois)</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
        </div>

        <!-- Section 2: Business / Activité -->
        <div class="section-title">2. Profil de l'Activité (Business / Projet)</div>
        <div class="row-grid">
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Type d'Activité / Projet</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Secteur d'Activité</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
            <div class="col-4">
                <div class="field-box">
                    <div class="field-label">Localisation de l'Activité</div>
                    <div class="field-value-blank"></div>
                </div>
            </div>
        </div>

        <!-- Section 3: TFR (Tableau de Flux de Trésorerie) -->
        <div class="section-title">3. Tableau de Flux de Trésorerie Mensuel (TFR)</div>
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 50%;">Rubrique / Libellé Financier</th>
                    <th style="width: 25%; text-align: center;">Montant Mensuel</th>
                    <th style="width: 25%;">Notes / Observations de l'Agent</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Chiffre d'Affaires Mensuel Estimé (Ventes) (+)</strong></td>
                    <td class="field-value-blank"></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Coût d'Achat des Marchandises Vendues (Purchases / CAMV) (-)</td>
                    <td class="field-value-blank"></td>
                    <td></td>
                </tr>
                <tr style="background-color: #fafafa;">
                    <td><strong>MARGE BRUTE MENSUELLE (Calculée : Ventes - Achats)</strong></td>
                    <td style="background-color: #f3f4f6; border: 1px solid #bbb;"></td>
                    <td style="color: #666; font-style: italic; font-size: 8px;">Auto-calculé dans le système</td>
                </tr>
                <tr>
                    <td>Charges d'Activité Mensuelles (Salaires, Loyer, Transport, Taxes...) (-)</td>
                    <td class="field-value-blank"></td>
                    <td></td>
                </tr>
                <tr style="background-color: #fafafa;">
                    <td><strong>MARGE NETTE D'ACTIVITÉ (Marge Brute - Charges Activité)</strong></td>
                    <td style="background-color: #f3f4f6; border: 1px solid #bbb;"></td>
                    <td style="color: #666; font-style: italic; font-size: 8px;">Auto-calculé dans le système</td>
                </tr>
                <tr>
                    <td>Autres Revenus du Foyer (Salaire conjoint, autres business) (+)</td>
                    <td class="field-value-blank"></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Charges du Ménage / Familiales (Alimentation, Loyer foyer, Écolage...) (-)</td>
                    <td class="field-value-blank"></td>
                    <td></td>
                </tr>
                <tr style="background-color: #f3f4f6; font-weight: bold;">
                    <td>REVENU MENSUEL DISPONIBLE (RMD)</td>
                    <td style="border: 1px solid #bbb; background-color: #e5e7eb;"></td>
                    <td style="color: #666; font-style: italic; font-size: 8px;">Auto-calculé dans le système</td>
                </tr>
                <tr style="background-color: #fef08a; font-weight: bold;">
                    <td>CAPACITÉ DE REMBOURSEMENT MENSUELLE (Taux standard appliqué)</td>
                    <td style="border: 1px solid #bbb; background-color: #fef9c3;"></td>
                    <td style="color: #666; font-style: italic; font-size: 8px;">Capacité maximale d'endettement</td>
                </tr>
            </tbody>
        </table>

        <!-- Page break for printing to keep structure extremely tidy -->
        <div class="page-break"></div>

        <!-- Section 4: Bilan -->
        <div class="section-title" style="margin-top: 0;">4. Bilan Financier (État du Patrimoine)</div>
        <div class="balance-sheet-container">
            <!-- ACTIF -->
            <div class="balance-column">
                <div class="balance-header">ACTIF (Possessions)</div>
                <div class="balance-row">
                    <div class="balance-label">Cash / Liquidités en Caisse</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Créances Clients (Dettes actives)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Stock de Marchandises</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Actifs Immobilisés (Outils, véhicules, dépôt...)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-total-row">
                    <div class="balance-label" style="font-weight: bold;">TOTAL ACTIF (A)</div>
                    <div style="width: 40%; text-align: right; font-style: italic; font-size: 8px; color: #666; padding-right: 5px;">Somme Actifs</div>
                </div>
            </div>
            
            <!-- PASSIF -->
            <div class="balance-column">
                <div class="balance-header">PASSIF (Dettes) & FONDS PROPRES</div>
                <div class="balance-row">
                    <div class="balance-label">Dettes Formelles Court Terme (CT)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Dettes Formelles Moyen Terme (MT)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Dettes Formelles Long Terme (LT)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label">Dettes Informelles (Famille, tiers CT/MT)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-row">
                    <div class="balance-label" style="font-weight: bold; color: #1e3a8a;">Fonds Propres (Patrimoine Net)</div>
                    <div class="balance-value-blank"></div>
                </div>
                <div class="balance-total-row">
                    <div class="balance-label" style="font-weight: bold;">TOTAL PASSIF (B + FP)</div>
                    <div style="width: 40%; text-align: right; font-style: italic; font-size: 8px; color: #666; padding-right: 5px;">Équilibre (A = B + FP)</div>
                </div>
            </div>
        </div>

        <!-- Section 5: Garanties -->
        <div class="section-title">5. Garanties Proposées / Sûretés</div>
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 25%;">Type de Garantie</th>
                    <th style="width: 35%;">Description du Bien (Marque, Numéro, État)</th>
                    <th style="width: 15%; text-align: center;">Valeur Estimée</th>
                    <th style="width: 15%;">Nature du Bien</th>
                    <th style="width: 10%;">Propriétaire</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                </tr>
                <tr>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                    <td class="field-value-blank"></td>
                </tr>
            </tbody>
        </table>

        <!-- Section 6: Évaluation Qualitative (5C) & Observations -->
        <div class="section-title">6. Évaluation Qualitative (5C - Grille Terrain)</div>
        <table class="table-custom" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th style="width: 35%;">Critère d'Évaluation (5C)</th>
                    <th style="width: 15%; text-align: center;">Note (1 à 5)</th>
                    <th style="width: 50%;">Justification Terrain / Remarques Particulières</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><strong>Caractère (Reputation, moralité, historique)</strong></td>
                    <td style="text-align: center;"><div class="rating-box"></div> / 5</td>
                    <td class="field-value-blank"></td>
                </tr>
                <tr>
                    <td><strong>Capacité (Aptitude de gestion, flux de trésorerie)</strong></td>
                    <td style="text-align: center;"><div class="rating-box"></div> / 5</td>
                    <td class="field-value-blank"></td>
                </tr>
                <tr>
                    <td><strong>Capital (Apport propre du membre dans l'activité)</strong></td>
                    <td style="text-align: center;"><div class="rating-box"></div> / 5</td>
                    <td class="field-value-blank"></td>
                </tr>
                <tr>
                    <td><strong>Caution (Qualité et couverture des garanties)</strong></td>
                    <td style="text-align: center;"><div class="rating-box"></div> / 5</td>
                    <td class="field-value-blank"></td>
                </tr>
                <tr>
                    <td><strong>Caractéristiques Financières (Viabilité marché/secteur)</strong></td>
                    <td style="text-align: center;"><div class="rating-box"></div> / 5</td>
                    <td class="field-value-blank"></td>
                </tr>
            </tbody>
        </table>

        <div class="field-box" style="margin-top: 10px;">
            <div class="field-label">Avis Global & Recommandation de l'Agent de Crédit (Montant suggéré, Durée, Taux)</div>
            <div class="field-value-blank" style="height: 45px;"></div>
        </div>

        <!-- Section 7: Signatures -->
        <div class="signatures-container">
            <div class="signature-box">
                <div class="signature-title">L'Agent de Crédit (Visiteur)</div>
                <div style="font-size: 8px; color: #666; margin-top: 30px;">Signature & Date</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">L'Emprunteur (Membre)</div>
                <div style="font-size: 8px; color: #666; margin-top: 30px;">Signature (Lu et approuvé)</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Le Comité de Crédit / Décision</div>
                <div style="font-size: 8px; color: #666; margin-top: 30px;">Avis & Visa Comité</div>
            </div>
        </div>
    </div>

</body>
</html>
