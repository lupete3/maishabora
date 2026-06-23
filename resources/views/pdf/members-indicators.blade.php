<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Indicateurs</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 5px;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 50px
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .text-start {
            text-align: left;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 2px;
            font-size: 8px;
        }

        .signature {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }

        .signature-block {
            width: 45%;
            text-align: center;
        }

        th {
            background-color: #f1c206;
        }

        .section-title {
            margin-top: 10px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        .totals p {
            margin: 2px 0;
        }

        .logo {
            width: 80px;
        }

        .balances {
            width: 49%;
            display: inline-block;
            vertical-align: top;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES INDICATEURS DES COLLECTEURS'])

    <div class="filters">

        <strong>Filtres appliqués :</strong>

        <br><br>

        <strong>Collecteur :</strong>

        {{ $agentName ?? 'Tous les collecteurs' }}

        <br>

        <strong>Période :</strong>

        {{ $periodLabel }}

        <br>

        <strong>Statut :</strong>

        @switch($status)
            @case('active')
                Membres actifs
            @break

            @case('follow')
                Membres à relancer
            @break

            @case('inactive')
                Membres inactifs
            @break

            @default
                Tous les membres
        @endswitch

    </div>


    {{-- RESUME --}}

    <div class="summary">

        <strong>
            Nombre total de membres :
        </strong>

        {{ number_format($members->count()) }}

    </div>

    <table class="table" border="1" cellspacing="0" cellpadding="4">
        <thead>

            <tr>
                <th style="width:5%">#</th>
                <th style="width:10%">Code</th>

                <th style="width:28%">Noms complets</th>

                <th style="width:15%">Téléphone</th>
                <th style="width:18%">Collecteur</th>

                <th style="width:14%">Dernier mouvement</th>

                <th style="width:10%">Etat</th>

            </tr>

        </thead>

        <tbody>

            @forelse($members as $member)
                @php
                    if (!$member->last_transaction_at) {
                        $etat = 'Inactif';
                    } elseif ($member->last_transaction_at->lt(now()->subDays(90))) {
                        $etat = 'Inactif';
                    } elseif ($member->last_transaction_at->lt(now()->subDays(30))) {
                        $etat = 'A relancer';
                    } else {
                        $etat = 'Actif';
                    }
                @endphp

                <tr>

                    <td>
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $member->code }}
                    </td>

                    <td>
                        {{ $member->name }}
                        {{ $member->postnom }}
                        {{ $member->prenom }}
                    </td>

                    <td>
                        {{ $member->telephone }}
                    </td>

                    <td>

                        @if ($member->agent)
                            {{ $member->agent->name }}
                            {{ $member->agent->postnom }}
                        @else
                            Non affecté
                        @endif

                    </td>

                    <td>

                        @if ($member->last_transaction_at)
                            {{ $member->last_transaction_at->format('d/m/Y') }}
                        @else
                            Jamais
                        @endif

                    </td>

                    <td>
                        {{ $etat }}
                    </td>

                </tr>
            @empty
                <tr>
                    <td colspan="7" align="center">
                        Aucun membre trouvé.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Rapport généré le {{ now()->format('d/m/Y H:i') }} par {{ Auth::user()->name }} {{ Auth::user()->postnom }} -
        {{ $company->name ?? config('app.name') }}
    </div>

</body>

</html>
