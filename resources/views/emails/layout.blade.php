<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OliveTrace</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #2d3748; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: #f8f6ef; border: 1px solid #e2e5da; border-radius: 12px; padding: 24px; margin: 16px 0; }
        .btn-primary { display: inline-block; background: #283e29; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        .btn-primary:hover { background: #3b512d; }
        .eyebrow { font-size: 0.75rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase; color: #586a3d; margin-bottom: 8px; display: block; }
        .display-title { font-family: Georgia, serif; font-size: 1.5rem; font-weight: 400; color: #283e29; margin: 0 0 16px; }
        dl { margin: 0; }
        dt { color: #78716c; font-size: 0.875rem; }
        dd { margin: 4px 0 0; font-weight: 600; }
        .grid { display: grid; gap: 16px; }
        @media (min-width: 640px) { .sm\:grid-cols-2 { grid-template-columns: repeat(2, 1fr); } .sm\:grid-cols-3 { grid-template-columns: repeat(3, 1fr); } }
        .text-center { text-align: center; }
        .text-red-600 { color: #dc2626; }
        .text-olive-600 { color: #586a3d; }
        .text-sm { font-size: 0.875rem; }
        .leading-6 { line-height: 1.5rem; }
        .font-semibold { font-weight: 600; }
        .font-bold { font-weight: 700; }
        .text-olive-600 { color: #586a3d; }
        .mb-2 { margin-bottom: 8px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .p-4 { padding: 16px; }
        .rounded-xl { border-radius: 12px; }
        .border { border: 1px solid #e2e5da; }
        .bg-olive-50 { background: #eef2e8; }
    </style>
</head>
<body>
    <div class="container">
        <div style="text-align: center; margin-bottom: 24px;">
            <span style="font-family: Georgia, serif; font-size: 1.5rem; font-weight: 600; color: #283e29;">OliveTrace</span>
        </div>
        @yield('content')
        <hr style="border-color: #e2e5da; margin: 32px 0;">
        <p style="font-size: 0.75rem; color: #989389; text-align: center;">
            OliveTrace — Traçabilité de l'huile d'olive tunisienne<br>
            Cet email a été envoyé automatiquement, merci de ne pas y répondre.
        </p>
    </div>
</body>
</html>