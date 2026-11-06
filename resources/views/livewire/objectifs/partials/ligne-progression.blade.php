{{-- Une ligne de progression : $ligne (résultat de ObjectifProgressService::progression) --}}
@php
    $o = $ligne['objectif'];
    $def = $ligne['def'];
    $fmt = fn ($v) => \App\Services\ObjectifProgressService::formater((float) $v, $def, $o->currency);
    $barre = min(100, $ligne['taux']);
    $estPlafond = $def['direction'] === 'down';
@endphp
<div class="mb-3">
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-1">
        <div>
            <span class="fw-semibold">{{ $def['label'] }}</span>
            @if ($o->currency)
                <span class="badge bg-label-dark ms-1">{{ $o->currency }}</span>
            @endif
            <i class="bx bx-info-circle text-muted small" title="{{ $def['description'] }}"></i>
        </div>
        <span class="badge bg-label-{{ $ligne['statut'] }}">{{ $ligne['statut_label'] }}</span>
    </div>

    <div class="position-relative my-1">
        <div class="progress" style="height: 10px;">
            <div class="progress-bar bg-{{ $ligne['statut'] === 'secondary' ? 'secondary' : $ligne['statut'] }}"
                 role="progressbar" style="width: {{ $barre }}%"></div>
        </div>
        @if ($def['nature'] === 'flux' && $ligne['taux_attendu'] > 0 && $ligne['taux_attendu'] < 100)
            {{-- Repère : où l'on devrait être à date --}}
            <div class="position-absolute top-0 bg-dark" title="Attendu à date : {{ $ligne['taux_attendu'] }} %"
                 style="left: {{ $ligne['taux_attendu'] }}%; width: 2px; height: 10px;"></div>
        @endif
    </div>

    <div class="d-flex justify-content-between small text-muted flex-wrap gap-1">
        <span>
            Réalisé <strong class="text-body">{{ $fmt($ligne['realise']) }}</strong>
            / {{ $estPlafond ? 'plafond' : 'objectif' }} {{ $fmt($ligne['cible']) }}
            ({{ number_format($ligne['taux'], 1, ',', ' ') }} %)
        </span>
        @if ($def['nature'] === 'flux')
            <span>
                Attendu à date : {{ $fmt($ligne['attendu']) }}
                @if (!$estPlafond && $ligne['reste'] > 0)
                    · Reste : {{ $fmt($ligne['reste']) }}
                @endif
            </span>
        @else
            <span>Situation à date</span>
        @endif
    </div>
</div>
