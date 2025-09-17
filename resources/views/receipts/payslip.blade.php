
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin de Paie RDC</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 9px;
            margin: 5px;
            color: #000;
        }
        .footer { text-align: center; margin-top: 30px; font-size: 9px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .text-start { text-align: left; }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        .table td, .table th {
            border: 1px solid;
            padding: 4px;
        }
        .signature {
            margin-top: 5px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        .signature-block {
            width: 40%;
            text-align: center;
        }
        .logo { width: 40px; }
        th { background-color: #f1c206; }
    </style>
</head>
<body>
    <div class="header" style="padding-bottom: 5px;">
        <table style="width:100%;">
            <tr>
                <td style="width: 10%;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('logo.jpg'))) }}" class="logo" alt="Logo">
                </td>
                <td style="width: 60%; text-align:center;">
                    <h4 style="margin: 0; font-size: 10px;">{{ strtoupper(config('app.name')) }}</h4>
                    <p style="margin: 0; font-size: 9px;">Adresse : {{ env('APP_ADRESS') }}</p>
                    <p style="margin: 0; font-size: 9px;">Tel : {{ env('APP_PHONE') }} – Email : {{ env('APP_EMAIL') }}</p>
                </td>
                <td style="width: 30%; text-align:right; font-size: 9px;">
                    <strong>Date :</strong> {{ now()->format('d/m/Y') }}<br>
                    <strong>Agent :</strong><br>
                    {{ $payroll->user->name }} {{ $payroll->user->postnom }}
                </td>
            </tr>
        </table>
        <hr style="margin: 5px 0; border-bottom: 2px solid #ed8d0f;">
        <h3 class="text-center" style="text-decoration: underline; margin-bottom: 2px;">
            BULLETIN DE PAIE - PÉRIODE : {{ strtoupper($payroll->period) }}
        </h3>
    </div>

    <div style="margin-top: 5px; margin-bottom: 10px;">
        <table class="sub-table">
            <tr>
                <td style="width: 50%;"><strong>Nom & Postnom :</strong> {{ $payroll->user->name }} {{ $payroll->user->postnom }}</td>
                <td style="width: 50%;"><strong>Fonction :</strong> {{ $payroll->user->profession ?? 'Non définie' }}</td>
            </tr>
            <tr>
                <td><strong>Matricule :</strong> {{ $payroll->user->code ?? 'N/A' }}</td>
                <td><strong>Statut civil :</strong> {{ $payroll->user->etat_civil ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td><strong>Contact :</strong> {{ $payroll->user->telephone ?? 'N/A' }}</td>
                <td><strong>Adresse :</strong> {{ $payroll->user->adresse_physique ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th colspan="2">Gains ({{ $payroll->currency }})</th>
                <th colspan="2">Retenues ({{ $payroll->currency }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="section-title text-start" colspan="2">Eléments de Salaire</td>
                <td class="section-title text-start" colspan="2">Charges Sociales & Impôts</td>
            </tr>
            <tr>
                <td>Salaire de base</td>
                <td class="text-end">{{ number_format($payroll->amount, 2) }}</td>
                <td>CNSS (3% du brut)</td>
                <td class="text-end">{{ number_format($payroll->amount * 0.03, 2) }}</td>
            </tr>
            <tr>
                <td>Prime d'ancienneté</td>
                <td class="text-end">0.00</td>
                <td>Impôt Professionnel sur les Rémunérations (IPR)</td>
                <td class="text-end">{{ number_format($payroll->amount * 0.07, 2) }}</td>
            </tr>
            <tr>
                <td>Indemnité de logement</td>
                <td class="text-end">0.00</td>
                <td>Syndicat</td>
                <td class="text-end">0.00</td>
            </tr>
            <tr>
                <td>Indemnité de transport</td>
                <td class="text-end">0.00</td>
                <td>Avance sur salaire</td>
                <td class="text-end">0.00</td>
            </tr>
            <tr class="total-row">
                <td>Total Gains Bruts</td>
                <td class="text-end">{{ number_format($payroll->amount, 2) }}</td>
                <td>Total Retenues</td>
                <td class="text-end">{{ number_format($payroll->amount * 0.1, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="table" style="width: 50%; float: right; margin-top: 0;">
        <thead>
            <tr>
                <th colspan="2">Récapitulatif ({{ $payroll->currency }})</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Salaire Brut Imposable</td>
                <td class="text-end">{{ number_format($payroll->amount, 2) }}</td>
            </tr>
            <tr>
                <td>Total Retenues</td>
                <td class="text-end">{{ number_format($payroll->amount * 0.1, 2) }}</td>
            </tr>
            <tr class="total-row final-net">
                <td>Net à Payer</td>
                <td class="text-end">
                    <strong>{{ number_format($payroll->amount - ($payroll->amount * 0.1), 2) }}</strong>
                </td>
            </tr>
        </tbody>
    </table>
    <div style="clear: both;"></div>

    <!-- Signatures -->
    <table style="border: none; border-collapse: collapse; width: 100%; margin-top:5px">
        <tr>
            <td style="border: none; padding: 0; text-align: left;">
                Signature Employé<br><br><br><br>
                <strong>{{ $payroll->user->name }} {{ $payroll->user->postnom }}</strong>
            </td>
            <td style="border: none; padding: 0; text-align: left;">
                Visa Ressources Humaines<br><br><br><br>
                <strong></strong>
            </td>
            <td style="border: none; padding: 0; text-align:right">
                Visa Responsable Gérant<br><br><br><br>
                <strong>{{ auth()->user()->name ?? 'Responsable RH' }} {{ auth()->user()->postnom ?? '' }}</strong>
            </td>
        </tr>
    </table>


    {{-- <div class="footer">
        Bulletin généré le {{ now()->format('d/m/Y H:i') }} - {{ config('app.name') }}
    </div> --}}
</body>
</html>

