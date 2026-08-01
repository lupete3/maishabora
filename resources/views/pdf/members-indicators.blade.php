<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Rapport des Indicateurs et Pointage Hebdomadaire</title>
    <style>
        @page {
            size: A4 landscape; /* Optionnel : recommandé pour faire tenir les 6 jours de la semaine */
            margin: 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 5px;
            color: #000;
        }

        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 8px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table td,
        .table th {
            border: 1px solid #000;
            padding: 3px 2px;
            font-size: 8px;
            vertical-align: middle;
        }

        th {
            background-color: #f1c206;
            text-align: center;
        }

        .checkbox-cell {
            text-align: center;
            font-size: 11px; /* Taille légèrement plus grande pour la case à cocher */
            width: 5%;
        }

        .filters {
            margin-bottom: 10px;
            line-height: 1.4;
        }

        .summary {
            margin-top: 10px;
            margin-bottom: 10px;
        }
    </style>
</head>

<body>

    @include('partials.pdf-header', ['reportTitle' => 'RAPPORT DES INDICATEURS ET FICHE DE POINTAGE HEBDOMADAIRE'])

    <div class="filters">
        <strong>Filtres appliqués :</strong><br>
        <strong>Collecteur :</strong> {{ $agentName ?? 'Tous les collecteurs' }} {{ $agentLastName ?? 'Maisha Bora' }} <br>
        <strong>Période :</strong> {{ $periodLabel }} <br>
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
        <strong>Nombre total de membres :</strong> {{ number_format($members->count()) }}
    </div>

    @php
        // Calcul automatique des dates de la semaine en cours
        $startOfWeek = now()->startOfWeek(); // Lundi
    @endphp

    <table class="table" border="1" cellspacing="0" cellpadding="2">
        <thead>
            <tr>
                <th rowspan="2" style="width:3%">#</th>
                <th rowspan="2" style="width:7%">Code</th>
                <th rowspan="2" style="width:18%">Noms complets</th>
                <th rowspan="2" style="width:10%">Téléphone</th>
                <th rowspan="2" style="width:12%">Collecteur</th>
                <th rowspan="2" style="width:10%">Dernier mvt</th>
                <th colspan="6" style="width:40%">Pointage Hebdomadaire (Semaine du {{ $startOfWeek->format('d/m/Y') }})</th>
            </tr>
            <tr>
                <!-- Colonnes jours avec leurs dates respectives -->
                <th style="width:6.6%">Lun<br>{{ $startOfWeek->copy()->addDays(0)->format('d/m') }}</th>
                <th style="width:6.6%">Mar<br>{{ $startOfWeek->copy()->addDays(1)->format('d/m') }}</th>
                <th style="width:6.6%">Mer<br>{{ $startOfWeek->copy()->addDays(2)->format('d/m') }}</th>
                <th style="width:6.6%">Jeu<br>{{ $startOfWeek->copy()->addDays(3)->format('d/m') }}</th>
                <th style="width:6.6%">Ven<br>{{ $startOfWeek->copy()->addDays(4)->format('d/m') }}</th>
                <th style="width:6.6%">Sam<br>{{ $startOfWeek->copy()->addDays(5)->format('d/m') }}</th>
            </tr>
        </thead>

        <tbody>
            @forelse($members as $member)
                <tr>
                    <td style="text-align: center;">{{ $loop->iteration }}</td>
                    <td>{{ $member->code }}</td>
                    <td>
                        {{ $member->name }}
                        {{ $member->postnom }}
                        {{ $member->prenom }}
                    </td>
                    <td>{{ $member->telephone }}</td>
                    <td>
                        @if ($member->agent)
                            {{ $member->agent->name }} {{ $member->agent->postnom }}
                        @else
                            Non affecté
                        @endif
                    </td>
                    <td style="text-align: center;">
                        @if ($member->last_transaction_at)
                            {{ $member->last_transaction_at->format('d/m/Y') }}
                        @else
                            Jamais
                        @endif
                    </td>

                    <!-- Cases à cocher pour du Lundi au Samedi -->
                    <td class="checkbox-cell"></td>
                    <td class="checkbox-cell"></td>
                    <td class="checkbox-cell"></td>
                    <td class="checkbox-cell"></td>
                    <td class="checkbox-cell"></td>
                    <td class="checkbox-cell"></td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" style="text-align: center;">
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