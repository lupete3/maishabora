<div>
    {{-- Style CSS dédié pour empêcher l'écrasement des inputs dans les tableaux HTML --}}
    <style>
        .table-responsive .form-control-sm,
        .table-responsive .form-select-sm {
            min-width: 120px;
        }
        .table-responsive th, 
        .table-responsive td {
            white-space: nowrap;
            vertical-align: middle;
        }
    </style>
    
    @if (session()->has('message'))
        <div class="alert alert-success">{{ session('message') }}</div>
    @endif

    <div class="alert alert-warning">
        <strong>Champs requis pour une analyse fiable :</strong>
        date de visite, activite, adresse de l entreprise, ventes retenues, achats retenus, charges entreprise,
        depenses menage, cash, stock, fonds propres, conclusion agent, montant, taux et maturite proposes.
    </div>

    <form wire:submit.prevent="save">
        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Entete et menage</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Date de visite *</label>
                        <input type="date" class="form-control" wire:model="visit.visit_date">
                        @error('visit.visit_date') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Taux 1 USD = CDF {{ $loan->currency === 'CDF' ? '*' : '' }}</label>
                        <input type="number" step="0.0001" class="form-control" wire:model="visit.usd_cdf_rate">
                        @error('visit.usd_cdf_rate') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">N credit / client</label>
                        <input type="text" class="form-control" wire:model="visit.credit_number">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Type habitation</label>
                        <select class="form-select" wire:model="visit.housing_type">
                            <option value="">Non renseigne</option>
                            <option value="propriete">Propriete</option>
                            <option value="propriete_familiale">Propriete familiale</option>
                            <option value="location">Location</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Province d origine</label>
                        <input type="text" class="form-control" wire:model="visit.origin_province">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Niveau d education</label>
                        <input type="text" class="form-control" wire:model="visit.education_level">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Religion</label>
                        <input type="text" class="form-control" wire:model="visit.religion">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valeur habitation</label>
                        <input type="number" step="0.01" class="form-control" wire:model="visit.housing_value">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Temps residence</label>
                        <input type="text" class="form-control" wire:model="visit.residence_duration">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loyer mensuel</label>
                        <input type="number" step="0.01" class="form-control" wire:model="visit.monthly_rent">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Loyer paye avance</label>
                        <input type="number" step="0.01" class="form-control" wire:model="visit.rent_paid_in_advance">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Biographie rapide</label>
                        <textarea class="form-control" rows="2" wire:model="visit.quick_biography"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Impressions generales menage</label>
                        <textarea class="form-control" rows="2" wire:model="visit.household_impressions"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Explications pour trouver le domicile</label>
                        <textarea class="form-control" rows="2" wire:model="visit.home_directions"></textarea>
                    </div>
                </div>

                <h6 class="mt-4">Structure familiale</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Nom</th><th>Lien</th><th>Occupation</th><th>Observations</th><th style="width: 50px;">Action</th></tr></thead>
                        <tbody>
                            @foreach($familyMembers as $i => $row)
                                <tr>
                                    <td><input class="form-control form-control-sm" wire:model="familyMembers.{{ $i }}.name"></td>
                                    <td><input class="form-control form-control-sm" wire:model="familyMembers.{{ $i }}.relationship"></td>
                                    <td><input class="form-control form-control-sm" wire:model="familyMembers.{{ $i }}.occupation"></td>
                                    <td><input class="form-control form-control-sm" wire:model="familyMembers.{{ $i }}.observations"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeFamilyMember({{ $i }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" wire:click="addFamilyMember">
                        <i class="bx bx-plus"></i> Ajouter un membre
                    </button>
                </div>

                <h6 class="mt-4">References du menage</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Nom</th><th>Adresse</th><th>Telephone</th><th>Type</th><th style="width: 50px;">Action</th></tr></thead>
                        <tbody>
                            @foreach($householdReferences as $i => $row)
                                <tr>
                                    <td><input class="form-control form-control-sm" wire:model="householdReferences.{{ $i }}.name"></td>
                                    <td><input class="form-control form-control-sm" wire:model="householdReferences.{{ $i }}.address"></td>
                                    <td><input class="form-control form-control-sm" wire:model="householdReferences.{{ $i }}.phone"></td>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model="householdReferences.{{ $i }}.reference_type">
                                            <option value="ecole">Ecole</option>
                                            <option value="fournisseur">Fournisseur</option>
                                            <option value="parent">Parent</option>
                                            <option value="voisin">Voisin</option>
                                            <option value="autre">Autre</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeHouseholdReference({{ $i }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" wire:click="addHouseholdReference">
                        <i class="bx bx-plus"></i> Ajouter une référence
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Activite et plan d investissement</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Nom entreprise</label>
                        <input class="form-control" wire:model="businessProfile.business_name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Activite *</label>
                        <input class="form-control" wire:model="businessProfile.activity">
                        @error('businessProfile.activity') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Depuis</label>
                        <input type="date" class="form-control" wire:model="businessProfile.started_at">
                    </div>
                    <div class="col-md-8">
                        <label class="form-label">Adresse complete entreprise *</label>
                        <input class="form-control" wire:model="businessProfile.full_address">
                        @error('businessProfile.full_address') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Employes</label>
                        <input type="number" class="form-control" wire:model="businessProfile.employees_count">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Marge %</label>
                        <input type="number" step="0.01" class="form-control" wire:model="businessProfile.business_margin_percent">
                    </div>
                    {{-- <div class="col-md-6">
                        <label class="form-label">Historique entreprise</label>
                        <textarea class="form-control" rows="2" wire:model="businessProfile.business_history"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Observations qualitatives</label>
                        <textarea class="form-control" rows="2" wire:model="businessProfile.qualitative_observations"></textarea>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Commentaires achat, vente a credit, concurrence</label>
                        <textarea class="form-control" rows="2" wire:model="businessProfile.purchase_sales_competition_comments"></textarea>
                    </div> --}}
                </div>

                {{-- <h6 class="mt-4">Destination du credit</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Destination</th><th>Montant</th><th>Debut</th><th>Fin</th><th>Part client</th><th>Part MAISHA BORA</th><th>Part tiers</th><th style="width: 50px;">Action</th></tr></thead>
                        <tbody>
                            @foreach($investmentPlanItems as $i => $row)
                                <tr>
                                    <td><input class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.destination"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.amount"></td>
                                    <td><input type="date" class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.starts_on"></td>
                                    <td><input type="date" class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.ends_on"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.client_share"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.institution_share"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="investmentPlanItems.{{ $i }}.third_party_share"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeInvestmentPlanItem({{ $i }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" wire:click="addInvestmentPlanItem">
                        <i class="bx bx-plus"></i> Ajouter un plan
                    </button>
                </div> --}}
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Bilan et TFR</h5></div>
            <div class="card-body">
                <h6>Bilan</h6>
                <div class="row g-3">
                    @foreach([
                        'cash' => 'Cash *', 'bank' => 'Banque', 'savings' => 'Epargne Maisha Bora', 'receivables' => 'Creances',
                        'supplier_advances' => 'Avance fournisseurs', 'stock' => 'Stock *', 'machines_tools' => 'Machines et outils',
                        'transport_assets' => 'Transport', 'buildings_land' => 'Batiments et terrain', 'supplier_debts' => 'Dettes fournisseurs',
                        'current_customer_credit' => 'Credit client en cours', 'short_term_debt' => 'Dette court terme',
                        'long_term_debt' => 'Dette long terme', 'equity' => 'Fonds propres *'
                    ] as $field => $label)
                        <div class="col-md-3">
                            <label class="form-label">{{ $label }}</label>
                            <input type="number" step="0.01" class="form-control" wire:model.live="balance.{{ $field }}">
                            @error('balance.' . $field) <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    @endforeach
                    <div class="col-md-4"><div class="alert alert-secondary mb-0">Total actif: {{ number_format($balanceTotals['total_assets'], 2) }}</div></div>
                    <div class="col-md-4"><div class="alert alert-secondary mb-0">Total dettes: {{ number_format($balanceTotals['total_debts'], 2) }}</div></div>
                    <div class="col-md-4"><div class="alert alert-secondary mb-0">Passif total: {{ number_format($balanceTotals['total_liabilities_equity'], 2) }}</div></div>
                    {{-- <div class="col-12">
                        <label class="form-label">Commentaires bilan</label>
                        <textarea class="form-control" rows="2" wire:model="balance.comments"></textarea>
                    </div> --}}
                </div>

                <h6 class="mt-4">TFR et charges</h6>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Ventes cash</label>
                        <input type="number" step="0.01" class="form-control" wire:model="cashflow.cash_sales">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ventes credit (créances)</label>
                        <input type="number" step="0.01" class="form-control" wire:model="cashflow.credit_sales">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Ventes retenues *</label>
                        <input type="number" step="0.01" class="form-control" wire:model.live="cashflow.retained_sales">
                        @error('cashflow.retained_sales') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Achats retenus *</label>
                        <input type="number" step="0.01" class="form-control" wire:model.live="cashflow.retained_purchases">
                        @error('cashflow.retained_purchases') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Charges entreprise *</label>
                        <input type="number" step="0.01" class="form-control" wire:model.live="cashflow.business_expenses_total">
                        @error('cashflow.business_expenses_total') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Revenu menage</label>
                        <input type="number" step="0.01" class="form-control" wire:model.live="cashflow.household_income">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Depenses menage *</label>
                        <input type="number" step="0.01" class="form-control" wire:model.live="cashflow.household_expenses_total">
                        @error('cashflow.household_expenses_total') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Source revenu menage</label>
                        <input class="form-control" wire:model="cashflow.household_income_source">
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            Marge brute: {{ number_format($cashflowTotals['gross_margin'], 2) }}
                            <div class="small text-muted mt-1">Formule: Ventes retenues − Achats retenues</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            Revenu disponible: {{ number_format($cashflowTotals['available_income'], 2) }}
                            <div class="small text-muted mt-1">Formule: Marge brute − Charges d'activité + Revenu ménage − Dépenses ménage</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0">
                            Capacité remboursement: {{ number_format($cashflowTotals['repayment_capacity'], 2) }}
                            <div class="small text-muted mt-1">Formule: Revenu disponible × 65%</div>
                        </div>
                    </div>
                    {{-- <div class="col-12">
                        <label class="form-label">Commentaires TFR</label>
                        <textarea class="form-control" rows="2" wire:model="cashflow.comments"></textarea>
                    </div> --}}
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Details complementaires</h5></div>
            <div class="card-body">
                <h6>Charges detaillees entreprise</h6>
                <div class="row g-2">
                    @foreach($businessExpenses as $i => $row)
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <input class="form-control" wire:model="businessExpenses.{{ $i }}.label">
                                <input type="number" step="0.01" class="form-control" wire:model="businessExpenses.{{ $i }}.amount">
                            </div>
                        </div>
                    @endforeach
                </div>

                <h6 class="mt-4">Depenses detaillees menage</h6>
                <div class="row g-2">
                    @foreach($householdExpenses as $i => $row)
                        <div class="col-md-6">
                            <div class="input-group input-group-sm">
                                <input class="form-control" wire:model="householdExpenses.{{ $i }}.label">
                                <input type="number" step="0.01" class="form-control" wire:model="householdExpenses.{{ $i }}.amount">
                            </div>
                        </div>
                    @endforeach
                </div>

                <h6 class="mt-4">Stock, immobilises, CAMV ou production</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Section</th><th>Description</th><th>PA</th><th>PV</th><th>Qte</th><th>Montant</th>
                            {{-- <th>Observations</th> --}}
                            <th style="width: 50px;">Action</th></tr></thead>
                        <tbody>
                            @foreach($inventoryItems as $i => $row)
                                <tr>
                                    <td>
                                        <select class="form-select form-select-sm" wire:model="inventoryItems.{{ $i }}.section">
                                            <option value="stock">Stock</option>
                                            <option value="fixed_asset">Immobilise</option>
                                            <option value="camv">CAMV</option>
                                            <option value="production_cost">Production</option>
                                            <option value="off_balance_investment">Invest. hors bilan</option>
                                        </select>
                                    </td>
                                    <td><input class="form-control form-control-sm" wire:model="inventoryItems.{{ $i }}.description"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="inventoryItems.{{ $i }}.purchase_price"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="inventoryItems.{{ $i }}.sale_price"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="inventoryItems.{{ $i }}.quantity"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="inventoryItems.{{ $i }}.amount"></td>
                                    {{-- <td><input class="form-control form-control-sm" wire:model="inventoryItems.{{ $i }}.observations"></td> --}}
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeInventoryItem({{ $i }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" wire:click="addInventoryItem">
                        <i class="bx bx-plus"></i> Ajouter un article
                    </button>
                </div>

                <h6 class="mt-4">Historique de credit</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Institution</th><th>Montant</th><th>Statut</th><th>Observations</th><th style="width: 50px;">Action</th></tr></thead>
                        <tbody>
                            @foreach($creditHistories as $i => $row)
                                <tr>
                                    <td><input class="form-control form-control-sm" wire:model="creditHistories.{{ $i }}.institution"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="creditHistories.{{ $i }}.amount"></td>
                                    <td><input class="form-control form-control-sm" wire:model="creditHistories.{{ $i }}.status"></td>
                                    <td><input class="form-control form-control-sm" wire:model="creditHistories.{{ $i }}.observations"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeCreditHistory({{ $i }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" wire:click="addCreditHistory">
                        <i class="bx bx-plus"></i> Ajouter un historique
                    </button>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h5 class="mb-0">Garanties et proposition agent</h5></div>
            <div class="card-body">
                <h6>Biens de l emprunteur</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered">
                        <thead><tr><th>Type</th><th>Nature</th><th>Description</th><th>Valeur</th><th>Proprietaire</th><th style="width: 50px;">Action</th></tr></thead>
                        <tbody>
                            @foreach($securities as $i => $row)
                                <tr>
                                    <td><input class="form-control form-control-sm" wire:model="securities.{{ $i }}.type"></td>
                                    <td><input class="form-control form-control-sm" wire:model="securities.{{ $i }}.nature_bien"></td>
                                    <td><input class="form-control form-control-sm" wire:model="securities.{{ $i }}.description"></td>
                                    <td><input type="number" step="0.01" class="form-control form-control-sm" wire:model="securities.{{ $i }}.valeur_estimee"></td>
                                    <td><input class="form-control form-control-sm" wire:model="securities.{{ $i }}.proprietaire"></td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger" wire:click="removeSecurity({{ $i }})">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-xs btn-outline-primary mt-1" wire:click="addSecurity">
                        <i class="bx bx-plus"></i> Ajouter une garantie
                    </button>
                </div>

                <div class="row g-3 mt-2">
                    <div class="col-md-4">
                        <label class="form-label">Proprietaire immeuble</label>
                        <input class="form-control" wire:model="collateralProperty.owner_name">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Type document</label>
                        <input class="form-control" wire:model="collateralProperty.document_type">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Valeur marchande</label>
                        <input type="number" step="0.01" class="form-control" wire:model="collateralProperty.market_value">
                    </div>
                    <div class="col-12">
                        <label class="form-label">References / adresse immeuble</label>
                        <textarea class="form-control" rows="2" wire:model="collateralProperty.address_references"></textarea>
                    </div>
                </div>

                <h6 class="mt-4">Codebiteur</h6>
                <div class="row g-3">
                    @foreach([
                        'name' => 'Nom', 'postnom_prenom' => 'Post-nom / prenom', 'phone' => 'Telephone',
                        'identity_document' => 'Piece identite', 'occupation' => 'Occupation', 'income' => 'Revenu',
                        'address' => 'Adresse', 'relationship' => 'Relation'
                    ] as $field => $label)
                        <div class="col-md-3">
                            <label class="form-label">{{ $label }}</label>
                            <input class="form-control" @if($field === 'income') type="number" step="0.01" @endif wire:model="coBorrower.{{ $field }}">
                        </div>
                    @endforeach
                </div>

                <h6 class="mt-4">Proposition de l agent</h6>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Conclusions finales *</label>
                        <textarea class="form-control" rows="3" wire:model="proposal.final_conclusions"></textarea>
                        @error('proposal.final_conclusions') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Montant propose *</label>
                        <input type="number" step="0.01" class="form-control" wire:model="proposal.proposed_amount">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Taux propose *</label>
                        <input type="number" step="0.01" class="form-control" wire:model="proposal.proposed_rate">
                        @error('proposal.proposed_rate') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Maturite mois *</label>
                        <input type="number" class="form-control" wire:model="proposal.proposed_maturity_months">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Grace mois</label>
                        <input type="number" class="form-control" wire:model="proposal.grace_period_months">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Modalites remboursement</label>
                        <input class="form-control" wire:model="proposal.repayment_modality">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Explication paiement irregulier</label>
                        <input class="form-control" wire:model="proposal.irregular_payment_explanation">
                    </div>
                </div>
            </div>
        </div>

        <div class="text-end mb-4">
            <button class="btn btn-primary px-4" type="submit">
                <i class="bx bx-save me-1"></i> Enregistrer la fiche d analyse
            </button>
        </div>
    </form>
</div>