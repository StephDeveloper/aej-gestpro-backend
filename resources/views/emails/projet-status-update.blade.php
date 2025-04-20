<!DOCTYPE html>
<html>
<head>
    <title>Mise à jour du statut de votre projet</title>
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
        .status-validated {
            color: #27ae60;
            font-weight: bold;
        }
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        .justification-validated {
            background-color: #fff;
            border-left: 4px solid #27ae60;
            padding: 15px;
            margin: 15px 0;
        }
        .justification-rejected {
            background-color: #fff;
            border-left: 4px solid #e74c3c;
            padding: 15px;
            margin: 15px 0;
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
        <h1>Mise à jour du statut de votre projet</h1>
    </div>

    <div class="content">
        <p>Bonjour {{ $projet->nom }},</p>
        
        @if($projet->statut === 'Validé')
            <p>Nous avons le plaisir de vous informer que votre projet a été <span class="status-validated">VALIDÉ</span>.</p>
            <p>Votre dossier a été examiné avec attention et répond à nos critères d'éligibilité. Félicitations!</p>
            
            @if($projet->justification)
            <div class="justification-validated">
                <p><strong>Remarques :</strong></p>
                <p>{{ $projet->justification }}</p>
            </div>
            @endif
            
            <p>Un membre de notre équipe vous contactera prochainement pour vous informer des prochaines étapes.</p>
        @elseif($projet->statut === 'Rejeté')
            <p>Nous regrettons de vous informer que votre projet a été <span class="status-rejected">REJETÉ</span>.</p>
            <p>Après examen approfondi de votre dossier, nous ne sommes malheureusement pas en mesure de donner suite à votre projet.</p>
            
            @if($projet->justification)
            <div class="justification-rejected">
                <p><strong>Motif :</strong></p>
                <p>{{ $projet->justification }}</p>
            </div>
            @endif
            
            <p>Si vous souhaitez obtenir plus d'informations sur les raisons de cette décision, n'hésitez pas à nous contacter.</p>
        @endif
        
        <p>Détails du projet :</p>
        <ul>
            <li><strong>Type de projet :</strong> {{ $projet->type_projet }}</li>
            <li><strong>Forme juridique :</strong> {{ $projet->forme_juridique }}</li>
            <li><strong>Date de soumission :</strong> {{ $projet->created_at->format('d/m/Y') }}</li>
        </ul>
        
        <p>Pour toute question ou information complémentaire, n'hésitez pas à nous contacter.</p>
        
        <p>Cordialement,</p>
        <p>L'équipe GestPro</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} GestPro. Tous droits réservés.</p>
    </div>
</body>
</html> 