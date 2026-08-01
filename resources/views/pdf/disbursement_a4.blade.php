<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Bon de Décaissement</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 80px;
        }

        .title {
            font-size: 18px;
            font-weight: bold;
            text-decoration: underline;
            margin: 20px 0;
            text-align: center;
        }

        .content {
            margin: 20px auto;
            width: 90%;
            border: 1px solid #000;
            padding: 20px;
        }

        .row {
            margin-bottom: 10px;
            display: flex;
        }

        .label {
            font-weight: bold;
            width: 150px;
            display: inline-block;
        }

        .footer {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 40%;
            text-align: center;
            border-top: 1px solid #000;
            padding-top: 10px;
            margin-top: 50px;
        }
    </style>
</head>

<body>
    @include('partials.pdf-header', ['reportTitle' => 'BON DE DÉCAISSEMENT N° ' . $transaction->id])

    <div class="content">
        <div class="row">
            <span class="label">Date :</span>
            <span>{{ $transaction->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span class="label">Bénéficiaire :</span>
            <span>(Interne)</span>
        </div>
        <div class="row">
            <span class="label">Motif :</span>
            <span>{{ $transaction->disbursementType->name ?? 'Autre' }}</span>
        </div>
        <div class="row">
            <span class="label">Description :</span>
            <span>{{ $transaction->description }}</span>
        </div>
        <div class="row">
            <span class="label">Montant :</span>
            <span style="font-weight: bold; font-size: 14px;">{{ number_format($transaction->amount, 2) }}
                {{ $transaction->currency }}</span>
        </div>
    </div>

    <div class="footer" style="width: 90%; margin: 50px auto 0 auto;">
        <table style="width:100%">
            <tr>
                <td style="width:20%; text-align:center">
                    <strong>Caissier</strong>
                    <br><br><br>
                    {{ $transaction->user->name . ' ' . $transaction->user?->prostnom . ' ' . $transaction->user?->prenom }}
                </td>
                <td style="width:20%; text-align:center">
                    <strong>Chef Comptable</strong>
                    <br><br><br>
                    .....................
                </td>
                <td style="width:20%; text-align:center">
                    <strong>Bénéficiaire</strong>
                    <br><br><br>
                    .....................
                </td>
                <td style="width:20%; text-align:center">
                    <strong>Controleur Interne</strong>
                    <br><br><br>
                    .....................
                </td>
                <td style="width:20%; text-align:center">
                    <strong>Pour Approbation</strong>
                    <br><br><br>
                    .....................
                </td>
            </tr>
        </table>
    </div>
</body>

</html>
