<div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="fas fa-balance-scale"></i> Bilan</h4>
            <button wire:click="exportPDF" class="btn btn-sm btn-danger">
                <i class="fas fa-file-pdf"></i> Exporter PDF
            </button>
        </div>
        <div class="card-body">
            {{-- Filtres --}}
            <div class="row mb-4">
                <div class="col-md-4">
                    <label>Devise</label>
                    <select wire:model.live="devise" class="form-control">
                        @foreach($currencies as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label>Date de référence</label>
                    <input type="date" wire:model.live="date_reference" class="form-control" />
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    @if($isBalanced)
                        <div class="alert alert-success mb-0 w-100">
                            <i class="fas fa-check-circle"></i> <strong>Bilan équilibré</strong>
                        </div>
                    @else
                        <div class="alert alert-warning mb-0 w-100">
                            <i class="fas fa-exclamation-triangle"></i> <strong>Bilan déséquilibré</strong>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Date affichée --}}
            <div class="alert alert-info">
                <strong>Situation au :</strong> {{ \Carbon\Carbon::parse($date_reference)->format('d/m/Y') }}
                ({{ $devise }})
            </div>

            <div class="row">
                {{-- ACTIF --}}
                <div class="col-md-6">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">ACTIF</h5>
                        </div>
                        <div class="card-body p-0">
                            @foreach($actifs as $classe => $comptes)
                                <div class="mb-3">
                                    <div class="bg-light p-2 font-weight-bold">
                                        Classe {{ $classe }}
                                    </div>
                                    <table class="table table-sm table-hover mb-0">
                                        <tbody>
                                            @foreach($comptes as $compte)
                                                <tr>
                                                    <td style="width: 20%;">{{ $compte['code'] }}</td>
                                                    <td style="width: 55%;">
                                                        @if($compte['level'] == 2)
                                                            <span class="ml-2">{{ $compte['intitule'] }}</span>
                                                        @elseif($compte['level'] == 3)
                                                            <span class="ml-4">{{ $compte['intitule'] }}</span>
                                                        @else
                                                            {{ $compte['intitule'] }}
                                                        @endif
                                                    </td>
                                                    <td style="width: 25%;" class="text-right">
                                                        {{ number_format($compte['montant'], 2, ',', ' ') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach

                            @if(empty($actifs))
                                <div class="p-3 text-center text-muted">
                                    Aucun actif à afficher
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-info text-white font-weight-bold">
                            <div class="d-flex justify-content-between">
                                <span>TOTAL ACTIF</span>
                                <span>{{ number_format($totalActifs, 2, ',', ' ') }} {{ $devise }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- PASSIF --}}
                <div class="col-md-6">
                    <div class="card border-warning">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">PASSIF</h5>
                        </div>
                        <div class="card-body p-0">
                            @foreach($passifs as $classe => $comptes)
                                <div class="mb-3">
                                    <div class="bg-light p-2 font-weight-bold">
                                        Classe {{ $classe }}
                                    </div>
                                    <table class="table table-sm table-hover mb-0">
                                        <tbody>
                                            @foreach($comptes as $compte)
                                                <tr>
                                                    <td style="width: 20%;">{{ $compte['code'] }}</td>
                                                    <td style="width: 55%;">
                                                        @if($compte['level'] == 2)
                                                            <span class="ml-2">{{ $compte['intitule'] }}</span>
                                                        @elseif($compte['level'] == 3)
                                                            <span class="ml-4">{{ $compte['intitule'] }}</span>
                                                        @else
                                                            {{ $compte['intitule'] }}
                                                        @endif
                                                    </td>
                                                    <td style="width: 25%;" class="text-right">
                                                        {{ number_format($compte['montant'], 2, ',', ' ') }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endforeach

                            @if(empty($passifs))
                                <div class="p-3 text-center text-muted">
                                    Aucun passif à afficher
                                </div>
                            @endif
                        </div>
                        <div class="card-footer bg-warning text-white font-weight-bold">
                            <div class="d-flex justify-content-between">
                                <span>TOTAL PASSIF</span>
                                <span>{{ number_format($totalPassifs, 2, ',', ' ') }} {{ $devise }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Équilibre --}}
            <div class="row mt-4">
                <div class="col-md-12">
                    <div
                        class="card {{ $isBalanced ? 'border-success bg-light-success' : 'border-danger bg-light-danger' }}">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h5 class="mb-0">
                                        @if($isBalanced)
                                            <i class="fas fa-check-circle text-success"></i> BILAN ÉQUILIBRÉ
                                        @else
                                            <i class="fas fa-times-circle text-danger"></i> ÉCART DÉTECTÉ
                                        @endif
                                    </h5>
                                    <small class="text-muted">Actif - Passif =
                                        {{ number_format($totalActifs - $totalPassifs, 2, ',', ' ') }}
                                        {{ $devise }}</small>
                                </div>
                                <div>
                                    <div class="text-muted">Total Actif</div>
                                    <div class="h5 mb-0">{{ number_format($totalActifs, 2, ',', ' ') }} {{ $devise }}
                                    </div>
                                </div>
                                <div class="text-center">
                                    <i class="fas fa-equals fa-2x"></i>
                                </div>
                                <div>
                                    <div class="text-muted">Total Passif</div>
                                    <div class="h5 mb-0">{{ number_format($totalPassifs, 2, ',', ' ') }} {{ $devise }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>