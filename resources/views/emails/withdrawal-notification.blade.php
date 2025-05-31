<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Retrait de carnet</title>
    </head>
    <body>
        <h2>Bonjour {{ $book->subscription->user->name }},</h2>

        <p>Nous vous informons que votre carnet <strong>{{ $book->code }}</strong> a été verrouillé.</p>

        @php
            $newAmount = $book->total_amount - $book->subscription->montant_souscrit;
        @endphp

        <p><strong>Montant total récupéré :</strong> {{ number_format($newAmount, 0, ',', '.') }} FC</p>

        <p>Veuillez trouver ci-joint le récapitulatif de vos dépôts dans ce carnet.</p>

        <p>Merci pour votre confiance !</p>

        <br>
        <p>L’équipe de gestion des contributions</p>
    </body>
</html>
