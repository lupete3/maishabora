<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-shield-alt"></i> Provisions et Risques de Crédit</h4>
            <div>
                <button wire:click="calculateProvisions" class="btn btn-sm btn-primary">
                    <i class="fas fa-calculator"></i> Recalculer Provisions
                </button>
                <button wire:click="generateJournalEntries" class="btn btn-sm btn-success">
                    <i class="fas fa-file-invoice-dollar"></i> Générer Écritures
                </button>
            </div>
        </div>
        <div class="card-body">
            {{-- Indicateurs PAR --}}
            <div class="row mb-4">
                <div class="col-md-12">
                    <h5 class="mb-3">📊 Indicateurs PAR (Portfolio At Risk)</h5>
                </div>

                <div class="col-md-3">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <div class="text-muted">Encours total</div>
                            <div class="h4 mb-0 text-primary">
                                {{ number_format($parIndicators['total_outstanding'] ?? 0, 2, ',', ' ') }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-warning-light">
                        <div class="card-body text-center">
                            <div class="text-muted">PAR 30</div>
                            <div class="h4 mb-0 text-warning">
                                {{ number_format($parIndicators['par30_rate'] ?? 0, 2) }}%
                            </div>
                            <small class="text-muted">
                                {{ number_format($parIndicators['par30'] ?? 0, 2, ',', ' ') }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-orange-light">
                        <div class="card-body text-center">
                            <div class="text-muted">PAR 60</div>
                            <div class="h4 mb-0 text-orange">
                                {{ number_format($parIndicators['par60_rate'] ?? 0, 2) }}%
                            </div>
                            <small class="text-muted">
                                {{ number_format($parIndicators['par60'] ?? 0, 2, ',', ' ') }}
                            </small>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card bg-danger-light">
                        <div class="card-body text-center">
                            <div class="text-muted">PAR 90</div>
                            <div class="h4 mb-0 text-danger">
                                {{ number_format($parIndicators['par90_rate'] ?? 0, 2) }}%
                            </div>
                            <small class="text-muted">
                                {{ number_format($parIndicators['par90'] ?? 0, 2, ',', ' ') }}
                            </small>
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
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="2" class="text-right">TOTAL PROVISIONS REQUISES</td>
                                    <td colspan="2"></td>
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
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Mode de calcul des provisions</h6>
                        <ul class="mb-0">
                            <li><strong>Créances saines (0j)</strong> : 0% de provision</li>
                            <li><strong>1-30 jours de retard</strong> : 10% du capital restant dû</li>
                            <li><strong>31-60 jours</strong> : 25% du capital restant dû</li>
                            <li><strong>61-90 jours</strong> : 50% du capital restant dû</li>
                            <li><strong>Plus de 90 jours</strong> : 100% du capital restant dû (créance douteuse)</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>