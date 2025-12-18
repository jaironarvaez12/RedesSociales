<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Webhook receptor (con soporte de:
 *  - firma nueva: HMAC(ts.body) + ventana anti-replay
 *  - firma compat: HMAC(body) si no llega ts
 */
Route::post('/wp/webhook', function (Request $r) {
    $raw = (string) $r->getContent();

    $sig = $r->header('X-Signature');
    $tsHeader = $r->header('X-Timestamp');
    $ts = $tsHeader !== null ? (int) $tsHeader : null;

    $payload = $r->json()->all();
    if (!is_array($payload)) $payload = [];

    // ✅ Secret (recomendado desde config/services.php; fallback a env)
    $secret = config('services.wp_webhook.secret');
    if (!is_string($secret) || trim($secret) === '') {
       $secret = config('services.wp_webhook.secret');
    }
    $secret = is_string($secret) ? trim($secret) : null;
    if ($secret === '') $secret = null;

    Log::info('WP webhook HIT', [
        'ip' => $r->ip(),
        'has_sig' => (bool)$sig,
        'has_ts'  => $tsHeader !== null,
        'site'    => $payload['site'] ?? null,
        'event'   => $payload['event'] ?? null,
        'type'    => $payload['type'] ?? null,
        'wp_id'   => $payload['wp_id'] ?? null,
    ]);

    // Validación
    $reason = null;
    $mode = null;

    if (!$secret || !$sig) {
        $reason = 'missing_secret_or_signature';
    } else {
        $sig = (string) $sig;

        if ($tsHeader !== null) {
            // Ventana anti-replay (5 min)
            $window = 300;

            if ($ts <= 0) {
                $reason = 'invalid_timestamp';
            } elseif (abs(time() - $ts) > $window) {
                $reason = 'timestamp_out_of_window';
            } else {
                $calc = hash_hmac('sha256', $ts . '.' . $raw, $secret);
                if (!hash_equals($calc, $sig)) {
                    $reason = 'bad_signature';
                } else {
                    $mode = 'ts_body';
                }
            }
        } else {
            // Compat: firma solo del body
            $calc = hash_hmac('sha256', $raw, $secret);
            if (!hash_equals($calc, $sig)) {
                $reason = 'bad_signature';
            } else {
                $mode = 'body_only';
            }
        }
    }

    if ($reason !== null) {
        Cache::put('wp_webhook_last_rejected', [
            'at' => now()->toDateTimeString(),
            'ip' => $r->ip(),
            'reason' => $reason,
            'has_sig' => (bool)$r->header('X-Signature'),
            'has_ts' => $tsHeader !== null,
            'ts' => $tsHeader,
            'site' => $payload['site'] ?? null,
            'event' => $payload['event'] ?? null,
        ], now()->addMinutes(30));

        Log::warning('WP webhook UNAUTHORIZED', [
            'ip' => $r->ip(),
            'reason' => $reason,
            'ts' => $tsHeader,
            'site' => $payload['site'] ?? null,
        ]);

        return response()->json(['ok' => false, 'error' => 'unauthorized', 'reason' => $reason], 401);
    }

    Cache::put('wp_webhook_last', [
        'at'   => now()->toDateTimeString(),
        'ip'   => $r->ip(),
        'mode' => $mode,
        'data' => $payload,
    ], now()->addMinutes(30));

    return response()->json(['ok' => true]);
});

Route::get('/wp/webhook/last', function (Request $r) {
    $out = [
        'last_ok' => Cache::get('wp_webhook_last', ['ok' => false, 'message' => 'Aún no ha llegado nada']),
    ];

    if ($r->query('debug')) {
        $out['last_rejected'] = Cache::get('wp_webhook_last_rejected', ['message' => 'No hay rechazados registrados']);
    }

    return response()->json($out);
});
