<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mise à jour de réclamation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #faf9f7; margin: 0; padding: 20px; }
        .container { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background: #3d4f2f; padding: 32px; text-align: center; }
        .header h1 { color: #fdfbf5; font-size: 22px; margin: 0; font-weight: 600; }
        .header p { color: rgba(253,251,245,0.7); font-size: 13px; margin-top: 8px; }
        .body { padding: 32px; }
        .status-badge { display: inline-block; padding: 6px 14px; border-radius: 20px; font-size: 13px; font-weight: 600; }
        .status-in_review { background: #e8f0e0; color: #3d4f2f; }
        .status-resolved { background: #3d4f2f; color: #fdfbf5; }
        .status-rejected { background: #e7e5e4; color: #78716c; }
        .info { margin-top: 24px; }
        .info dt { font-size: 13px; color: #78716c; margin-bottom: 4px; }
        .info dd { font-size: 15px; font-weight: 500; margin: 0 0 16px 0; color: #1c1917; }
        .response-box { background: #f5f5f0; border-radius: 12px; padding: 20px; margin-top: 20px; }
        .response-box p.label { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7c5a; margin: 0 0 8px 0; }
        .response-box p.text { font-size: 14px; line-height: 1.7; color: #44403c; margin: 0; }
        .cta { display: inline-block; margin-top: 24px; padding: 12px 24px; background: #3d4f2f; color: #fdfbf5; text-decoration: none; border-radius: 10px; font-size: 14px; font-weight: 600; }
        .footer { text-align: center; padding: 24px 32px; font-size: 12px; color: #a8a29e; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🫒 OliveTrace</h1>
            <p>Mise à jour de votre réclamation</p>
        </div>
        <div class="body">
            <p style="font-size: 15px; color: #44403c; line-height: 1.7;">Bonjour <strong>{{ $complaint->consumer?->name ?? 'Cher client' }}</strong>,</p>
            <p style="font-size: 15px; color: #44403c; line-height: 1.7;">Le statut de votre réclamation a été mis à jour :</p>

            <dl class="info">
                <dt>Réclamation</dt>
                <dd>#{{ $complaint->id }} — {{ $complaint->subject }}</dd>
                <dt>Produit</dt>
                <dd>{{ $complaint->product?->name ?? 'Produit #' . $complaint->oil_product_id }}</dd>
                <dt>Nouveau statut</dt>
                <dd>
                    @php
                        $statusLabels = ['in_review' => 'En cours d\'examen', 'resolved' => 'Résolue', 'rejected' => 'Rejetée'];
                    @endphp
                    <span class="status-badge status-{{ $newStatus }}">{{ $statusLabels[$newStatus] ?? ucfirst($newStatus) }}</span>
                </dd>
            </dl>

            @if($complaint->admin_response)
                <div class="response-box">
                    <p class="label">Réponse de l'équipe</p>
                    <p class="text">{{ $complaint->admin_response }}</p>
                </div>
            @endif

            <a class="cta" href="{{ url('/complaints/' . $complaint->id) }}">Voir ma réclamation →</a>
        </div>
        <div class="footer">
            <p>Cet email a été envoyé automatiquement par OliveTrace.<br>Traçabilité de l'huile d'olive tunisienne, de la ferme au consommateur.</p>
        </div>
    </div>
</body>
</html>
