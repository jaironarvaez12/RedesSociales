<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    public $timeout = 4200;
    public $tries   = 5;
    public $backoff = [60, 120, 300, 600, 900];

    public string $jobUuid;

    public function __construct(
        public string $idDominio,
        public string $idDominioContenido,
        public string $tipo,
        public string $keyword
    ) {
        // ✅ DEDUPE: 1 registro por (dominio_contenido + dominio + tipo + keyword)
        $this->jobUuid = sha1(
            (int)$this->idDominio . '|' .
            (int)$this->idDominioContenido . '|' .
            trim(mb_strtolower($this->keyword)) . '|' .
            trim(mb_strtolower($this->tipo))
        );
    }

    public function handle(): void
    {
        $registro = null;

        try {
            // ===========================================================
            // 1) Crear/recuperar registro (IDEMPOTENTE y seguro en concurrencia)
            // ===========================================================
            $registro = $this->getOrCreateRegistro();

            // ===========================================================
            // 2) DeepSeek config
            // ===========================================================
            $apiKey = (string) env('DEEPSEEK_API_KEY', '');
            $model  = (string) env('DEEPSEEK_MODEL', 'deepseek-chat');

            if ($apiKey === '') {
                throw new \RuntimeException('DEEPSEEK_API_KEY no configurado');
            }

            // ===========================================================
            // 3) Historial (para evitar repetición)
            // ===========================================================
            $prev = Dominios_Contenido_DetallesModel::where('id_dominio_contenido', (int)$this->idDominioContenido)
                ->whereNotNull('draft_html')
                ->orderByDesc('id_dominio_contenido_detalle')
                ->limit(8)
                ->get(['title', 'draft_html']);

            $usedTitles = [];
            $usedCorpus = [];

            foreach ($prev as $row) {
                if (!empty($row->title)) $usedTitles[] = (string)$row->title;
                $usedCorpus[] = $this->copyTextFromDraftJson((string)$row->draft_html);
            }

            $noRepetirTitles = implode(' | ', array_slice(array_filter($usedTitles), 0, 12));
            $noRepetirCorpus = $this->compactHistory($usedCorpus, 2500);

            // ===========================================================
            // 4) Cargar template + map (si existe)
            // ===========================================================
            [$tpl, $tplPath] = $this->loadElementorTemplateForDomainWithPath((int)$this->idDominio);

            $map = $this->loadTokenMapIfExists($tplPath); // puede ser null

            // ===========================================================
            // 5) Generar copy para tokens (UNIVERSAL)
            // ===========================================================
            $copy = $this->generateCopyForTemplateTokens(
                $apiKey,
                $model,
                $map,
                $tpl,
                $noRepetirTitles,
                $noRepetirCorpus
            );

            // ===========================================================
            // 6) Reemplazar tokens
            // ===========================================================
            [$filled, $replacedCount, $remaining] = $this->fillTemplateByTokensWithStats($tpl, $copy);

            if ($replacedCount < 8) {
                throw new \RuntimeException("Template no parece tokenizado (replacedCount={$replacedCount}). Template: {$tplPath}");
            }

            if (!empty($remaining)) {
                throw new \RuntimeException("Quedaron tokens sin reemplazar: " . implode(' | ', array_slice($remaining, 0, 50)));
            }

            // ===========================================================
            // 7) Title + slug (seo_title o keyword)
            // ===========================================================
            $title = trim(strip_tags((string)($copy['seo_title'] ?? $this->keyword)));
            if ($title === '') $title = $this->keyword;

            $slugBase = Str::slug($title ?: $this->keyword);
            $slug = $slugBase . '-' . $registro->id_dominio_contenido_detalle;

            // ===========================================================
            // 8) Guardar en BD
            // ===========================================================
            $registro->update([
                'title' => $title,
                'slug' => $slug,
                'draft_html' => json_encode($copy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'contenido_html' => json_encode($filled, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'estatus' => 'generado',
                'error' => null,
            ]);

        } catch (\Throwable $e) {
            if ($registro) {
                $isLast = ($this->attempts() >= (int)$this->tries);
                $registro->update([
                    'estatus' => $isLast ? 'error_final' : 'error',
                    'error' => $e->getMessage() . ' | attempts=' . $this->attempts(),
                ]);
            }

            throw $e;
        }
    }

    // ===========================================================
    // Registro idempotente (1 solo registro por job_uuid)
    // ===========================================================
    private function getOrCreateRegistro(): Dominios_Contenido_DetallesModel
    {
        $existing = Dominios_Contenido_DetallesModel::where('job_uuid', $this->jobUuid)->first();
        if ($existing) {
            $existing->update([
                'estatus' => 'en_proceso',
                'modelo' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            ]);
            return $existing;
        }

        try {
            return Dominios_Contenido_DetallesModel::create([
                'job_uuid' => $this->jobUuid,
                'id_dominio_contenido' => (int)$this->idDominioContenido,
                'id_dominio' => (int)$this->idDominio,
                'tipo' => $this->tipo,
                'keyword' => $this->keyword,
                'estatus' => 'en_proceso',
                'modelo' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
            ]);
        } catch (\Throwable $e) {
            // Si hubo condición de carrera (2 workers), recupera el existente
            $again = Dominios_Contenido_DetallesModel::where('job_uuid', $this->jobUuid)->first();
            if ($again) return $again;
            throw $e;
        }
    }

    // ===========================================================
    // Load Elementor template
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
    // Cargar map si existe: template.json => template.map.json
    // ===========================================================
    private function loadTokenMapIfExists(string $templatePath): ?array
    {
        $mapPath = preg_replace('~\.json$~i', '.map.json', $templatePath);
        if (!$mapPath || !is_file($mapPath)) return null;

        $raw = (string) file_get_contents($mapPath);
        $arr = json_decode($raw, true);

        return is_array($arr) ? $arr : null;
    }

    // ===========================================================
    // Generar copy UNIVERSAL para cualquier template tokenizado
    // - Si hay map: genera por token según widgetType y original.
    // - Si NO hay map: genera fallback genérico por tokens detectados.
    // ===========================================================
    private function generateCopyForTemplateTokens(
        string $apiKey,
        string $model,
        ?array $map,
        array $tpl,
        string $noRepetirTitles,
        string $noRepetirCorpus
    ): array {
        // 1) Tokens presentes en template
        $tokensInTpl = $this->collectRemainingTokensDeep($tpl);

        // 2) Blueprint (token -> tipo + original)
        $blueprint = [];

        if (is_array($map)) {
            foreach ($map as $item) {
                if (!is_array($item)) continue;
                $tok = (string)($item['token'] ?? '');
                if ($tok === '' || !in_array($tok, $tokensInTpl, true)) continue;

                $blueprint[] = [
                    'token' => $tok,
                    'widgetType' => (string)($item['widgetType'] ?? 'unknown'),
                    'original' => (string)($item['original'] ?? ''),
                ];
            }
        }

        // 3) Si map no cubre todo, añade tokens faltantes como unknown
        $bpTokens = array_map(fn($x) => $x['token'], $blueprint);
        foreach ($tokensInTpl as $tok) {
            if (!in_array($tok, $bpTokens, true)) {
                $blueprint[] = ['token' => $tok, 'widgetType' => 'unknown', 'original' => ''];
            }
        }

        // 4) Prompt (1 llamada + 1 repair máximo)
        $prompt = $this->promptUniversalTokenCopy(
            $this->keyword,
            $this->tipo,
            $blueprint,
            $noRepetirTitles,
            $noRepetirCorpus
        );

        $raw = $this->deepseekText($apiKey, $model, $prompt, maxTokens: 3600, temperature: 0.75, topP: 0.90, jsonMode: true);

        $copy = $this->safeParseJsonObject($apiKey, $model, $raw, $prompt);

        // 5) Validación mínima + fallbacks por token
        $out = [];
        $out['seo_title'] = $this->sanitizeSeoTitle((string)($copy['seo_title'] ?? $this->keyword), $this->keyword);

        foreach ($blueprint as $b) {
            $tok = $b['token'];
            $type = $b['widgetType'];

            $val = isset($copy[$tok]) ? (string)$copy[$tok] : '';

            if ($type === 'text-editor') {
                $val = $this->keepAllowedInlineHtml($val);
                if ($this->isBlankHtml($val)) {
                    $val = "<p>" . htmlspecialchars($this->keyword, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . " — contenido listo para publicar.</p>";
                }
            } elseif ($type === 'button') {
                $val = trim(strip_tags($val));
                if ($val === '') $val = $this->pick(["Ver más","Saber más","Empezar","Contactar"]);
                if (mb_strlen($val) > 22) $val = mb_substr($val, 0, 22);
            } elseif ($type === 'icon-list') {
                $val = trim(strip_tags($val));
                if ($val === '') $val = $this->pick(["Rápido","Claro","Optimizado","Listo para publicar"]);
                if (mb_strlen($val) > 55) $val = mb_substr($val, 0, 55);
            } elseif ($type === 'heading') {
                $val = $this->keepAllowedInlineStrong($val);
                $val = trim($val);
                if ($val === '') $val = "Sección para " . mb_substr($this->keyword, 0, 50);
                if (mb_strlen(strip_tags($val)) > 70) {
                    $val = mb_substr(strip_tags($val), 0, 70);
                }
            } else {
                // unknown: decide por prefijo
                if (str_starts_with($tok, '{{BTN_')) {
                    $val = trim(strip_tags($val));
                    if ($val === '') $val = $this->pick(["Ver más","Saber más","Empezar","Contactar"]);
                } elseif (str_starts_with($tok, '{{P_')) {
                    $val = $this->keepAllowedInlineHtml($val);
                    if ($this->isBlankHtml($val)) $val = "<p>Contenido útil y claro sobre {$this->keyword}.</p>";
                } else {
                    $val = $this->keepAllowedInlineStrong($val);
                    if (trim(strip_tags($val)) === '') $val = "Contenido listo para " . mb_substr($this->keyword, 0, 50);
                }
            }

            $out[$tok] = $val;
        }

        return $out;
    }

    private function promptUniversalTokenCopy(
        string $keyword,
        string $tipo,
        array $blueprint,
        string $noRepetirTitles,
        string $noRepetirCorpus
    ): string {
        $bpShort = mb_substr(json_encode($blueprint, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 0, 9500);

        return <<<PROMPT
Devuelve SOLO JSON válido (sin markdown, sin explicación). RESPUESTA MINIFICADA (sin saltos de línea).

Objetivo: Reemplazar TODOS los tokens del template por textos nuevos y variados, útiles para cualquier tipo de plantilla.

Keyword: {$keyword}
Tipo: {$tipo}

NO repetir títulos:
{$noRepetirTitles}

NO repetir frases/subtemas:
{$noRepetirCorpus}

REGLAS:
- Debes devolver "seo_title" (60–65 chars aprox).
- Para cada token del blueprint, devuelve una key EXACTA igual al token (ej "{{H_01}}") con su valor.
- heading: texto corto, puede usar <strong>, NO uses <h1>/<h2>.
- text-editor: HTML permitido SOLO <p>, <strong>, <br>. Debe ser útil, sin relleno, sin promesas falsas.
- button: 2–4 palabras, sin HTML.
- icon-list: muy corto, sin HTML.
- Evita keyword stuffing: usa variaciones semánticas, pero mantén relevancia.
- Nada vacío. No uses "<p></p>".

BLUEPRINT (token, widgetType, original):
{$bpShort}

Formato de respuesta:
{"seo_title":"...","{{H_01}}":"...","{{P_01}}":"<p>...</p>","{{BTN_01}}":"..."}
PROMPT;
    }

    // ===========================================================
    // Reemplazo tokens + stats
    // ===========================================================
    private function fillTemplateByTokensWithStats(array $tpl, array $copy): array
    {
        $dict = $this->buildTokenDictionary($copy);

        $replacedCount = 0;
        $this->replaceTokensDeep($tpl, $dict, $replacedCount);

        $remaining = $this->collectRemainingTokensDeep($tpl);

        return [$tpl, $replacedCount, $remaining];
    }

    private function buildTokenDictionary(array $copy): array
    {
        $dict = [];

        foreach ($copy as $k => $v) {
            if ($k === 'seo_title') continue;
            if (is_string($k) && str_starts_with($k, '{{') && str_ends_with($k, '}}')) {
                $dict[$k] = (string)$v;
            }
        }

        // fallback por si hay tokens estándar (si en alguna plantilla los usas)
        $seo = (string)($copy['seo_title'] ?? $this->keyword);
        $dict['{{SEO_TITLE}}'] = $seo;

        return $dict;
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
            'presence_penalty' => 1.0,
            'frequency_penalty' => 0.5,
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

    private function safeParseJsonObject(string $apiKey, string $model, string $raw, string $promptForRepair): array
    {
        try {
            return $this->parseJsonStrict($raw);
        } catch (\Throwable $e) {
            // 1 repair
            $repair = <<<PROMPT
Devuelve SOLO JSON válido. Sin markdown. RESPUESTA MINIFICADA.
Repara este output para que sea un JSON objeto válido.
OUTPUT ROTO:
{$raw}
PROMPT;

            $fixed = $this->deepseekText($apiKey, $model, $repair, maxTokens: 2000, temperature: 0.10, topP: 0.90, jsonMode: true);

            return $this->parseJsonStrict($fixed);
        }
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
            throw new \RuntimeException('DeepSeek no devolvió JSON válido. Snippet: ' . $snip);
        }
        return $data;
    }

    // ===========================================================
    // Utils
    // ===========================================================
    private function compactHistory(array $corpusArr, int $maxChars = 2500): string
    {
        $chunks = [];
        foreach ($corpusArr as $t) {
            $t = trim((string)$t);
            if ($t === '') continue;

            $t = mb_substr($t, 0, 380);
            $chunks[] = $t;

            $joined = implode("\n---\n", $chunks);
            if (mb_strlen($joined) >= $maxChars) break;
        }

        $out = trim(implode("\n---\n", $chunks));
        if (mb_strlen($out) > $maxChars) $out = mb_substr($out, 0, $maxChars);
        return $out;
    }

    private function copyTextFromDraftJson(string $draftJson): string
    {
        $draftJson = trim((string)$draftJson);
        if ($draftJson === '') return '';
        $arr = json_decode($draftJson, true);
        if (!is_array($arr)) return '';

        $parts = [];
        foreach ($arr as $k => $v) {
            if ($k === 'seo_title') $parts[] = strip_tags((string)$v);
            if (is_string($k) && str_starts_with($k, '{{')) {
                $parts[] = strip_tags((string)$v);
            }
        }
        $txt = implode(' ', array_filter($parts));
        $txt = preg_replace('~\s+~u', ' ', $txt);
        return trim((string)$txt);
    }

    private function pick(array $arr): string
    {
        return $arr[random_int(0, count($arr) - 1)];
    }

    private function isBlankHtml(string $html): bool
    {
        $txt = trim(preg_replace('~\s+~u', ' ', strip_tags($html)));
        return $txt === '';
    }

    private function keepAllowedInlineHtml(string $html): string
    {
        $clean = strip_tags((string)$html, '<p><strong><br>');
        $clean = preg_replace('~\s+~u', ' ', $clean);
        $clean = str_replace(['</p> <p>','</p><p>'], '</p><p>', $clean);
        $clean = trim((string)$clean);

        if ($clean !== '' && !preg_match('~^\s*<p>~i', $clean)) {
            $clean = '<p>' . $clean . '</p>';
        }
        return $clean;
    }

    private function keepAllowedInlineStrong(string $html): string
    {
        $clean = strip_tags((string)$html, '<strong><br>');
        $clean = preg_replace('~\s+~u', ' ', $clean);
        return trim((string)$clean);
    }

    private function sanitizeSeoTitle(string $seo, string $fallbackKw): string
    {
        $seo = trim(strip_tags((string)$seo));
        if ($seo === '') $seo = trim($fallbackKw);
        if (mb_strlen($seo) > 65) $seo = rtrim(mb_substr($seo, 0, 65), " \t\n\r\0\x0B-–—|:");
        return $seo;
    }
}
