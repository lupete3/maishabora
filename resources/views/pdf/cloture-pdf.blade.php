@extends('layouts.pdf')

@section('title', 'Historique Clôtures - PDF')
@section('font-size', '7px')

@section('header')
    {{-- On vide l'en-tête automatique car on va le répéter manuellement dans la boucle --}}
@endsection

@section('content')
    @foreach ($cloture as $cl)
        @include('partials.pdf-header', [
            'reportTitle' => 'FICHE DE CLÔTURE DE CAISSE',
            'date' => \Carbon\Carbon::parse($cl->closing_date)->format('d/m/Y'),
            'heure' => \Carbon\Carbon::parse($cl->created_at)->format('H:i'),
            'agent_name' => $cl->user->name . ' ' . $cl->user->postnom
        ])

        <p class="text-center">
            Statut :
            @if($cl->status == 'validated')
                <span class="badge badge-success">VALIDÉE</span>
            @elseif($cl->status == 'rejected')
                <span class="badge badge-danger">REJETÉE</span>
            @else
                EN ATTENTE
            @endif
            @if($cl->validated_at)
                | du {{ $cl->validated_at->format('d/m/Y H:i') }}
                par {{ $cl->validatedBy?->name }} {{ $cl->validatedBy?->postnom }}
            @endif
        </p>

        <div style="font-weight: bold; text-align: center; font-size: 9px; background-color: #f8f9fa; border: 1px solid #ddd; padding: 2px;">SOLDES</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Devise</th>
                    <th>Logique</th>
                    <th>Physique</th>
                    <th>Écart</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>USD</td>
                    <td>{{ number_format($cl->logical_usd, 2) }}</td>
                    <td>{{ number_format($cl->physical_usd, 2) }}</td>
                    <td>{{ number_format($cl->gap_usd, 2) }}</td>
                </tr>
                <tr>
                    <td>CDF</td>
                    <td>{{ number_format($cl->logical_cdf, 2) }}</td>
                    <td>{{ number_format($cl->physical_cdf, 2) }}</td>
                    <td>{{ number_format($cl->gap_cdf, 2) }}</td>
                </tr>
            </tbody>
        </table>

        <table style="width: 100%; border: none; border-collapse: collapse;">
            <tr>
                <td style="width: 49%; vertical-align: top; padding-right: 1%;">
                    <h4 style="text-align:center;">USD</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Valeur</th>
                                <th>Quantité</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cl->billetages->where('currency', 'USD') as $billet)
                                <tr>
                                    <td>${{ number_format($billet->denomination, 0) }}</td>
                                    <td>{{ $billet->quantity }}</td>
                                    <td>${{ number_format($billet->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
                <td style="width: 49%; vertical-align: top; padding-left: 1%;">
                    <h4 style="text-align:center;">CDF</h4>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Valeur</th>
                                <th>Quantité</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($cl->billetages->where('currency', 'CDF') as $billet)
                                <tr>
                                    <td>{{ number_format($billet->denomination, 0) }} CDF</td>
                                    <td>{{ $billet->quantity }}</td>
                                    <td>{{ number_format($billet->total, 2) }} CDF</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>

        <table style="width:100%; margin-top:40px">
            <tr>
                <td style="width: 49%; text-align:left; font-size: 12px;">
                    <strong>Agent :</strong><br><br><br>
                    {{ $cl->user->name }} {{ $cl->user->postnom }}
                </td>
                <td style="width: 49%; text-align:right; font-size: 12px;">
                    <strong>Visa Responsable</strong><br><br><br>
                    @if($cl->validatedBy)
                        {{ $cl->validatedBy->name }} {{ $cl->validatedBy->postnom }}
                    @else
                        (Cette clôture a été rejetée)
                    @endif
                </td>
            </tr>
        </table>

        <div class="page-break"></div>

        @include('partials.pdf-header', [
            'reportTitle' => 'FEUILLE DE CAISSE N° ' . $cl->id,
            'date' => \Carbon\Carbon::parse($cl->closing_date)->format('d/m/Y'),
            'heure' => \Carbon\Carbon::parse($cl->created_at)->format('H:i'),
            'agent_name' => $cl->user->name . ' ' . $cl->user->postnom
        ])

        <div style="font-weight: bold; text-align: center; font-size: 9px; background-color: #f8f9fa; border: 1px solid #ddd; padding: 2px;">TABLEAU DES DÉPÔTS</div>
        <table class="table">
            <thead>
                <tr>
                    <th>N° Transaction</th>
                    <th>Date & Heure</th>
                    <th>Libellé</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cl->deposits as $dep)
                    <tr>
                        <td>{{ $dep->id }}</td>
                        <td>{{ $dep->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $dep->description ?? $dep->type }}</td>
                        <td class="text-end">{{ number_format($dep->amount, 2) }} {{ $dep->currency }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Aucun dépôt enregistré</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">TOTAL DÉPÔTS USD</th>
                    <th class="text-end">{{ number_format($cl->total_deposits_usd, 2) }} USD</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">TOTAL DÉPÔTS CDF</th>
                    <th class="text-end">{{ number_format($cl->total_deposits_cdf, 2) }} CDF</th>
                </tr>
            </tfoot>
        </table>

        <div style="font-weight: bold; text-align: center; font-size: 9px; background-color: #f8f9fa; border: 1px solid #ddd; padding: 2px;">TABLEAU DES RETRAITS</div>
        <table class="table">
            <thead>
                <tr>
                    <th>N° Transaction</th>
                    <th>Date & Heure</th>
                    <th>Libellé</th>
                    <th>Montant</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cl->withdrawals as $wit)
                    <tr>
                        <td>{{ $wit->id }}</td>
                        <td>{{ $wit->created_at->format('d/m/Y H:i') }}</td>
                        <td>{{ $wit->description ?? $wit->type }}</td>
                        <td class="text-end">{{ number_format($wit->amount, 2) }} {{ $wit->currency }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Aucun retrait enregistré</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-end">TOTAL RETRAITS USD</th>
                    <th class="text-end">{{ number_format($cl->total_withdrawals_usd, 2) }} USD</th>
                </tr>
                <tr>
                    <th colspan="3" class="text-end">TOTAL RETRAITS CDF</th>
                    <th class="text-end">{{ number_format($cl->total_withdrawals_cdf, 2) }} CDF</th>
                </tr>
            </tfoot>
        </table>

        <div style="font-weight: bold; text-align: center; font-size: 9px; background-color: #f8f9fa; border: 1px solid #ddd; padding: 2px;">PREUVE COMPTABLE</div>
        <table class="table">
            <thead>
                <tr>
                    <th>Détails</th>
                    <th class="text-end">USD</th>
                    <th class="text-end">CDF</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Solde Logique Précédent</td>
                    <td class="text-end">{{ number_format($cl->previous_logical_usd, 2) }}</td>
                    <td class="text-end">{{ number_format($cl->previous_logical_cdf, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Dépôts du Jour (+)</td>
                    <td class="text-end">{{ number_format($cl->total_deposits_usd, 2) }}</td>
                    <td class="text-end">{{ number_format($cl->total_deposits_cdf, 2) }}</td>
                </tr>
                <tr>
                    <td>Total Retraits du Jour (-)</td>
                    <td class="text-end">{{ number_format($cl->total_withdrawals_usd, 2) }}</td>
                    <td class="text-end">{{ number_format($cl->total_withdrawals_cdf, 2) }}</td>
                </tr>
                <tr style="background-color: #f8f9fa; font-weight: bold;">
                    <td>SOLDE EN CAISSE ACTUALISÉ</td>
                    <td class="text-end">{{ number_format($cl->logical_usd, 2) }} USD</td>
                    <td class="text-end">{{ number_format($cl->logical_cdf, 2) }} CDF</td>
                </tr>
            </tbody>
        </table>

        @if($cl->note || $cl->rejection_reason)
            <div style="font-weight: bold; text-align: center; font-size: 9px; background-color: #f8f9fa; border: 1px solid #ddd; padding: 2px;">DESCRIPTION DÉTAILLÉE / MOTIF</div>
            <table class="table">
                @if($cl->note)
                    <tr>
                        <th style="width: 30%;">Justification de l'agent</th>
                        <td>{{ $cl->note }}</td>
                    </tr>
                @endif
                @if($cl->status === 'rejected')
                    <tr>
                        <th style="width: 30%;">Motif du rejet</th>
                        <td>{{ $cl->rejection_reason ?? '-' }}</td>
                    </tr>
                @endif
            </table>
        @endif

        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
@endsection
