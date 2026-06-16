<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche terrain vierge - Analyse de credit MPME</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #333; line-height: 1.35; font-size: 9px; margin: 0; padding: 20px; background: #f9f9f9; }
        .container { max-width: 820px; margin: 0 auto; background: #fff; padding: 28px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,.05); }
        .no-print-bar { max-width: 820px; margin: 0 auto 20px; background: #1e293b; color: #fff; padding: 10px 20px; display: flex; justify-content: space-between; align-items: center; border-radius: 6px; }
        .btn-back { color: #cbd5e1; text-decoration: none; font-size: 11px; }
        .btn-print { background: #eab308; color: #1e293b; border: 0; padding: 6px 15px; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; font-size: 10px; }
        .header-table, .table-custom { width: 100%; border-collapse: collapse; }
        .header-table { margin-bottom: 12px; }
        .header-table td { border: none !important; padding: 0 !important; }
        .header-logo { width: 80px; height: auto; }
        .header-title { text-align: center; }
        .header-title h2 { margin: 0; font-size: 16px; color: #bd8a12; font-weight: bold; letter-spacing: .5px; }
        .header-title p { margin: 3px 0 0; font-size: 9px; color: #666; }
        .header-meta { text-align: right; font-size: 9px; color: #555; }
        .divider { border: 0; border-bottom: 2px solid #eab308; margin: 10px 0 15px; }
        .document-title-container { background: #bd8a12; color: #fff; text-align: center; padding: 8px 15px; border-radius: 4px; margin-bottom: 12px; }
        .document-title { margin: 0; font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .notice { border: 1px solid #facc15; background: #fefce8; color: #713f12; padding: 8px; border-radius: 4px; margin-bottom: 12px; font-size: 9px; }
        .section-title { font-size: 10px; font-weight: bold; color: #bd8a12; text-transform: uppercase; border-bottom: 1px solid #bd8a12; padding-bottom: 3px; margin: 16px 0 8px; letter-spacing: .5px; }
        .subsection-title { font-size: 9px; font-weight: bold; color: #333; text-transform: uppercase; margin: 10px 0 5px; }
        .row-grid { display: flex; flex-wrap: wrap; margin-bottom: 8px; }
        .col-3 { width: 25%; box-sizing: border-box; padding-right: 10px; }
        .col-4 { width: 33.33%; box-sizing: border-box; padding-right: 10px; }
        .col-6 { width: 50%; box-sizing: border-box; padding-right: 10px; }
        .col-12 { width: 100%; box-sizing: border-box; padding-right: 10px; }
        .field-box { margin-bottom: 7px; }
        .field-label { font-weight: bold; color: #555; margin-bottom: 3px; font-size: 8px; text-transform: uppercase; }
        .field-value-blank { border: 1px solid #bbb; min-height: 21px; border-radius: 2px; background: #fafafa; }
        .field-value-blank.tall { min-height: 48px; }
        .field-value-blank.notes { min-height: 72px; }
        .table-custom { margin-bottom: 11px; }
        .table-custom th, .table-custom td { border: 1px solid #aaa; padding: 4px 6px; text-align: left; vertical-align: middle; }
        .table-custom th { background: #f3f4f6; color: #0a802e; font-weight: bold; font-size: 8px; text-transform: uppercase; }
        .blank-cell { height: 20px; background: #fafafa; }
        .total-row td { background: #f3f4f6; font-weight: bold; }
        .calc-row td { background: #eef6ff; color: #1d4ed8; font-weight: bold; }
        .two-columns { display: flex; gap: 12px; align-items: flex-start; }
        .two-columns > div { width: 50%; }
        .required { color: #b91c1c; font-weight: bold; }
        .hint { color: #64748b; font-size: 8px; font-style: italic; }
        .page-break { page-break-before: always; }
        .signatures-container { display: flex; margin-top: 22px; justify-content: space-between; }
        .signature-box { width: 30%; border: 1px dashed #999; border-radius: 4px; padding: 10px; text-align: center; min-height: 75px; box-sizing: border-box; }
        .signature-title { font-weight: bold; font-size: 9px; color: #555; text-transform: uppercase; margin-bottom: 38px; }
        @media print {
            body { background: #fff; padding: 0; font-size: 9px; }
            .container { box-shadow: none; padding: 0; max-width: 100%; }
            .no-print-bar { display: none !important; }
            .document-title-container, .table-custom th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }
    </style>
</head>
<body>
    <div class="no-print-bar">
        <a href="{{ route('credit.applications.list') }}" class="btn-back">&larr; Retour a la liste</a>
        <div style="font-weight: bold; font-size: 12px; color: #e2e8f0;">Fiche terrain vierge - Nouvelle analyse credit</div>
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
                    <p>Tel : {{ $company->phone ?? env('APP_PHONE', '+243...') }} | Email : {{ $company->email ?? env('APP_EMAIL', 'info@maishabora.com') }}</p>
                    <p>RCCM : {{ $company->rccm ?? env('APP_RCCM', 'RDC/GOM/...') }}</p>
                </td>
                <td style="width: 20%;" class="header-meta">
                    <strong>Date imp. :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Heure imp. :</strong> {{ now()->format('H:i') }}<br>
                    <strong>Agent :</strong> {{ Auth::user()->name ?? '' }} {{ Auth::user()->postnom ?? '' }}
                </td>
            </tr>
        </table>

        <hr class="divider">

        <div class="document-title-container">
            <h3 class="document-title">Fiche terrain de collecte - Analyse de credit MPME</h3>
        </div>

        <div class="notice">
            Les champs marques <span class="required">*</span> sont obligatoires dans le systeme.
            Ils evitent les calculs incomplets, les ratios impossibles et les analyses basees sur zero.
            Les lignes indiquees <strong>calcule par le systeme</strong> ne sont pas a completer par l'agent.
        </div>

        <div class="section-title">0. Identification du dossier</div>
        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Date de la visite <span class="required">*</span></div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Agent de credit</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Taux 1 USD = CDF <span class="hint">obligatoire si CDF</span></div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">N credit / N client</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Nom complet du client</div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Monnaie analyse</div><div class="field-value-blank"><span class="hint">Cocher : USD / CDF</span></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Montant sollicite</div><div class="field-value-blank"></div></div></div>
        </div>

        <div class="section-title">I. Resume rapide a encoder</div>
        <div class="two-columns">
            <div>
                <div class="subsection-title">A. Revenu et TFR</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td>Activite principale <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr><td>Marge estimee de l'entreprise (%)</td><td class="blank-cell"></td></tr>
                        <tr><td>Nombre d'employes</td><td class="blank-cell"></td></tr>
                        <tr><td>Ventes cash</td><td class="blank-cell"></td></tr>
                        <tr><td>Ventes a credit</td><td class="blank-cell"></td></tr>
                        <tr><td>Ventes retenues pour analyse <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr><td>Achats retenus / CAMV <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr><td>Charges entreprise totales <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr><td>Revenus du menage</td><td class="blank-cell"></td></tr>
                        <tr><td>Depenses menage totales <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr class="calc-row"><td>Marge brute</td><td>Calcule par le systeme</td></tr>
                        <tr class="calc-row"><td>Revenu disponible</td><td>Calcule par le systeme</td></tr>
                        <tr class="calc-row"><td>Capacite remboursement</td><td>Calcule par le systeme</td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">B. Bilan</div>
                <table class="table-custom">
                    <tbody>
                        <tr><td>Cash en caisse <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr><td>Banque</td><td class="blank-cell"></td></tr>
                        <tr><td>Epargne</td><td class="blank-cell"></td></tr>
                        <tr><td>Creances</td><td class="blank-cell"></td></tr>
                        <tr><td>Avances fournisseurs</td><td class="blank-cell"></td></tr>
                        <tr><td>Stock <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr><td>Machines et outils</td><td class="blank-cell"></td></tr>
                        <tr><td>Transport</td><td class="blank-cell"></td></tr>
                        <tr><td>Batiments et terrain</td><td class="blank-cell"></td></tr>
                        <tr><td>Dettes fournisseurs</td><td class="blank-cell"></td></tr>
                        <tr><td>Credit client en cours</td><td class="blank-cell"></td></tr>
                        <tr><td>Dette court terme</td><td class="blank-cell"></td></tr>
                        <tr><td>Dette long terme</td><td class="blank-cell"></td></tr>
                        <tr><td>Fonds propres <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr class="calc-row"><td>Total actif / total dettes / passif</td><td>Calcule par le systeme</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="subsection-title">C. Destination du credit et plan d'investissement</div>
        <table class="table-custom">
            <thead><tr><th>Destination precise</th><th>Montant</th><th>Debut prevu</th><th>Fin prevue</th><th>Part client</th><th>Part Maisga Bora</th><th>Part tiers</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 5; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">II. Informations sur le menage</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Post-nom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Prenom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Province d'origine</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Niveau d'education</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Religion</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Biographie rapide</div><div class="field-value-blank tall"></div></div></div>
        </div>

        <div class="subsection-title">A. Structure familiale</div>
        <table class="table-custom">
            <thead><tr><th>Nom</th><th>Lien avec client</th><th>Occupation</th><th>Observations utiles</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 5; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="subsection-title">B. Habitation</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Type habitation</div><div class="field-value-blank"><span class="hint">Propriete / familiale / location</span></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Valeur habitation</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Temps de residence</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Montant loyer mensuel</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Loyer paye d'avance</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Explications pour trouver le domicile</div><div class="field-value-blank tall"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Impressions generales sur le menage</div><div class="field-value-blank notes"></div></div></div>
        </div>

        <div class="section-title">III. References du menage</div>
        <table class="table-custom">
            <thead><tr><th>Nom</th><th>Adresse complete</th><th>Telephone</th><th>Type de reference</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 4; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"><span class="hint">ecole / fournisseur / parent / voisin / autre</span></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="section-title">IV. Informations sur l'activite</div>
        <table class="table-custom">
            <tbody>
                <tr><td style="width: 28%;">Nom entreprise / commerce</td><td class="blank-cell"></td></tr>
                <tr><td>Activite exercee <span class="required">*</span></td><td class="blank-cell"></td></tr>
                <tr><td>Adresse complete entreprise <span class="required">*</span></td><td class="blank-cell"></td></tr>
                <tr><td>Date ou periode de debut</td><td class="blank-cell"></td></tr>
                <tr><td>Nombre d'employes</td><td class="blank-cell"></td></tr>
                <tr><td>Marge estimee (%)</td><td class="blank-cell"></td></tr>
                <tr><td>Historique de l'entreprise</td><td class="blank-cell" style="height: 42px;"></td></tr>
                <tr><td>Observations qualitatives</td><td class="blank-cell" style="height: 42px;"></td></tr>
                <tr><td>Commentaires achat, vente a credit, concurrence</td><td class="blank-cell" style="height: 42px;"></td></tr>
            </tbody>
        </table>

        <div class="subsection-title">Historique de credit</div>
        <table class="table-custom">
            <thead><tr><th>Institution</th><th>Montant</th><th>Statut actuel</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 4; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">V. Details du bilan a encoder</div>
        <div class="subsection-title">A. Details sur le stock</div>
        <table class="table-custom">
            <thead><tr><th>Description article</th><th>Prix achat</th><th>Prix vente</th><th>Quantite</th><th>Montant / valeur</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 6; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="subsection-title">B. Details sur les immobilises</div>
        <table class="table-custom">
            <thead><tr><th>Description bien</th><th>Prix achat</th><th>Prix vente / valeur</th><th>Quantite</th><th>Montant</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 5; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="subsection-title">C. Investissement non inclus dans le bilan</div>
        <table class="table-custom">
            <thead><tr><th>Description</th><th>Montant / valeur</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 4; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>
        <div class="field-box"><div class="field-label">Commentaires bilan</div><div class="field-value-blank tall"></div></div>

        <div class="section-title">VI. TFR detaille et charges</div>
        <div class="subsection-title">A. Calcul CAMV / achats retenus</div>
        <table class="table-custom">
            <thead><tr><th>Article</th><th>Prix achat</th><th>Prix vente</th><th>Quantite vendue</th><th>Montant</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 8; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="two-columns">
            <div>
                <div class="subsection-title">B. Charges entreprise</div>
                <table class="table-custom">
                    <tbody>
                        @foreach(['Loyer', 'Personnel', 'Transport', 'Eau et electricite', 'Communication', 'Autres charges'] as $label)
                            <tr><td>{{ $label }}</td><td class="blank-cell"></td></tr>
                        @endforeach
                        <tr class="total-row"><td>Total charges entreprise <span class="required">*</span></td><td class="blank-cell"></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subsection-title">C. Depenses menage</div>
                <table class="table-custom">
                    <tbody>
                        @foreach(['Loyer', 'Nourriture', 'Education', 'Eau et electricite', 'Transport', 'Partage'] as $label)
                            <tr><td>{{ $label }}</td><td class="blank-cell"></td></tr>
                        @endforeach
                        <tr class="total-row"><td>Total depenses menage <span class="required">*</span></td><td class="blank-cell"></td></tr>
                        <tr class="calc-row"><td>Marge securite 10%</td><td>Calcule par le systeme</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Source du revenu menage</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Montant revenu menage</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Periodicite</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">Commentaires TFR</div><div class="field-value-blank tall"></div></div></div>
        </div>

        <div class="subsection-title">D. Cout de production si activite de production</div>
        <table class="table-custom">
            <thead><tr><th>Produit</th><th>Cout / prix achat</th><th>Prix vente</th><th>Quantite</th><th>Montant</th><th>Observations</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 6; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="page-break"></div>

        <div class="section-title">VII. Garanties</div>
        <div class="subsection-title">A. Biens proposes en garantie</div>
        <table class="table-custom">
            <thead><tr><th>Type garantie</th><th>Nature du bien</th><th>Description precise</th><th>Valeur estimee</th><th>Proprietaire</th></tr></thead>
            <tbody>
                @for($i = 0; $i < 8; $i++)
                    <tr><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td><td class="blank-cell"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="subsection-title">B. Bien immeuble mis en garantie</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom du proprietaire</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Type de document</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Valeur marchande</div><div class="field-value-blank"></div></div></div>
            <div class="col-12"><div class="field-box"><div class="field-label">References / adresse</div><div class="field-value-blank tall"></div></div></div>
        </div>

        <div class="subsection-title">C. Informations sur le codebiteur</div>
        <div class="row-grid">
            <div class="col-4"><div class="field-box"><div class="field-label">Nom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Post-nom / prenom</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Telephone</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Piece d'identite</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Occupation</div><div class="field-value-blank"></div></div></div>
            <div class="col-4"><div class="field-box"><div class="field-label">Revenu</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Adresse</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Relation avec emprunteur</div><div class="field-value-blank"></div></div></div>
        </div>

        <div class="section-title">VIII. Proposition de l'agent de credit</div>
        <div class="field-box"><div class="field-label">Conclusions finales : impressions, risques, perspectives <span class="required">*</span></div><div class="field-value-blank notes"></div></div>
        <div class="row-grid">
            <div class="col-3"><div class="field-box"><div class="field-label">Montant propose <span class="required">*</span></div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Taux propose (%) <span class="required">*</span></div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Maturite en mois <span class="required">*</span></div><div class="field-value-blank"></div></div></div>
            <div class="col-3"><div class="field-box"><div class="field-label">Periode de grace en mois</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Modalites de remboursement</div><div class="field-value-blank"></div></div></div>
            <div class="col-6"><div class="field-box"><div class="field-label">Explication si paiement irregulier</div><div class="field-value-blank"></div></div></div>
        </div>

        <p style="font-size: 9px; margin-top: 18px;">
            L'agent certifie avoir verifie les informations collectees, les documents presentes, les garanties et les elements utiles a la decision de credit.
        </p>

        <div class="signatures-container">
            <div class="signature-box"><div class="signature-title">L'agent de credit</div><div style="font-size: 8px; color: #666;">Nom, signature et date</div></div>
            <div class="signature-box"><div class="signature-title">L'emprunteur</div><div style="font-size: 8px; color: #666;">Signature</div></div>
            <div class="signature-box"><div class="signature-title">Le comite de credit</div><div style="font-size: 8px; color: #666;">Avis et visa</div></div>
        </div>
    </div>
</body>
</html>
