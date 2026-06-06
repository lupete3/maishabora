<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulaire d'analyse de crédit MPME - Maisha Bora</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            line-height: 1.35;
            font-size: 10px;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }

        .container {
            max-width: 820px;
            margin: 0 auto;
            background-color: #fff;
            padding: 28px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
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
            color: #bd8a12;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .header-title p {
            margin: 3px 0 0 0;
            font-size: 9px;
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
            margin: 10px 0 15px 0;
        }

        .document-title-container {
            background-color: #bd8a12;
            color: #fff;
            text-align: center;
            padding: 8px 15px;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        .document-title {
            margin: 0;
            font-size: 13px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #bd8a12;
            text-transform: uppercase;
            border-bottom: 1px solid #bd8a12;
            padding-bottom: 3px;
            margin-top: 16px;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }

        .subsection-title {
            font-size: 9px;
            font-weight: bold;
            color: #333;
            text-transform: uppercase;
            margin: 10px 0 5px;
        }

        .row-grid {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 8px;
        }
        .col-3 {
            width: 25%;
            box-sizing: border-box;
            padding-right: 10px;
        }
        .col-4 {
            width: 33.33%;
            box-sizing: border-box;
            padding-right: 10px;
        }
        .col-6 {
            width: 50%;
            box-sizing: border-box;
            padding-right: 10px;
        }
        .col-12 {
            width: 100%;
            box-sizing: border-box;
        }

        .field-box {
            margin-bottom: 7px;
        }
        .field-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
            font-size: 8px;
            text-transform: uppercase;
        }
        .field-value-blank {
            border: 1px solid #bbb;
            min-height: 21px;
            border-radius: 2px;
            background-color: #fafafa;
        }
        .field-value-blank.tall {
            min-height: 48px;
        }
        .field-value-blank.notes {
            min-height: 72px;
        }
        .checkbox-line {
            font-size: 9px;
            line-height: 21px;
            padding-left: 7px;
            color: #333;
        }

        .table-custom {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 11px;
        }
        .table-custom th, .table-custom td {
            border: 1px solid #aaa;
            padding: 4px 6px;
            text-align: left;
            vertical-align: middle;
        }
        .table-custom th {
            background-color: #f3f4f6;
            color: #0a802e;
            font-weight: bold;
            font-size: 8px;
            text-transform: uppercase;
        }
        .table-custom td.blank-cell {
            height: 20px;
            background-color: #fafafa;
        }
        /* Specific column sizes for agent inputs */
        .w-input-wide {
            width: 65%;
        }
        .w-input-equal {
            width: 35%;
        }
        .w-month-label {
            width: 30%;
        }
        .w-month-input {
            width: 20%;
        }
        .table-custom .total-row td {
            background-color: #f3f4f6;
            font-weight: bold;
        }
        .narrow td {
            padding: 3px 5px;
        }

        .two-columns {
            display: flex;
            gap: 12px;
            align-items: flex-start;
        }
        .two-columns > div {
            width: 50%;
        }

        .no-print-bar {
            background-color: #1e293b;
            color: white;
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px;
            margin-bottom: 20px;
            max-width: 820px;
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
        }
        .btn-back {
            color: #cbd5e1;
            text-decoration: none;
            font-size: 11px;
        }

        .signatures-container {
            display: flex;
            margin-top: 22px;
            justify-content: space-between;
        }
        .signature-box {
            width: 30%;
            border: 1px dashed #999;
            border-radius: 4px;
            padding: 10px;
            text-align: center;
            min-height: 75px;
            box-sizing: border-box;
        }
        .signature-title {
            font-weight: bold;
            font-size: 9px;
            color: #555;
            text-transform: uppercase;
            margin-bottom: 38px;
        }

        @media print {
            body {
                background-color: #fff;
                padding: 0;
                font-size: 9px;
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
            .document-title-container {
                background-color: #bd8a12 !important;
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
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <a href="{{ route('credit.applications.list') }}" class="btn-back">&larr; Retour à la liste</a>
        <div style="font-weight: bold; font-size: 12px; color: #e2e8f0;">Formulaire d'analyse de crédit MPME</div>
        <button class="btn-print" onclick="window.print()">Imprimer la fiche</button>
    </div>

    <div class="container">
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
                        <div style="font-weight: bold; font-size: 16px; color: #a2840b;">MAISHA BORA</div>
                    @endif
                </td>
                <td style="width: 65%;" class="header-title">
                    <h2>{{ strtoupper($company->name ?? config('app.name', 'MAISHA BORA')) }}</h2>
                    <p>Adresse : {{ $company->address ?? env('APP_ADRESS', 'Goma, RDC') }}</p>
                    <p>Tél : {{ $company->phone ?? env('APP_PHONE', '+243...') }} | Email : {{ $company->email ?? env('APP_EMAIL', 'info@maishabora.com') }}</p>
                    <p>RCCM : {{ $company->rccm ?? env('APP_RCCM', 'RDC/GOM/...') }}</p>
                </td>
                <td style="width: 20%;" class="header-meta">
                    <strong>Date imp. :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure imp. :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong> {{ Auth::user()->name }} {{ Auth::user()->postnom }}
                </td>
            </tr>
        </table>

        <hr class="divider">

        <div class="document-title-container">
            <h3 class="document-title">Formulaire d'analyse de crédit MPME</h3>
        </div>

        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Date de la visite</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Agent de crédit</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Taux 1 USD = CDF</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">N° de crédit / N° client</div><div class="field-value-blank"></div></div></div>
        </div>
        <div class="row-grid">
            <div class="col-6"><div class="field-box"><div class="field-label">Nom du client</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Monnaie de l'analyse</div><div class="field-value-blank"><div class="checkbox-line">[ ] USD &nbsp;&nbsp; [ ] CDF</div></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Montant sollicité</div><div class="field-value-blank"></div></div></div>
        </div>

        <div class="section-title">I. Résumé de l'analyse</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">A. Revenu</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td class="w-input-wide">Activité</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Marge de l'entreprise (%)</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Nombre d'employés</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td><strong>Ventes cash</strong></td><td class="blank-cell"></td></tr>
                        <tr><td>Ventes à crédit</td><td class="blank-cell"></td></tr>
                        <tr><td>Coût de la marchandise vendue</td><td class="blank-cell"></td></tr>
                        <tr><td>Charges entreprise</td><td class="blank-cell"></td></tr>
                        <tr><td>Bénéfice</td><td class="blank-cell"></td></tr>
                        <tr><td>Revenus du ménage</td><td class="blank-cell"></td></tr>
                        <tr><td>Dépenses ménage</td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Revenu disponible</td><td></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">B. Bilan</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td class="w-input-wide">Cash</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Créances</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Stock</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td>Actifs immobilisés</td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total actif</td><td></td></tr>
                        <tr><td>Dettes fournisseurs</td><td class="blank-cell"></td></tr>
                        <tr><td>Dettes long terme</td><td class="blank-cell"></td></tr>
                        <tr><td>Fonds propres</td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total passif</td><td></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="subsection-title">C. Destination du crédit et plan d'investissement</div>
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Destination</th>
                    <th>Montant</th>
                    <th>Début</th>
                    <th>Fin</th>
                    <th>Part client</th>
                    <th>Part CAHI</th>
                    <th>Part tiers</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 7; $i++)
                    <tr>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                    </tr>
                @endfor
                <tr class="total-row"><td>Total</td><td colspan="6"></td></tr>
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">II. Informations sur le ménage</div>
        <div class="subsection-title">A. Identité</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Post-nom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Prénom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Province d'origine</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Niveau d'éducation</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Religion</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Biographie rapide</div><div class="field-value-blank tall"></div></div></div>
        </div>

        <div class="subsection-title">B. Structure familiale</div>
        <table class="table-custom">
            <thead>
                <tr><th>Nom</th><th>Lien</th><th>Occupation</th><th>Observations</th></tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 5; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="subsection-title">C. Habitation</div>
        <div class="row-grid">
            <div class="col-6"><div class="field-box"><div class="field-label">Type d'habitation</div><div class="field-value-blank"><div class="checkbox-line">[ ] Propriété &nbsp; [ ] Propriété familiale &nbsp; [ ] Location</div></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Valeur</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Temps de résidence</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Montant du loyer</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Loyer payé d'avance</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Explications pour trouver le domicile</div><div class="field-value-blank tall"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Impressions générales sur le ménage</div><div class="field-value-blank notes"></div></div></div>
        </div>

        <div class="section-title">III. Références du ménage</div>
        <table class="table-custom">
            <thead>
                <tr><th style="width: 15%;">Nom</th><th style="width: 25%;">Adresse complète</th><th style="width: 10%;">École</th><th style="width: 10%;">Fournisseur</th><th style="width: 10%;">Parents</th><th style="width: 10%;">Voisins</th><th style="width: 10%;">Autres</th><th style="width: 10%;">Tél.</th></tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 2; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">IV. Informations sur l'activité</div>
        <div class="subsection-title">A. Identification</div>
        <table class="table-custom">
            <tbody>
                <tr><td style="width: 25%;">Nom</td><td class="blank-cell"></td></tr>
                <tr><td>Activité</td><td class="blank-cell"></td></tr>
                <tr><td>Adresse complète de l'entreprise</td><td class="blank-cell"></td></tr>
                <tr><td>Depuis</td><td class="blank-cell"></td></tr>
                <tr><td>Historique de l'entreprise</td><td class="blank-cell" style="height: 48px;"></td></tr>
            </tbody>
        </table>

        <div class="subsection-title">B. Observations qualitatives sur l'entreprise</div>
        <div class="row-grid">
            <div class="col-4">
                <table class="table-custom">
                    <thead><tr><th>Actionnaires</th><th>%</th></tr></thead>
                    <tbody>@for($i = 0; $i < 6; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor</tbody>
                </table>
            </div>
            <div class="col-4">
                <table class="table-custom">
                    <thead><tr><th>Fournisseurs</th><th>%</th></tr></thead>
                    <tbody>@for($i = 0; $i < 6; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor</tbody>
                </table>
            </div>
            <div class="col-4">
                <table class="table-custom">
                    <thead><tr><th>Clientèles</th><th>%</th></tr></thead>
                    <tbody>@for($i = 0; $i < 6; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor</tbody>
                </table>
            </div>
            <div class="col-12"><div class="field-box"><div class="field-label">Commentaires : fréquence d'achat, vente à crédit, concurrence</div><div class="field-value-blank tall"></div></div></div>
        </div>

        <div class="subsection-title">C. Historique de crédit</div>
        <table class="table-custom">
            <thead><tr><th>Institution</th><th>Montant</th><th>Date</th><th>Retard cumulé</th></tr></thead>
            <tbody>@for($i = 0; $i < 4; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor</tbody>
        </table>

        <div class="subsection-title">D. Emplacement entreprise et ménage</div>
        <div class="two-columns">
            <div><div class="field-label">Emplacement entreprise</div><div class="field-value-blank notes"></div></div>
            <div><div class="field-label">Emplacement ménage (croquis)</div><div class="field-value-blank notes"></div></div>
        </div>

        <div class="page-break"></div>

        <div class="section-title">V. Bilan</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">A. Actif</div>
                <table class="table-custom">
                    <thead><tr><th>Rubrique</th><th>Date</th><th>Actuel</th><th>Ancien</th></tr></thead>
                    <tbody>
                        <tr><td>Cash</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Banque</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Épargne</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total liquidités</td><td></td><td></td><td></td></tr>
                        <tr><td>Créances</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Avance fournisseurs</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total actif circulant</td><td></td><td></td><td></td></tr>
                        <tr><td>Machines et outils</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Transport</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Bâtiments et terrain</td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total actif</td><td></td><td></td><td></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">B. Passif</div>
                <table class="table-custom">
                    <thead><tr><th>Rubrique</th><th>Dette fournisseur</th><th>Date</th><th>Actuel</th><th>Ancien</th></tr></thead>
                    <tbody>
                        <tr><td>Dettes fournisseurs</td><td></td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Crédit client en cours</td><td></td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Crédit à court terme</td><td></td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr><td>Dette à long terme</td><td></td><td></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total dettes</td><td colspan="4"></td></tr>
                        <tr><td>Fonds propres</td><td colspan="2"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total passif</td><td colspan="4"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="subsection-title">C. Détails sur le stock</div>
        <table class="table-custom">
            <thead><tr><th>Stock / matières premières</th><th>Quantité</th><th>Prix unitaire</th><th>Valeur totale</th></tr></thead>
            <tbody>@for($i = 0; $i < 6; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor<tr class="total-row"><td>Total</td><td colspan="3"></td></tr></tbody>
        </table>

        <div class="subsection-title">D. Détails sur les immobilisés</div>
        <table class="table-custom">
            <thead><tr><th>Actif immobilisé</th><th>Date d'achat</th><th>Valeur d'acquisition</th><th>Valeur actuelle</th></tr></thead>
            <tbody>@for($i = 0; $i < 5; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor<tr class="total-row"><td>Total</td><td colspan="3"></td></tr></tbody>
        </table>

        <div class="subsection-title">E. Capitalisation (à partir du 2nd crédit)</div>
        <table class="table-custom">
            <tbody>
                <tr><td style="width: 45%;">Profit accumulé entre 2 analyses</td><td class="blank-cell"></td></tr>
                <tr><td>Investissement non inclus dans le bilan</td><td class="blank-cell"></td></tr>
                <tr><td>Amortissement</td><td class="blank-cell"></td></tr>
                <tr><td>Changement du fonds propre</td><td class="blank-cell"></td></tr>
                <tr class="total-row"><td>Différence</td><td></td></tr>
            </tbody>
        </table>

        <div class="subsection-title">F. Investissement non inclus dans le bilan</div>
        <table class="table-custom">
            <thead><tr><th>Description</th><th>Montant / valeur</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 5; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
                <tr class="total-row"><td>Total</td><td colspan="2"></td></tr>
            </tbody>
        </table>

        <div class="field-box">
            <div class="field-label">Commentaires</div>
            <div class="field-value-blank tall"></div>
        </div>

        <div class="page-break"></div>

        <div class="section-title">VI. TFR</div>
        <div class="subsection-title">A. Les ventes</div>
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 16%;">Déclarations</th>
                    <th style="width: 8%;">Max.</th>
                    <th style="width: 8%;">Moy.</th>
                    <th style="width: 8%;">Min.</th>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                    <th>Samedi</th>
                    <th>Dimanche</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Par jour</td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                </tr>
                <tr>
                    <td>Historique récent</td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td colspan="7" class="blank-cell"></td>
                </tr>
            </tbody>
        </table>

        <div class="two-columns">
            <div>
                <table class="table-custom">
                    <thead><tr><th colspan="4">Fréquence - Ventes (a)</th></tr></thead>
                    <tbody>
                        <tr><td class="w-month-label">Janvier</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Avril</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Février</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Mai</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Mars</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Juin</td><td class="blank-cell w-month-input"></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <table class="table-custom">
                    <thead><tr><th colspan="4">Fréquence - Ventes (b)</th></tr></thead>
                    <tbody>
                        <tr><td class="w-month-label">Juillet</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Octobre</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Août</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Novembre</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Septembre</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Décembre</td><td class="blank-cell w-month-input"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="field-box"><div class="field-label">Ventes retenues (1)</div><div class="field-value-blank"></div></div>

        <div class="subsection-title">B. Les achats</div>
        <table class="table-custom">
            <thead>
                <tr>
                    <th style="width: 16%;">Déclarations</th>
                    <th style="width: 8%;">Max.</th>
                    <th style="width: 8%;">Moy.</th>
                    <th style="width: 8%;">Min.</th>
                    <th>Lundi</th>
                    <th>Mardi</th>
                    <th>Mercredi</th>
                    <th>Jeudi</th>
                    <th>Vendredi</th>
                    <th>Samedi</th>
                    <th>Dimanche</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Par jour</td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                </tr>
                <tr>
                    <td>Historique récent</td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td class="blank-cell"></td>
                    <td colspan="7" class="blank-cell"></td>
                </tr>
            </tbody>
        </table>

        <div class="two-columns">
            <div>
                <table class="table-custom">
                    <thead><tr><th colspan="4">Fréquence - Achats (a)</th></tr></thead>
                    <tbody>
                        <tr><td class="w-month-label">Janvier</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Avril</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Février</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Mai</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Mars</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Juin</td><td class="blank-cell w-month-input"></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <table class="table-custom">
                    <thead><tr><th colspan="4">Fréquence - Achats (b)</th></tr></thead>
                    <tbody>
                        <tr><td class="w-month-label">Juillet</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Octobre</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Août</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Novembre</td><td class="blank-cell w-month-input"></td></tr>
                        <tr><td class="w-month-label">Septembre</td><td class="blank-cell w-month-input"></td><td class="w-month-label">Décembre</td><td class="blank-cell w-month-input"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="subsection-title">Calcul CAMV</div>
        <table class="table-custom">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>PA</th>
                    <th>PV</th>
                    <th>Qté vendue</th>
                    <th>CA</th>
                    <th>Part CA</th>
                    <th>CMV</th>
                    <th>CMV pondéré</th>
                    <th>CAMV (c)</th>
                </tr>
            </thead>
            <tbody>
                @for($i = 0; $i < 8; $i++)
                    <tr>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                        <td class="blank-cell"></td>
                    </tr>
                @endfor
                <tr class="total-row"><td>Total</td><td colspan="8"></td></tr>
            </tbody>
        </table>

        <div style="width: 42%; margin-left: auto;">
            <table class="table-custom">
                <thead><tr><th colspan="2">Résumé</th></tr></thead>
                <tbody>
                    <tr><td>Ventes</td><td class="blank-cell"></td></tr>
                    <tr><td>Achats</td><td class="blank-cell"></td></tr>
                    <tr><td>Marge brute</td><td class="blank-cell"></td></tr>
                </tbody>
            </table>
        </div>

        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Achats retenus (2)</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Marge brute (1-2)</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Commentaires</div><div class="field-value-blank"></div></div></div>
        </div>

        <div class="subsection-title">C. Les ratios financiers</div>
        <table class="table-custom">
            <tbody>
                <tr><td style="width: 30%;">Fonds de roulement immédiat</td><td class="blank-cell" style="width: 20%;"></td><td style="width: 30%;">Liquidité générale</td><td class="blank-cell" style="width: 20%;"></td></tr>
                <tr><td>Indépendance financière</td><td class="blank-cell"></td><td>Rotation de stock</td><td class="blank-cell"></td></tr>
                <tr><td>Créances</td><td class="blank-cell"></td><td>Profitabilité nette</td><td class="blank-cell"></td></tr>
                <tr><td>Solvabilité</td><td class="blank-cell"></td><td>Capitaux propres / Dettes</td><td class="blank-cell"></td></tr>
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">VII. Charges et revenu du ménage</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">A. Charges d'entreprise</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td class="w-input-wide">Loyer</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Personnel</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Charges du personnel</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td>Transport</td><td class="blank-cell"></td></tr>
                        <tr><td>Eau et électricité</td><td class="blank-cell"></td></tr>
                        <tr><td>Entretien</td><td class="blank-cell"></td></tr>
                        <tr><td>Communication</td><td class="blank-cell"></td></tr>
                        <tr><td>Fret et douane</td><td class="blank-cell"></td></tr>
                        <tr><td>Charges d'exploitation</td><td class="blank-cell"></td></tr>
                        <tr><td>Autres charges</td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total charges entreprise</td><td></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">B. Dépenses du ménage</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td class="w-input-wide">Loyer</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Nourriture</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td class="w-input-wide">Éducation</td><td class="blank-cell w-input-equal"></td></tr>
                        <tr><td>Eau et électricité</td><td class="blank-cell"></td></tr>
                        <tr><td>Habillement</td><td class="blank-cell"></td></tr>
                        <tr><td>Communication</td><td class="blank-cell"></td></tr>
                        <tr><td>Transport</td><td class="blank-cell"></td></tr>
                        <tr><td>Partage</td><td class="blank-cell"></td></tr>
                        <tr class="total-row"><td>Total dépenses du ménage</td><td></td></tr>
                        <tr><td>Marge (10%)</td><td class="blank-cell"></td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Source du revenu du ménage</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Montant</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Périodicité</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Commentaires</div><div class="field-value-blank tall"></div></div></div>
        </div>

        <div class="subsection-title">Calcul du coût de production (pour activité de production)</div>
        <table class="table-custom">
            <thead><tr><th>Produit</th><th>Coût ingrédient</th><th>PV</th><th>Qté vendue</th><th>CA</th><th>Part CA</th><th>CV%</th><th>CX%</th></tr></thead>
            <tbody>@for($i = 0; $i < 7; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor<tr class="total-row"><td>Total</td><td colspan="7"></td></tr></tbody>
        </table>

        <div class="section-title">VIII. Détail des garanties</div>
        <div class="subsection-title">A. Biens de l'emprunteur</div>
        <table class="table-custom">
            <thead><tr><th>Nature</th><th>Description</th><th>Valeur</th></tr></thead>
            <tbody>@for($i = 0; $i < 10; $i++)<tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>@endfor</tbody>
        </table>
        <div class="subsection-title">B. Détails du bien immeuble mis en garantie</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom du propriétaire</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Type de document</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Valeur marchande</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Références / Adresse</div><div class="field-value-blank tall"></div></div></div>
        </div>
        <div class="subsection-title">C. Informations sur le codébiteur</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Post-nom / Prénom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">N° téléphone</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Pièce d'identité</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Occupation</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Revenu</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Adresse</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Relation avec l'emprunteur</div><div class="field-value-blank"></div></div></div>
        </div>

        <div class="section-title">IX. Proposition de l'agent de crédit</div>
        <div class="field-box"><div class="field-label">Conclusions finales : impressions générales, risques, perspectives</div><div class="field-value-blank notes"></div></div>
        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Montant du crédit</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Taux</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Maturité</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Période de grâce</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Modalités de remboursement</div><div class="field-value-blank"><div class="checkbox-line">[ ] Période &nbsp;&nbsp; [ ] Montant &nbsp;&nbsp; [ ] Raison</div></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Explication sur paiement irrégulier</div><div class="field-value-blank"></div></div></div>
        </div>

        <p style="font-size: 9px; margin-top: 18px;">
            Le soussigné a personnellement vérifié les documents, évaluations et garanties associés au compte et certifie leur conformité.
            Il accepte la pleine responsabilité de l'évaluation du crédit du point de vue de son exactitude et de l'absence d'omission d'éléments pertinents pour la décision de crédit.
        </p>

        <div class="signatures-container">
            <div class="signature-box">
                <div class="signature-title">L'agent de crédit</div>
                <div style="font-size: 8px; color: #666;">Nom, signature et date</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">L'emprunteur</div>
                <div style="font-size: 8px; color: #666;">Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-title">Le comité de crédit</div>
                <div style="font-size: 8px; color: #666;">Avis et visa</div>
            </div>
        </div>
    </div>
</body>
</html>
