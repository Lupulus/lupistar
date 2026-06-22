<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockMobileAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isMobileRequest($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Lupistar n’est pas accessible sur mobile.',
            ], 403, [
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
            ]);
        }

        $html = <<<'HTML'
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupistar — Indisponible sur mobile</title>
  <style>
    :root { color-scheme: dark; }
    html, body { height: 100%; margin: 0; }
    body {
      background: #1a1a1a;
      color: #e0e0e0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      display: grid;
      place-items: center;
      padding: 24px;
    }
    .card {
      max-width: 720px;
      width: 100%;
      background: linear-gradient(135deg, #2d2d2d 0%, #404040 100%);
      border: 2px solid #ff8c00;
      border-radius: 16px;
      box-shadow: 0 18px 40px rgba(0,0,0,0.45);
      padding: 22px 20px;
    }
    h1 { margin: 0 0 10px 0; font-size: 1.35rem; color: #ffffff; }
    p { margin: 0; line-height: 1.55; font-weight: 500; color: #b0b0b0; }
    .hint {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid rgba(255,255,255,0.12);
      color: #e0e0e0;
      font-weight: 700;
    }
  </style>
</head>
<body>
  <div class="card">
    <h1>Indisponible sur mobile</h1>
    <p>Lupistar n’est pas accessible sur mobile pour le moment.</p>
    <p class="hint">Merci d’utiliser un ordinateur pour accéder au site.</p>
  </div>
</body>
</html>
HTML;

        return response($html, 403, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    private function isMobileRequest(Request $request): bool
    {
        $chMobile = $request->header('Sec-CH-UA-Mobile');
        if ($chMobile === '?1') {
            return true;
        }

        $ua = (string) $request->userAgent();
        if ($ua === '') {
            return false;
        }

        return (bool) preg_match('/\b(Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Windows Phone|Opera Mini|Mobile)\b/i', $ua);
    }
}
