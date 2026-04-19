<div>
    <div class="card">
        <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            <h4 class="mb-0"><i class="fas fa-shield-alt"></i> Provisions et Risques de Crédit</h4>
            <div class="d-flex flex-wrap align-items-center gap-2">
                {{-- Filtre de devise --}}
                <div class="d-flex align-items-center me-md-3">
                    <label class="me-2 mb-0" style="white-space: nowrap;">
                        <i class="fas fa-dollar-sign"></i> Devise :
                    </label>
                    <select wire:model.live="currency" class="form-select form-select-sm" style="width: auto;">
                        <option value="all">Toutes</option>
                        <option value="USD">USD</option>
                        <option value="CDF">CDF</option>
                    </select>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <button wire:click="calculateProvisions" class="btn btn-sm btn-primary">
                        <i class="bx bx-calculator"></i> Recalculer
                    </button>
                    <a href="{{ route('provisions.export.pdf', ['currency' => $currency]) }}"
                        class="btn btn-sm btn-danger">
                        <i class="bx bx-file"></i> Export PDF
                    </a>
                    <button wire:click="generateJournalEntries" class="btn btn-sm btn-success">
                        <i class="bx bx-file-invoice"></i> Écritures
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            {{-- Indicateurs PAR --}}
            <div class="row mb-4 g-3">
                <div class="col-12">
                    <h5 class="mb-3">📊 Indicateurs PAR (Portfolio At Risk)</h5>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="card bg-light h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="text-muted small">
                                Encours total
                                @if($currency !== 'all')
                                    <span class="badge bg-info ms-1">{{ $currency }}</span>
                                @endif
                            </div>
                            <div class="h5 mb-0 text-primary">
                                {{ number_format($parIndicators['total_outstanding'] ?? 0, 2, ',', ' ') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="card bg-warning-light h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="text-muted small">PAR 30</div>
                            <div class="h5 mb-0 text-warning">
                                {{ number_format($parIndicators['par30_rate'] ?? 0, 2) }}%
                            </div>
                            <div class="small text-muted">
                                {{ number_format($parIndicators['par30'] ?? 0, 2, ',', ' ') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="card bg-orange-light h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="text-muted small">PAR 60</div>
                            <div class="h5 mb-0 text-orange">
                                {{ number_format($parIndicators['par60_rate'] ?? 0, 2) }}%
                            </div>
                            <div class="small text-muted">
                                {{ number_format($parIndicators['par60'] ?? 0, 2, ',', ' ') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-sm-6 col-md-3">
                    <div class="card bg-danger-light h-100">
                        <div class="card-body text-center d-flex flex-column justify-content-center">
                            <div class="text-muted small">PAR 90</div>
                            <div class="h5 mb-0 text-danger">
                                {{ number_format($parIndicators['par90_rate'] ?? 0, 2) }}%
                            </div>
                            <div class="small text-muted">
                                {{ number_format($parIndicators['par90'] ?? 0, 2, ',', ' ') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Statistiques par classification --}}
            <div class="row">
                <div class="col-md-12">
                    <h5 class="mb-3">📋 Détail par Classification</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="bg-primary text-white">
                                <tr>
                                    <th>Classification</th>
                                    <th class="text-center">Nombre de crédits</th>
                                    <th class="text-right">Capital restant dû</th>
                                    <th class="text-center">Taux provision</th>
                                    <th class="text-right">Montant provisionné</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($statsByClassification as $classification => $stats)
                                    <tr>
                                        <td>
                                            @if($classification == 'saine')
                                                <span class="badge bg-success">Saine (0j)</span>
                                            @elseif($classification == '1-30')
                                                <span class="badge bg-warning">1-30 jours</span>
                                            @elseif($classification == '31-60')
                                                <span class="badge bg-primary">31-60 jours</span>
                                            @elseif($classification == '61-90')
                                                <span class="badge bg-danger">61-90 jours</span>
                                            @else
                                                <span class="badge bg-dark">+90 jours (Douteuse)</span>
                                            @endif
                                        </td>
                                        <td class="text-center">{{ $stats['count'] }}</td>
                                        <td class="text-right">
                                            {{ number_format($stats['outstanding'], 2, ',', ' ') }}
                                        </td>
                                        <td class="text-center">
                                            @php
                                                $rate = match ($classification) {
                                                    'saine' => 0,
                                                    '1-30' => 10,
                                                    '31-60' => 25,
                                                    '61-90' => 50,
                                                    '>90' => 100,
                                                };
                                            @endphp
                                            {{ $rate }}%
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            {{ number_format($stats['provision'], 2, ',', ' ') }}
                                        </td>
                                        <td class="text-center">
                                            <button wire:click="showCredits('{{ $classification }}')"
                                                class="btn btn-sm btn-icon btn-outline-primary" title="Voir les détails">
                                                <i class="bx bx-show"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="2" class="text-right">TOTAL PROVISIONS REQUISES</td>
                                    <td colspan="3"></td>
                                    <td class="text-right text-danger">
                                        {{ number_format($totalProvisions, 2, ',', ' ') }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Informations --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="alert alert-info border-0 shadow-sm">
                        <h6 class="font-weight-bold"><i class="fas fa-info-circle"></i> Comprendre ce rapport</h6>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <p class="mb-2"><strong>1. Logique de Ventilation (Nouveau)</strong></p>
                                <p class="small text-muted">
                                    Contrairement à un rapport classique qui classerait tout le crédit selon son retard
                                    le plus ancien, ce système
                                    <strong>ventile</strong> le capital. Une partie d'un même crédit peut être en
                                    "Saine" (échéances futures)
                                    tandis qu'une autre partie est classée en "1-30 jours" (échéances en retard).
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p class="mb-2"><strong>2. Base de Calcul : Capital Uniquement</strong></p>
                                <p class="small text-muted">
                                    Les provisions sont calculées uniquement sur le <strong>principal (fonds
                                        déboursés)</strong>.
                                    Les intérêts attendus et les pénalités sont exclus de l'assiette pour respecter les
                                    standards prudentiels
                                    de gestion du risque de perte sur capital.
                                </p>
                            </div>
                        </div>
                        <div class="mt-2 border-top pt-2">
                            <p class="mb-1"><strong>Barème des provisions :</strong></p>
                            <div class="d-flex flex-wrap gap-3">
                                <span class="badge bg-success">Saine (0j) : 0%</span>
                                <span class="badge bg-warning text-dark">1-30 jours : 10%</span>
                                <span class="badge bg-primary">31-60 jours : 25%</span>
                                <span class="badge bg-danger">61-90 jours : 50%</span>
                                <span class="badge bg-dark">+90 jours : 100%</span>
                            </div>
                        </div>
                        <p class="mt-2 small italic text-muted">
                            <i class="fas fa-sync"></i> Les données sont extraites en temps réel du calendrier de
                            remboursement (échéancier) de chaque membre.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal des Détails --}}
    <div wire:ignore.self class="modal fade" id="provisionDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="bx bx-list-ul"></i> Détails des Crédits :
                        @if($selectedClassification == 'saine') Saine (0j)
                        @elseif($selectedClassification == '1-30') 1-30 jours
                        @elseif($selectedClassification == '31-60') 31-60 jours
                        @elseif($selectedClassification == '61-90') 61-90 jours
                        @elseif($selectedClassification == '>90') +90 jours (Douteuse)
                        @endif
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                        wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Membre</th>
                                    <th>Référence</th>
                                    <th class="text-right">Encours</th>
                                    <th class="text-center">Retard exact</th>
                                    <th class="text-right">Provision</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($selectedCredits as $credit)
                                    <tr>
                                        <td><strong>#{{ $credit->user->code ?? '' }} {{ $credit->user->name ?? 'Inconnu' }}
                                                {{ $credit->user->postnom ?? 'Inconnu' }}
                                                {{ $credit->user->prenom ?? '' }}</strong></td>
                                        <td><small class="text-muted">#{{ $credit->id }}</small></td>
                                        <td class="text-right">{{ number_format($credit->outstanding_amount, 2, ',', ' ') }}
                                            {{ $credit->currency }}
                                        </td>
                                        <td class="text-center">
                                            @if($credit->days_overdue > 0)
                                                <span class="text-danger font-weight-bold">{{ $credit->days_overdue }}
                                                    jours</span>
                                            @else
                                                <span class="text-success">À jour</span>
                                            @endif
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            {{ number_format($credit->provision_amount, 2, ',', ' ') }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4 text-muted italic">Aucun crédit trouvé dans
                                            cette catégorie.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"
                        wire:click="closeModal">Fermer</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('livewire:init', () => {
                Livewire.on('show-provision-modal', () => {
                    var modal = new bootstrap.Modal(document.getElementById('provisionDetailModal'));
                    modal.show();
                });
            });
        </script>
    @endpush
</div>
