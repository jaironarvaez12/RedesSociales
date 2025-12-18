<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

Route::post('/wp/webhook', function (Request $r) {
    $secret = env('WP_WEBHOOK_SECRET');

    $raw = $r->getContent();
    $sig = (string) $r->header('X-Signature');

    $calc = hash_hmac('sha256', $raw, (string)$secret);

    if (!$secret || !$sig || !hash_equals($calc, $sig)) {
        Log::warning('WP webhook: bad_signature', ['ip'=>$r->ip()]);
        return response()->json(['ok'=>false,'error'=>'bad_signature'], 401);
    }

    $data = $r->json()->all();

    Cache::put('wp_webhook_last', [
        'at'   => now()->toDateTimeString(),
        'ip'   => $r->ip(),
        'data' => $data,
    ], now()->addMinutes(10));

    return response()->json(['ok'=>true]);
});

Route::get('/wp/webhook/last', function () {
    return Cache::get('wp_webhook_last', ['ok'=>false,'message'=>'Aún no ha llegado nada']);
});
