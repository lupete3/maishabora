<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Rapport Annuel Global</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #2c3e50;
            margin: 40px;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
        }

        .header h2 {
            color: #1a5276;
            font-size: 22px;
            margin-bottom: 5px;
        }

        .header p {
            font-size: 13px;
            color: #555;
        }

        h4 {
            margin-top: 30px;
            color: #154360;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        thead {
            background-color: #f2f4f4;
        }

        th {
            padding: 10px;
            border: 1px solid #ccc;
            background-color: #eaf2f8;
            color: #154360;
            font-weight: bold;
        }

        td {
            padding: 8px;
            border: 1px solid #ccc;
            text-align: center;
        }

        .highlight {
            background-color: #d4efdf;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: 30px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #888;
            font-style: italic;
        }
    </style>
</head>
<body>

<div class="header">
    <h2>Rapport Annuel - {{ now()->format('Y') }}</h2>
    <p>Ce document résume les opérations financières de l’année complète.</p>
</div>

<h4>Statistiques Générales</h4>
<table>
    <tr class="highlight">
        <td>Total Adhésions<br><strong>{{ number_format($totalAdhesion, 0, ',', '.') }} FC</strong></td>
        <td>Total Contributions<br><strong>{{ number_format($totalContributions, 0, ',', '.') }} FC</strong></td>
        <td>Total Retraits<br><strong>{{ number_format($totalWithdrawals, 0, ',', '.') }} FC</strong></td>
        <td>Solde Net<br><strong>{{ number_format($totalBalance, 0, ',', '.') }} FC</strong></td>
    </tr>
</table>

<h4>Détails Mensuels</h4>
<table>
    <thead>
        <tr>
            <th>Mois</th>
            <th>Adhésions (FC)</th>
            <th>Contributions (FC)</th>
            <th>Retraits (FC)</th>
            <th>Solde (FC)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($data as $row)
            <tr>
                <td>{{ $row['mois'] }}</td>
                <td>{{ number_format($row['adhesions'], 0, ',', '.') }}</td>
                <td>{{ number_format($row['contributions'], 0, ',', '.') }}</td>
                <td>{{ number_format($row['retraits'], 0, ',', '.') }}</td>
                <td>{{ number_format($row['solde'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<p class="footer">
    Document généré automatiquement le {{ now()->format('d/m/Y à H:i') }}.
</p>

</body>
</html>
