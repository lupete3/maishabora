<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rapport Mensuel</title>
</head>
<body>
    <h2>Bonjour {{ $member->name }},</h2>

    <p>Nous vous envoyons votre rapport mensuel concernant vos contributions.</p>

    <p><strong>Total déposé ce mois-ci :</strong> {{ number_format($totalDeposited, 0, ',', '.') }} FC</p>

    <p>Veuillez trouver ci-joint le récapitulatif complet.</p>

    <p>Merci pour votre fidélité,</p>
    <p>L’équipe de gestion</p>
</body>
</html>
