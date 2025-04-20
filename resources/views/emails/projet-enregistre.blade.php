<!DOCTYPE html>
<html>
<head>
    <title>Confirmation d'enregistrement de votre projet</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2c3e50;
        }
        .content {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Confirmation de soumission de votre projet</h1>
    </div>

    <div class="content">
        <p>Bonjour {{ $projet->nom }},</p>
        
        <p>Nous vous informons que votre projet a été enregistré avec succès et est actuellement <strong>en cours d'examen</strong>.</p>
        
        <p>Détails du projet :</p>
        <ul>
            <li><strong>Type de projet :</strong> {{ $projet->type_projet }}</li>
            <li><strong>Forme juridique :</strong> {{ $projet->forme_juridique }}</li>
        </ul>
        
        <p>Notre équipe va examiner votre dossier dans les meilleurs délais. Vous recevrez une notification dès que le statut de votre projet sera mis à jour.</p>
        
        <p>Pour toute question ou information complémentaire, n'hésitez pas à nous contacter.</p>
        
        <p>Cordialement,</p>
        <p>L'équipe GestPro</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} GestPro. Tous droits réservés.</p>
    </div>
</body>
</html> 