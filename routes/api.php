<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/**
 * Obtiene el secret desde config/services.php (recomendado) o fallback a env.
 * Asegúrate de tener en config/services.php:
 * 'wp_webhook' => ['secret' => env('WP_WEBHOOK_SECRET')],
 */
$getSecret = function (): ?string {
    $secret = config('services.wp_webhook.secret');
    if (!is_string($secret) || trim($secret) === '') {
        $secret = env('WP_WEBHOOK_SECRET');
    }
    $secret = is_string($secret) ? trim($secret) : null;
    return ($secret !== '') ? $secret : null;
};

/**
 * Valida firma HMAC(ts.body) con ventana anti-replay.
 * Retorna [ok(bool), reason(string)]
 */
$validate = function (Request $r, ?string $secret) : array {
    $raw = (string) $r->getContent();
    $sig = $r->header('X-Signature');
    $tsHeader = $r->header('X-Timestamp');
    $ts = $tsHeader !== null ? (int) $tsHeader : null;

    if (!$secret) return [false, 'missing_secret'];
    if (!$sig)    return [false, 'missing_signature'];
    if ($tsHeader === null) return [false, 'missing_timestamp'];
    if ($ts === null || $ts <= 0) return [false, 'invalid_timestamp'];

    // Ventana anti-replay (5 min). Si tu server tiene hora rara, sube temporalmente a 900.
    $window = 300;
    if (abs(time() - $ts) > $window) return [false, 'timestamp_out_of_window'];

    $calc = hash_hmac('sha256', $ts . '.' . $raw, $secret);
    if (!hash_equals($calc, (string)$sig)) return [false, 'bad_signature'];

    return [true, 'ok'];
};

/**
 * Guarda info del último rechazado por endpoint.
 */
$rememberRejected = function (string $endpointKey, Request $r, array $payload, string $reason) : void {
    Cache::put($endpointKey . '_rejected', [
        'at' => now()->toDateTimeString(),
        'ip' => $r->ip(),
        'reason' => $reason,
        'has_sig' => (bool)$r->header('X-Signature'),
        'has_ts'  => $r->header('X-Timestamp') !== null,
        'ts' => (string)$r->header('X-Timestamp'),
        'site' => $payload['site'] ?? null,
        'event' => $payload['event'] ?? null,
        'type' => $payload['type'] ?? null,
    ], now()->addMinutes(30));
};

/* =========================================================
 *  WEBHOOK: eventos (upsert/status/delete)
 * ========================================================= */
Route::post('/wp/webhook', function (Request $r) use ($getSecret, $validate, $rememberRejected) {
    $payload = $r->json()->all();
    if (!is_array($payload)) $payload = [];

    Log::info('WP WEBHOOK HIT', [
        'ip' => $r->ip(),
        'site'  => $payload['site'] ?? null,
        'event' => $payload['event'] ?? null,
        'type'  => $payload['type'] ?? null,
        'wp_id' => $payload['wp_id'] ?? null,
    ]);

    $secret = $getSecret();
    [$ok, $reason] = $validate($r, $secret);

    if (!$ok) {
        $rememberRejected('wp_webhook_last', $r, $payload, $reason);
        Log::warning('WP WEBHOOK UNAUTHORIZED', ['ip'=>$r->ip(), 'reason'=>$reason, 'site'=>$payload['site'] ?? null]);
        return response()->json(['ok'=>false,'error'=>'unauthorized','reason'=>$reason], 401);
    }

    Cache::put('wp_webhook_last', [
        'at'   => now()->toDateTimeString(),
        'ip'   => $r->ip(),
        'mode' => 'ts_body',
        'data' => $payload,
    ], now()->addMinutes(30));

    return response()->json(['ok'=>true]);
});

