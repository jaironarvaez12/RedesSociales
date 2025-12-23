<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Str;

use App\Models\DominiosModel;
use App\Models\Dominios_Contenido_DetallesModel;

class GenerarContenidoKeywordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Producción
    public $timeout = 4200;
    public $tries   = 5;
    public $backoff = [60, 120, 300, 600, 900];

    public ?int $registroId = null;

    /**
     * Clave idempotente: MISMO input => MISMO job_uuid
     * (si lo disparas 2 veces por error, NO crea 2 registros)
     */
    public string $jobUuid;

    private array $briefContext = [];

    public function __construct(
        public string $idDominio,
        public string $idDominioContenido,
        public string $tipo,
        public string $keyword
    ) {
        // sha1 => 40 chars (asegúrate que job_uuid soporte 40)
        $this->jobUuid = sha1(
            'dom='.(int)$this->idDominio
            .'|cont='.(int)$this->idDominioContenido
            .'|tipo='.$this->tipo
            .'|kw='.mb_strtolower(trim($this->keyword))
        );
    }

    public function handle(): void
    {
        // 1) Cargar template primero (para saber qué tokens necesita)
        [$tpl, $tplPath] = $this->loadElementorTemplateForDomainWithPath((int)$this->idDominio);
        $templateTokens = $this->extractTokensDeep($tpl);

        if (count($templateTokens) === 0) {
            throw new \RuntimeException("La plantilla no tiene tokens {{...}}. Path: {$tplPath}");
        }

        // 2) Crear/recuperar registro de forma idempotente por job_uuid
        $registro = Dominios_Contenido_DetallesModel::where('job_uuid', $this->jobUuid)->first();

        if (!$registro) {
            // Si en tu tabla no existe job_uuid aún, primero aplica la migración del apartado C
            $registro = Dominios_Contenido_DetallesModel::create([
                'job_uuid' => $this->jobUuid,
                'id_dominio_contenido' => (int)$this->idDominioContenido,
                'id_dominio' => (int)$this->idDominio,
                'tipo' => $this->tipo,
                'keyword' => $this->keyword,
                'estatus' => 'en_proceso',
                'modelo' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            ]);

            $this->registroId = (int)$registro->id_dominio_contenido_detalle;
        } else {
            $this->registroId = (int)$registro->id_dominio_contenido_detalle;
            $registro->update([
                'estatus' => 'en_proceso',
                'modelo' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            ]);
        }

        try {
            $apiKey = (string) env('DEEPSEEK_API_KEY', '');
            $model  = (string) env('DEEPSEEK_MODEL', 'deepseek-chat');

            if ($apiKey === '') {
                throw new \RuntimeException('DEEPSEEK_API_KEY no configurado');
            }

            // =======================================
            // Historial para NO repetir (opcional)
            // =======================================
            $prev = Dominios_Contenido_DetallesModel::where('id_dominio_contenido', (int)$this->idDominioContenido)
                ->whereNotNull('draft_html')
                ->orderByDesc('id_dominio_contenido_detalle')
                ->limit(6)
                ->get(['draft_html']);

            $usedCorpus = [];
            foreach ($prev as $row) {
                $usedCorpus[] = $this->copyTextFromDraftJson((string)$row->draft_html);
            }
            $noRepetirCorpus = $this->compactHistory($usedCorpus, 2000);

            // =======================================
            // Brief (genérico para todo tipo de plantillas)
            // =======================================
            $brief = $this->creativeBrief($this->keyword);
            $this->briefContext = $brief;

            // =======================================
            // 3) Pedir a IA: JSON con keys = tokens exactos
            // =======================================
            $prompt = $this->promptTokensJson(
                $this->tipo,
                $this->keyword,
                $templateTokens,
                $noRepetirCorpus,
                $brief
            );

            $raw = $this->deepseekText(
                $apiKey,
                $model,
                $prompt,
                maxTokens: 3600,
                temperature: 0.85,
                topP: 0.90,
                jsonMode: true
            );

            $tokenValues = $this->safeParseOrRepairTokens($apiKey, $model, $raw, $templateTokens, $brief);

            // 4) Construir diccionario de reemplazo {{TOKEN}} => valor
            $dict = $this->buildTokenDictionary($templateTokens, $tokenValues);

            // 5) Reemplazar en template
            $replacedCount = 0;
            $this->replaceTokensDeep($tpl, $dict, $replacedCount);
            $remaining = $this->collectRemainingTokensDeep($tpl);

            if (count($remaining) > 0) {
                // Si quedan tokens, rellenamos con fallback variado (para NO dejar basura)
                $fallbackDict = $this->buildFallbackDictionary($remaining);
                $this->replaceTokensDeep($tpl, $fallbackDict, $replacedCount);
                $remaining2 = $this->collectRemainingTokensDeep($tpl);

                if (count($remaining2) > 0) {
                    throw new \RuntimeException("Quedaron tokens sin reemplazar: ".implode(' | ', array_slice($remaining2, 0, 60)));
                }
            }

            // 6) Title + slug (usa el primer heading si existe)
            $title = $this->pickTitleFromTokenValues($tokenValues) ?: $this->keyword;
            $title = trim(strip_tags($title));
            if ($title === '') $title = $this->keyword;

            $slugBase = Str::slug($title ?: $this->keyword);
            $slug = $slugBase . '-' . $registro->id_dominio_contenido_detalle;

            // 7) Guardar
            $registro->update([
                'title' => $title,
                'slug' => $slug,
                // draft_html: guardamos el “relleno de tokens”
                'draft_html' => json_encode([
                    'tokens' => $tokenValues,
                    'brief' => $brief,
                    'template_tokens_count' => count($templateTokens),
                    'replaced_count' => $replacedCount,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'contenido_html' => json_encode($tpl, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'estatus' => 'generado',
                'error' => null,
            ]);

        } catch (\Throwable $e) {
            $isLast = ($this->attempts() >= (int)$this->tries);

            if (isset($registro) && $registro) {
                $registro->update([
                    'estatus' => $isLast ? 'error_final' : 'error',
                    'error' => $e->getMessage().' | attempts='.$this->attempts(),
                ]);
            }

            throw $e;
        }
    }

    // ===========================================================
    // TEMPLATE LOADER
    // ===========================================================
    private function loadElementorTemplateForDomainWithPath(int $idDominio): array
    {
        $dominio = DominiosModel::where('id_dominio', $idDominio)->first();
        if (!$dominio) throw new \RuntimeException("Dominio no encontrado (id={$idDominio})");

        $templateRel = trim((string)($dominio->elementor_template_path ?? ''));
        if ($templateRel === '') $templateRel = trim((string) env('ELEMENTOR_TEMPLATE_PATH', ''));
        if ($templateRel === '') throw new \RuntimeException('No hay plantilla configurada (dominio ni env ELEMENTOR_TEMPLATE_PATH).');

        $templateRel = str_replace(['https:', 'http:'], '', $templateRel);

        if (preg_match('~^https?://~i', $templateRel)) {
            $u = parse_url($templateRel);
            $templateRel = $u['path'] ?? $templateRel;
        }

        $templateRel = preg_replace('~^/?storage/app/~i', '', $templateRel);
        $templateRel = ltrim(str_replace('\\', '/', $templateRel), '/');

        if (str_contains($templateRel, '..')) throw new \RuntimeException('Template path inválido (no se permite "..")');

        $templatePath = storage_path('app/' . $templateRel);
        if (!is_file($templatePath)) throw new \RuntimeException("No existe el template en disco: {$templatePath} (path={$templateRel})");

        $raw = (string) file_get_contents($templatePath);
        $tpl = json_decode($raw, true);

        if (!is_array($tpl) || !isset($tpl['content']) || !is_array($tpl['content'])) {
            throw new \RuntimeException('Template Elementor inválido: debe contener "content" (array).');
        }

        return [$tpl, $templatePath];
    }

    // ===========================================================
    // TOKENS: extracción + reemplazo
    // ===========================================================
    private function extractTokensDeep(mixed $node): array
    {
        $found = [];
        $walk = function ($n) use (&$walk, &$found) {
            if (is_array($n)) { foreach ($n as $v) $walk($v); return; }
            if (!is_string($n) || $n === '') return;
            if (preg_match_all('/\{\{[A-Z0-9_]+\}\}/', $n, $m)) {
                foreach ($m[0] as $tok) $found[] = $tok;
            }
        };
        $walk($node);
        $found = array_values(array_unique($found));
        sort($found);
        return $found;
    }

    private function replaceTokensDeep(mixed &$node, array $dict, int &$count): void
    {
        if (is_array($node)) {
            foreach ($node as &$v) $this->replaceTokensDeep($v, $dict, $count);
            return;
        }

        if (!is_string($node) || $node === '') return;
        if (!str_contains($node, '{{')) return;

        $orig = $node;
        $node = strtr($node, $dict);
        if ($node !== $orig) $count++;
    }

    private function collectRemainingTokensDeep(mixed $node): array
    {
        $found = [];
        $walk = function ($n) use (&$walk, &$found) {
            if (is_array($n)) { foreach ($n as $v) $walk($v); return; }
            if (!is_string($n) || $n === '') return;
            if (preg_match_all('/\{\{[A-Z0-9_]+\}\}/', $n, $m)) foreach ($m[0] as $tok) $found[] = $tok;
        };
        $walk($node);
        $found = array_values(array_unique($found));
        sort($found);
        return $found;
    }

    // ===========================================================
    // PROMPT: generar valores para tokens del template
    // ===========================================================
    private function promptTokensJson(string $tipo, string $keyword, array $tokens, string $noRepetirCorpus, array $brief): string
    {
        $angle = $this->toStr($brief['angle'] ?? '');
        $tone  = $this->toStr($brief['tone'] ?? '');
        $cta   = $this->toStr($brief['cta'] ?? '');
        $aud   = $this->toStr($brief['audience'] ?? '');

        // Clasificación simple para decirle al modelo qué tokens son HTML
        $htmlTokens = [];
        $textTokens = [];
        foreach ($tokens as $t) {
            if ($this->isHtmlToken($t)) $htmlTokens[] = $t;
            else $textTokens[] = $t;
        }

        $tokensList = implode(', ', $tokens);
        $htmlList   = implode(', ', $htmlTokens);
        $textList   = implode(', ', $textTokens);

        return <<<PROMPT
Devuelve SOLO JSON válido. Sin markdown. Sin explicaciones. RESPUESTA MINIFICADA.
Tus keys DEBEN ser EXACTAMENTE los TOKENS (incluyendo {{ y }}).

Contexto:
- Keyword: {$keyword}
- Tipo: {$tipo}

BRIEF (genérico para cualquier plantilla):
- Ángulo: {$angle}
- Tono: {$tone}
- Público: {$aud}
- CTA: {$cta}

NO repetir frases/ideas (resumen historial):
{$noRepetirCorpus}

TOKENS A RELLENAR (keys exactas):
{$tokensList}

REGLAS:
- NO dejes NINGÚN token vacío.
- Evita repetir la misma frase en muchos tokens (varía titulares, párrafos y CTAs).
- Evita “keyword stuffing”.
- No inventes testimonios reales ni números/claims falsos.
- Para tokens HTML: usa SOLO <p>, <strong>, <br>. No uses <h1> jamás.
- Longitud segura:
  - Titulares/botones: cortos (2–9 palabras si se puede)
  - HTML: 1 párrafo corto (ideal 140–260 chars)

TOKENS HTML (deben devolver <p>...</p>):
{$htmlList}

TOKENS TEXTO (sin HTML):
{$textList}

Formato esperado (ejemplo):
{"{{H_01}}":"...","{{P_01}}":"<p>...</p>","{{BTN_01}}":"..."}

PROMPT;
    }

    private function isHtmlToken(string $token): bool
    {
        // según cómo tokenizamos: P_XX y IMG_CAP_XX suelen ser HTML/texto largo
        return str_starts_with($token, '{{P_')
            || str_starts_with($token, '{{IMG_CAP_');
    }

    private function buildTokenDictionary(array $tokens, array $values): array
    {
        $dict = [];
        foreach ($tokens as $t) {
            $v = $this->toStr($values[$t] ?? '');
            $v = $this->sanitizeTokenValue($t, $v);

            if (trim(strip_tags($v)) === '') {
                // fallback por si IA dejó algo vacío
                $v = $this->fallbackForToken($t);
            }

            $dict[$t] = $v;
        }
        return $dict;
    }

    private function sanitizeTokenValue(string $token, string $value): string
    {
        $value = trim($value);

        // Nunca permitir <h1>
        $value = preg_replace('~<\s*h1\b[^>]*>~i', '', $value);
        $value = preg_replace('~<\s*/\s*h1\s*>~i', '', $value);

        if ($this->isHtmlToken($token)) {
            $value = $this->keepAllowedInlineHtml($value);
            // asegurar <p> si vino texto plano
            if ($value !== '' && !str_contains($value, '<p')) {
                $value = '<p>' . e(strip_tags($value)) . '</p>';
            }
        } else {
            $value = trim(strip_tags($value));
        }

        // recortes suaves
        if ($this->isHtmlToken($token) && mb_strlen(strip_tags($value)) > 320) {
            $txt = mb_substr(strip_tags($value), 0, 320);
            $txt = rtrim($txt, " \t\n\r\0\x0B-–—|:");
            $value = '<p>' . e($txt) . '</p>';
        }
        if (!$this->isHtmlToken($token) && mb_strlen($value) > 90) {
            $value = rtrim(mb_substr($value, 0, 90), " \t\n\r\0\x0B-–—|:");
        }

        return $value;
    }

    private function buildFallbackDictionary(array $remainingTokens): array
    {
        $dict = [];
        foreach ($remainingTokens as $t) {
            $dict[$t] = $this->fallbackForToken($t);
        }
        return $dict;
    }

    private function fallbackForToken(string $token): string
    {
        $kw = $this->shortKw();

        if ($this->isHtmlToken($token)) {
            $variants = [
                "<p>Contenido claro y directo sobre {$kw}, con estructura fácil de escanear y foco en la acción.</p>",
                "<p>Texto listo para publicar sobre {$kw}: beneficios concretos, tono profesional y mensaje coherente.</p>",
                "<p>Sección optimizada para {$kw}: lenguaje natural, sin relleno y con llamada a la acción clara.</p>",
            ];
            return $variants[array_rand($variants)];
        }

        if (str_starts_with($token, '{{BTN_')) {
            $btn = ["Saber más", "Ver detalles", "Empezar", "Hablar ahora", "Solicitar info"];
            return $btn[array_rand($btn)];
        }

        if (str_starts_with($token, '{{IMG_ALT_')) {
            return $kw;
        }

        if (str_starts_with($token, '{{LI_') || str_starts_with($token, '{{PT_FEAT_')) {
            $li = [
                "Beneficio aplicable a {$kw}",
                "Enfoque práctico para {$kw}",
                "Mejora visible en {$kw}",
                "Detalle clave para {$kw}",
            ];
            return $li[array_rand($li)];
        }

        // headings (H_XX) u otros
        $heads = [
            "Guía práctica de {$kw}",
            "Solución enfocada en {$kw}",
            "Sección clave sobre {$kw}",
            "Todo lo esencial de {$kw}",
            "Contenido listo para {$kw}",
        ];
        return $heads[array_rand($heads)];
    }

    private function pickTitleFromTokenValues(array $tokenValues): ?string
    {
        // Si existe H_01 úsalo como title
        if (isset($tokenValues['{{H_01}}'])) {
            $t = trim(strip_tags((string)$tokenValues['{{H_01}}']));
            if ($t !== '') return $t;
        }
        // si no, el primer heading que exista
        foreach ($tokenValues as $k => $v) {
            if (is_string($k) && str_starts_with($k, '{{H_')) {
                $t = trim(strip_tags((string)$v));
                if ($t !== '') return $t;
            }
        }
        return null;
    }

    // ===========================================================
    // DeepSeek
    // ===========================================================
    private function deepseekText(
        string $apiKey,
        string $model,
        string $prompt,
        int $maxTokens = 1200,
        float $temperature = 0.90,
        float $topP = 0.92,
        bool $jsonMode = true
    ): string {
        $nonce = 'nonce:' . Str::uuid()->toString();

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'Devuelves SOLO JSON válido. No markdown. No explicaciones.'],
                ['role' => 'user', 'content' => $prompt . "\n\n" . $nonce],
            ],
            'temperature' => $temperature,
            'top_p' => $topP,
            'presence_penalty' => 1.05,
            'frequency_penalty' => 0.45,
            'max_tokens' => $maxTokens,
        ];

        if ($jsonMode) $payload['response_format'] = ['type' => 'json_object'];

        $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(15)
            ->timeout(180)
            ->retry(0, 0)
            ->post('https://api.deepseek.com/v1/chat/completions', $payload);

        if (!$resp->successful() && $jsonMode) {
            $body = (string)$resp->body();
            if (str_contains($body, 'response_format') || str_contains($body, 'unknown') || str_contains($body, 'invalid')) {
                unset($payload['response_format']);
                $resp = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->connectTimeout(15)
                    ->timeout(180)
                    ->retry(0, 0)
                    ->post('https://api.deepseek.com/v1/chat/completions', $payload);
            }
        }

        if (!$resp->successful()) throw new \RuntimeException("DeepSeek error {$resp->status()}: {$resp->body()}");

        $data = $resp->json();
        $text = trim((string)($data['choices'][0]['message']['content'] ?? ''));
        if ($text === '') throw new \RuntimeException("DeepSeek returned empty text.");

        return $text;
    }

    private function safeParseOrRepairTokens(string $apiKey, string $model, string $raw, array $tokens, array $brief): array
    {
        // 1) intento parse normal
        try {
            $a = $this->parseJsonStrict($raw);
            return $this->ensureAllTokenKeys($a, $tokens);
        } catch (\Throwable $e) {
            // 2) repair con IA (minificado)
            $fixed = $this->repairJsonViaDeepseek($apiKey, $model, $raw, $tokens, $brief);

            $b = $this->parseJsonStrict($fixed);
            return $this->ensureAllTokenKeys($b, $tokens);
        }
    }

    private function ensureAllTokenKeys(array $data, array $tokens): array
    {
        // normaliza keys por si vienen sin llaves
        $out = $data;

        foreach ($tokens as $t) {
            if (!array_key_exists($t, $out)) {
                // si vino como "H_01" en vez de "{{H_01}}"
                $k2 = trim($t, '{}');
                if (array_key_exists($k2, $out)) {
                    $out[$t] = $out[$k2];
                    unset($out[$k2]);
                } else {
                    $out[$t] = ''; // fallback luego
                }
            }
        }

        return $out;
    }

    private function repairJsonViaDeepseek(string $apiKey, string $model, string $broken, array $tokens, array $brief): string
    {
        $angle = $this->toStr($brief['angle'] ?? '');
        $tone  = $this->toStr($brief['tone'] ?? '');
        $broken = mb_substr(trim((string)$broken), 0, 9000);
        $tokensList = implode(', ', $tokens);

        $prompt = <<<PROMPT
Devuelve SOLO JSON válido. Sin markdown. Sin texto fuera del JSON.
RESPUESTA MINIFICADA (sin saltos de línea).

Tus keys deben ser EXACTAMENTE estos tokens:
{$tokensList}

Reglas:
- NO dejar valores vacíos.
- HTML permitido SOLO: <p>, <strong>, <br>
- NO uses <h1>

Estilo:
- Ángulo: {$angle}
- Tono: {$tone}

JSON roto:
{$broken}
PROMPT;

        return $this->deepseekText($apiKey, $model, $prompt, maxTokens: 2600, temperature: 0.20, topP: 0.90, jsonMode: true);
    }

    private function parseJsonStrict(string $raw): array
    {
        $raw = trim((string)$raw);
        $raw = preg_replace('~^```(?:json)?\s*~i', '', $raw);
        $raw = preg_replace('~\s*```$~', '', $raw);
        $raw = trim($raw);

        $start = strpos($raw, '{');
        $end   = strrpos($raw, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $raw = substr($raw, $start, $end - $start + 1);
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $snip = mb_substr($raw, 0, 700);
            throw new \RuntimeException('IA no devolvió JSON válido. Snippet: ' . $snip);
        }
        return $data;
    }

    // ===========================================================
    // Compactar historial
    // ===========================================================
    private function compactHistory(array $corpusArr, int $maxChars = 2000): string
    {
        $chunks = [];
        foreach ($corpusArr as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;

            $t = mb_substr($t, 0, 330);
            $chunks[] = $t;

            $joined = implode("\n---\n", $chunks);
            if (mb_strlen($joined) >= $maxChars) break;
        }

        $out = trim(implode("\n---\n", $chunks));
        if (mb_strlen($out) > $maxChars) $out = mb_substr($out, 0, $maxChars);
        return $out;
    }

    // ===========================================================
    // BRIEF
    // ===========================================================
    private function creativeBrief(string $keyword): array
    {
        $angles = [
            "Claridad y orden (se entiende rápido, sin ruido)",
            "Conversión (CTA natural y objeciones cubiertas)",
            "Calidad y consistencia (tono limpio y profesional)",
            "Ejecución rápida (listo para publicar sin vueltas)",
            "Diferenciación (enfoque distinto a lo genérico)",
            "SEO natural (intención y semántica sin repetir de más)",
        ];
        $tones = ["Profesional directo","Cercano y humano","Premium sobrio","Enérgico y comercial","Técnico pero simple"];
        $ctas  = ["Agenda una llamada","Pide una propuesta","Solicita información","Ver detalles","Empezar ahora"];
        $audiences = ["Pymes y autónomos","Negocios locales","Servicios profesionales","Marcas en crecimiento","Equipos comerciales"];

        return [
            'angle' => $angles[random_int(0, count($angles) - 1)],
            'tone' => $tones[random_int(0, count($tones) - 1)],
            'cta' => $ctas[random_int(0, count($ctas) - 1)],
            'audience' => $audiences[random_int(0, count($audiences) - 1)],
        ];
    }

    // ===========================================================
    // Utils
    // ===========================================================
    private function toStr(mixed $v): string
    {
        if ($v === null) return '';
        if (is_string($v)) return $v;
        if (is_int($v) || is_float($v)) return (string)$v;
        if (is_bool($v)) return $v ? '1' : '0';

        if (is_array($v)) {
            foreach (['text','content','value','html'] as $k) {
                if (array_key_exists($k, $v)) return $this->toStr($v[$k]);
            }
            $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($j) ? $j : '';
        }

        if (is_object($v)) {
            if ($v instanceof \Stringable) return (string)$v;
            $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return is_string($j) ? $j : '';
        }

        return '';
    }

    private function shortKw(): string
    {
        $kw = trim((string)$this->keyword);
        if ($kw === '') return 'tu proyecto';
        return mb_substr($kw, 0, 70);
    }

    private function keepAllowedInlineHtml(string $html): string
    {
        $clean = strip_tags($html, '<p><strong><br>');
        $clean = preg_replace('~\s+~u', ' ', $clean);
        $clean = str_replace(['</p> <p>','</p><p>'], '</p><p>', $clean);
        return trim((string)$clean);
    }

    private function copyTextFromDraftJson(string $draftJson): string
    {
        $draftJson = trim((string)$draftJson);
        if ($draftJson === '') return '';
        $arr = json_decode($draftJson, true);
        if (!is_array($arr)) return '';
        // soporta el formato nuevo (tokens)
        if (isset($arr['tokens']) && is_array($arr['tokens'])) {
            $parts = [];
            foreach ($arr['tokens'] as $v) $parts[] = strip_tags($this->toStr($v));
            $txt = implode(' ', array_filter($parts));
            $txt = preg_replace('~\s+~u', ' ', $txt);
            return trim($txt);
        }
        return '';
    }
}
