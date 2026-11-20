<div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h4 class="card-title mb-0">Soldes des Membres</h4>
        @can('classer-titulaires-comptes')
            <a href="{{ route('member.accounts.classement') }}" class="btn btn-sm btn-outline-primary">
                <i class="bx bx-purchase-tag-alt me-1"></i> Classer les titulaires (client / interne / test)
            </a>
        @endcan
    </div>
    <div class="mb-3"></div>

    {{-- Ce que nous devons aux clients : toute l'institution, indépendamment des filtres --}}
    <div class="card mb-4 border border-primary">
        <div class="card-header py-2 bg-label-primary">
            <span class="fw-bold"><i class="bx bx-shield-quarter me-1"></i> Ce que nous devons aux clients</span>
            <small class="text-muted ms-2">toute l'institution, comptes des vrais clients (actifs, inactifs et dormants)</small>
        </div>
        <div class="card-body py-3">
            <div class="row g-3">
                @foreach ($liabilities as $currency => $l)
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <div class="text-muted small">Dû aux clients ({{ $currency }})</div>
                                <div class="fs-4 fw-bold text-primary">{{ \App\Support\ClientBalanceRanking::fr($l['du']) }} {{ $currency }}</div>
                                <small class="text-muted">{{ $l['nb_du'] }} compte(s) à solde positif</small>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">Soldes négatifs à régulariser</div>
                                <div class="fw-semibold text-danger">{{ number_format($l['negatifs'], 2, ',', ' ') }} {{ $currency }}</div>
                                <small class="text-muted">{{ $l['nb_negatifs'] }} compte(s)</small>
                            </div>
                        </div>
                        @if ($l['exclus'] > 0)
                            <small class="text-muted d-block mt-1">
                                Non comptés : {{ number_format($l['exclus'], 2, ',', ' ') }} {{ $currency }} sur des comptes internes ou de test.
                            </small>
                        @endif
                    </div>
                @endforeach
            </div>
            <small class="text-muted d-block mt-2">
                Les soldes négatifs ne sont pas une dette envers le client : ils ne sont pas soustraits et doivent être régularisés.
            </small>
        </div>
    </div>

    @php $fr = fn ($n, $dash = false) => \App\Support\ClientBalanceRanking::fr($n, $dash); @endphp

    {{-- Récapitulatif de la sélection : dû = total net − soldes négatifs --}}
    <div class="card mb-4">
        <div class="card-header py-2">
            <span class="fw-bold">Récapitulatif de la sélection</span>
            <small class="text-muted ms-2">selon les filtres ci-dessous</small>
        </div>
        <div class="table-responsive">
            <table class="table table-sm mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Devise</th>
                        <th class="text-end">Comptes courants</th>
                        <th class="text-end">Comptes d'épargne</th>
                        <th class="text-end">Total net</th>
                        <th class="text-end">Soldes négatifs</th>
                        <th class="text-end">Dû aux clients</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recap as $currency => $r)
                        <tr>
                            <td class="fw-bold">{{ $currency }}</td>
                            <td class="text-end">{{ $fr($r['courant']) }}</td>
                            <td class="text-end">{{ $fr($r['epargne']) }}</td>
                            <td class="text-end">{{ $fr($r['net']) }}</td>
                            <td class="text-end text-danger">{{ $fr($r['negatifs']) }} <small class="text-muted">({{ $r['nb_negatifs'] }})</small></td>
                            <td class="text-end fw-bold text-primary">{{ $fr($r['du']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="card-body py-2">
            <small class="text-muted">Dû aux clients = somme des soldes positifs = total net − soldes négatifs.</small>
        </div>
    </div>

    <div class="card">
        <div class="card-header border-bottom">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Catégorie de comptes</label>
                    <select wire:model.live="category" class="form-control">
                        @foreach ($categories as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($category === 'dormants')
                        <small class="text-muted">Solde positif et aucune opération depuis {{ $dormantMonths }} mois</small>
                    @endif
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Nature du titulaire</label>
                    <select wire:model.live="nature" class="form-control">
                        @foreach ($natures as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-5 d-flex align-items-end">
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="masquerSansSolde" wire:model.live="masquerSansSolde">
                        <label class="form-check-label" for="masquerSansSolde">
                            Masquer les membres sans solde (tous leurs comptes à zéro)
                        </label>
                    </div>
                </div>
                <div class="w-100 d-none d-md-block"></div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Recherche</label>
                    <input type="text" wire:model.live.debounce.300ms="search" id="search" class="form-control"
                        placeholder="Nom, code, prénom...">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Classer par</label>
                    <select wire:model.live="sortBy" class="form-control">
                        @foreach ($sorts as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @if ($sortBy === 'total')
                        <small class="text-muted">
                            @if ($rate)
                                CDF converti au taux de {{ number_format($rate, 0, ',', ' ') }} CDF = 1 USD
                            @else
                                Aucun taux de change : classement sur le solde USD
                            @endif
                        </small>
                    @endif
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Type Compte (Vue)</label>
                    <select wire:model.live="accountType" class="form-control">
                        <option value="all">Tous</option>
                        <option value="current">Courant Uniquement</option>
                        <option value="savings">Épargne Uniquement</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Devise</label>
                    <select wire:model.live="currencyFilter" class="form-control">
                        <option value="all">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Tranche Alphabétique</label>
                    <select wire:model.live="alphabetRange" class="form-control">
                        <option value="all">Toute la liste</option>
                        <option value="A-D">De A à D</option>
                        <option value="E-H">De E à H</option>
                        <option value="I-L">De I à L</option>
                        <option value="M-P">De M à P</option>
                        <option value="Q-T">De Q à T</option>
                        <option value="U-X">De U à X</option>
                        <option value="Y-Z">De Y à Z</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Solde Min</label>
                    <input type="number" wire:model.live.debounce.500ms="minBalance" class="form-control"
                        placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Lignes par page</label>
                    <select wire:model.live="perPage" class="form-control">
                        @foreach ($perPageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Taille d'un lot PDF</label>
                    <select wire:model.live="lotSize" class="form-control">
                        @foreach ($lotSizes as $size)
                            <option value="{{ $size }}">{{ $size }} membres</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 d-flex gap-2 align-items-end">
                    {{-- PDF par lots : un PDF de tous les membres dépasse les ressources du serveur --}}
                    <div class="dropdown flex-grow-1">
                        <button class="btn btn-danger w-100 p-2 dropdown-toggle" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false" @disabled(empty($lots))>
                            <i class="bx bx-file"></i> PDF ({{ count($lots) }} lot{{ count($lots) > 1 ? 's' : '' }})
                        </button>
                        <ul class="dropdown-menu" style="max-height: 320px; overflow-y: auto;">
                            @foreach ($lots as $item)
                                <li>
                                    <a class="dropdown-item" target="_blank"
                                        href="{{ route('rapports.compte-clients.pdf', $exportParams + ['lotSize' => $lotSize, 'lot' => $item['number']]) }}">
                                        Lot {{ $item['number'] }} : {{ $isBalanceSort ? 'rangs' : 'membres' }} {{ $item['from'] }} à {{ $item['to'] }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    <a href="{{ route('rapports.compte-clients.excel', $exportParams) }}"
                        target="_blank" class="btn btn-success flex-grow-1 p-2" title="Exporter tous les membres en Excel">
                        <i class="bx bx-spreadsheet"></i> Excel (tout)
                    </a>
                </div>
            </div>
            <div wire:loading.delay class="small text-muted mt-2">
                <span class="spinner-border spinner-border-sm"></span> Mise à jour…
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            @if ($isBalanceSort)
                                <th rowspan="2" class="align-middle text-center">Rang</th>
                            @endif
                            <th rowspan="2" class="align-middle">Code</th>
                            <th rowspan="2" class="align-middle">Membre</th>
                            @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                <th colspan="3" class="text-center">Solde USD</th>
                            @endif
                            @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                <th colspan="3" class="text-center">Solde CDF</th>
                            @endif
                            @if ($sortBy === 'total')
                                <th rowspan="2" class="align-middle text-center">Total équiv. USD</th>
                            @endif
                            @if ($isBalanceSort)
                                <th rowspan="2" class="align-middle text-center">Part de l'encours</th>
                            @endif
                        </tr>
                        <tr>
                            @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                <th class="text-center small">Courant</th>
                                <th class="text-center small">Épargne</th>
                                <th class="text-center small">Total</th>
                            @endif
                            @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                <th class="text-center small">Courant</th>
                                <th class="text-center small">Épargne</th>
                                <th class="text-center small">Total</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($balances as $balance)
                            <tr>
                                @if ($isBalanceSort)
                                    <td class="text-center fw-bold">
                                        @if ($balance['rank'] <= 3)
                                            <span class="badge bg-label-warning">{{ $balance['rank'] }}</span>
                                        @else
                                            {{ $balance['rank'] }}
                                        @endif
                                    </td>
                                @endif
                                <td><small class="fw-bold">{{ $balance['member']->code }}</small></td>
                                <td>{{ $balance['member']->name . ' ' . $balance['member']->postnom . ' ' . $balance['member']->prenom }}
                                </td>

                                @if ($currencyFilter === 'all' || $currencyFilter === 'USD')
                                    <td class="text-end">{{ $fr($balance['current_usd'], true) }}</td>
                                    <td class="text-end">{{ $fr($balance['savings_usd'], true) }}</td>
                                    <td class="text-end fw-semibold">{{ $fr($balance['current_usd'] + $balance['savings_usd'], true) }}</td>
                                @endif

                                @if ($currencyFilter === 'all' || $currencyFilter === 'CDF')
                                    <td class="text-end">{{ $fr($balance['current_cdf'], true) }}</td>
                                    <td class="text-end">{{ $fr($balance['savings_cdf'], true) }}</td>
                                    <td class="text-end fw-semibold">{{ $fr($balance['current_cdf'] + $balance['savings_cdf'], true) }}</td>
                                @endif

                                @if ($sortBy === 'total')
                                    <td class="text-end fw-semibold">{{ $fr($balance['total_usd_equiv'], true) }}</td>
                                @endif
                                @if ($isBalanceSort)
                                    <td class="text-end">{{ $balance['share'] !== null ? number_format($balance['share'], 2, ',', ' ') . ' %' : '—' }}</td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="mt-3">
                    {{ $members->links() }}
                </div>

            </div>
        </div>

    </div>