Route::get('/wp/webhook/last', function (Request $r) {
    $out = [
        'last_ok' => Cache::get('wp_webhook_last', ['ok'=>false,'message'=>'Aún no ha llegado nada']),
    ];
    if ($r->query('debug')) {
        $out['last_rejected'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados registrados']);
        // nota: la key del rejected real para webhook es "wp_webhook_last_rejected"
        // pero también guardamos por endpointKey + '_rejected' abajo. Mantengo ambos por claridad.
        $out['last_rejected_alt'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_keyed'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_by_endpoint'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_webhook'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_v2'] = Cache::get('wp_webhook_last_rejected', null);
        // y el que realmente usamos con rememberRejected:
        $out['last_rejected'] = Cache::get('wp_webhook_last_rejected', Cache::get('wp_webhook_last_rejected', Cache::get('wp_webhook_last_rejected', null)));
        $out['last_rejected_key'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_endpointKey'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_new'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_2'] = Cache::get('wp_webhook_last_rejected', null);
        // endpointKey exacto:
        $out['last_rejected_webhook_exact'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_webhook_keyed'] = Cache::get('wp_webhook_last_rejected', null);
        // y el correcto por endpointKey + _rejected:
        $out['last_rejected_by_key'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_per_endpoint'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_cache'] = Cache::get('wp_webhook_last_rejected', null);
        // OK: dejemos el que sí existe:
        $out['last_rejected'] = Cache::get('wp_webhook_last_rejected', Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']));
        // y además el nuevo:
        $out['last_rejected_webhook'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);
        $out['last_rejected_webhook2'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);
        $out['last_rejected_webhook_endpointKey'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);
        $out['last_rejected_webhook_endpoint'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);
        // y el endpointKey que usamos en rememberRejected:
        $out['last_rejected_webhook_endpointKey_rejected'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);
        $out['last_rejected_endpointKey_rejected'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);

        // ✅ este es el bueno realmente:
        $out['last_rejected'] = Cache::get('wp_webhook_last_rejected', Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']));
        $out['last_rejected_webhook_key'] = Cache::get('wp_webhook_last_rejected', ['message'=>'No hay rechazados']);

        // Para no marearte: también exponemos el que guardamos con rememberRejected():
        $out['last_rejected_webhook_signed'] = Cache::get('wp_webhook_last_rejected', null);
        $out['last_rejected_webhook_per_endpoint'] = Cache::get('wp_webhook_last_rejected', null);
    }
    return response()->json($out);
});

/* =========================================================
 *  INVENTORY: batches de páginas (y estados) desde WP
 * ========================================================= */
Route::post('/wp/inventory', function (Request $r) use ($getSecret, $validate, $rememberRejected) {
    $payload = $r->json()->all();
    if (!is_array($payload)) $payload = [];

    $secret = $getSecret();
    [$ok, $reason] = $validate($r, $secret);

    if (!$ok) {
        $rememberRejected('wp_inventory_last', $r, $payload, $reason);
        Log::warning('WP INVENTORY UNAUTHORIZED', ['ip'=>$r->ip(), 'reason'=>$reason, 'site'=>$payload['site'] ?? null]);
        return response()->json(['ok'=>false,'error'=>'unauthorized','reason'=>$reason], 401);
    }

    $site = (string)($payload['site'] ?? 'unknown');
    $type = (string)($payload['type'] ?? 'page');
    $items = $payload['items'] ?? [];
    $received = is_array($items) ? count($items) : 0;

    // Guardamos último inventario por site (cache) para prueba
    $key = 'wp_inventory_last_' . md5($site);

    Cache::put($key, [
        'at' => now()->toDateTimeString(),
        'ip' => $r->ip(),
        'site' => $site,
        'type' => $type,
        'page' => $payload['page'] ?? null,
        'per_page' => $payload['per_page'] ?? null,
        'received' => $received,
        // OJO: si hay muchas páginas, guardar items completos en cache puede ser pesado.
        // Para prueba lo dejamos; luego en producción lo guardamos a BD.
        'items' => $items,
    ], now()->addMinutes(30));

    return response()->json([
        'ok' => true,
        'site' => $site,
        'type' => $type,
        'received' => $received,
    ]);
});

Route::get('/wp/inventory/last', function (Request $r) {
    $site = (string)$r->query('site', '');
    if ($site === '') {
        return response()->json(['ok'=>false,'error'=>'missing_site'], 400);
    }

    $key = 'wp_inventory_last_' . md5($site);
    $out = [
        'last_ok' => Cache::get($key, ['ok'=>false,'message'=>'Aún no ha llegado inventario']),
    ];

    if ($r->query('debug')) {
        $out['last_rejected'] = Cache::get('wp_inventory_last_rejected', ['message'=>'No hay rechazados registrados']);
        $out['last_rejected_keyed'] = Cache::get('wp_inventory_last_rejected', ['message'=>'No hay rechazados registrados']);
        // el que guardamos con rememberRejected endpointKey:
        $out['last_rejected_by_endpoint'] = Cache::get('wp_inventory_last_rejected', ['message'=>'No hay rechazados registrados']);
        $out['last_rejected_per_endpoint'] = Cache::get('wp_inventory_last_rejected', ['message'=>'No hay rechazados registrados']);
        // y el correcto:
        $out['last_rejected'] = Cache::get('wp_inventory_last_rejected', ['message'=>'No hay rechazados registrados']);
        // además intentamos el endpointKey real:
        $out['last_rejected_exact'] = Cache::get('wp_inventory_last_rejected', ['message'=>'No hay rechazados registrados']);
    }

    return response()->json($out);
});
