<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Dominios_Contenido_DetallesModel;
use Illuminate\Support\Str;

class GenerarContenidoKeywordJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $timeout = 300;   // 5 min (ajusta según tu caso)
    public $tries = 1;       // para que no se duplique mientras pruebas
    public function __construct(
        public string $idDominio,
        public string $idDominioContenido,
        public string $tipo,
        public string $keyword
    ) {}

    public function handle(): void
    {
        // ✅ SIEMPRE genera un registro nuevo (historial)
        $registro = Dominios_Contenido_DetallesModel::create([
            // NO enviar id_dominio_contenido_detalle (AUTO_INCREMENT)
            'id_dominio_contenido' => (int)$this->idDominioContenido,
            'id_dominio' => (int)$this->idDominio,
            'tipo' => $this->tipo,
            'keyword' => $this->keyword,
            'estatus' => 'en_proceso',
            'modelo' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        ]);

        try {
            $apiKey = env('DEEPSEEK_API_KEY');
            $model  = env('DEEPSEEK_MODEL', 'deepseek-chat');

            // ✅ Títulos anteriores para NO repetir (limitado para no inflar el prompt)
            $existentes = Dominios_Contenido_DetallesModel::where('id_dominio_contenido', (int)$this->idDominioContenido)
                ->whereNotNull('title')
                ->orderByDesc('id_dominio_contenido_detalle')
                ->limit(12)
                ->pluck('title')
                ->toArray();

            $noRepetir = implode(' | ', array_filter($existentes));

            // 1) Redactor -> HTML borrador
            $draftPrompt = $this->promptRedactor($this->tipo, $this->keyword, $noRepetir);
            $draftHtml   = $this->deepseekText($apiKey, $model, $draftPrompt);

            // 2) Auditor -> HTML final mejorado
            $auditPrompt = $this->promptAuditorHtml($this->tipo, $this->keyword, $draftHtml, $noRepetir);
            $finalHtml   = $this->deepseekText($apiKey, $model, $auditPrompt);

            // Title desde H1
            $title = null;
            if (preg_match('~<h1[^>]*>(.*?)</h1>~is', $finalHtml, $m)) {
                $title = trim(strip_tags($m[1]));
            }

            // Slug único (si el title se repite igual, al menos el slug no choca)
            $slugBase = $title ? Str::slug($title) : Str::slug($this->keyword);
            $slug = $slugBase . '-' . $registro->id_dominio_contenido_detalle;

            // Actualiza el registro con resultados
            $registro->update([
                'title' => $title,
                'slug' => $slug,
                'contenido_html' => $finalHtml,
                'draft_html' => $draftHtml,
                'estatus' => 'generado',
                'error' => null,
            ]);

        } catch (\Throwable $e) {
            $registro->update([
                'estatus' => 'error',
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Llamada a DeepSeek (OpenAI-compatible) y extracción de texto.
     */
    private function deepseekText(string $apiKey, string $model, string $prompt): string
    {
        $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(15)
            ->timeout(150)
            ->retry(1, 700)
            ->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                // opcional:
                // 'temperature' => 0.8,
            ]);

        if (!$resp->successful()) {
            throw new \RuntimeException("DeepSeek error {$resp->status()}: {$resp->body()}");
        }

        $data = $resp->json();

        $text = trim((string)($data['choices'][0]['message']['content'] ?? ''));

        if ($text === '') {
            throw new \RuntimeException("DeepSeek returned empty text.");
        }

        return $text;
    }

    /**
     * Redactor: genera un borrador HTML (sin repetir títulos previos).
     */
    private function promptRedactor(string $tipo, string $keyword, string $noRepetir): string
    {
        $base = "Devuelve SOLO HTML válido listo para WordPress.
NO incluyas <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>.
NO uses markdown. NO expliques nada.
NO uses headings: Introducción, Conclusión, ¿Qué es...?
NO uses casos de éxito ni testimonios.
NO uses el texto 'guía práctica' ni variantes.

Títulos ya usados (NO repetir ni hacer muy similares):
{$noRepetir}

REGLA CLAVE:
Aunque la keyword sea la misma, crea una versión totalmente distinta:
- título diferente
- H2/H3 diferentes
- orden distinto
- ejemplos/argumentos diferentes
- evita frases tipo: 'en este artículo veremos...' y definiciones de diccionario.";

        if ($tipo === 'post') {
            return "{$base}

Crea un POST SEO en español sobre: {$keyword}

Estructura mínima:
- 1 <h1> (único y atractivo)
- 6 a 9 <h2> (no genéricos)
- varios <h3> cuando aporte profundidad
- Listas <ul><li> cuando aplique
- FAQ (2-5)
- CTA final en <p><strong>...</strong></p>

Devuelve SOLO HTML.";
        }

        // page/landing
        return "{$base}

Crea una PÁGINA/LANDING SEO en español para: {$keyword}

Estructura mínima:
- 1 <h1> (único y potente)
- 8 a 12 <h2> orientados a conversión (beneficios, servicios, proceso, objeciones, FAQ, CTA)
- CTA al inicio, mitad y final
- FAQ (3-6)
- Usa <div> si ayuda a maquetar (sin CSS, solo estructura)

Devuelve SOLO HTML.";
    }

    /**
     * Auditor: mejora el borrador y devuelve HTML final.
     */
    private function promptAuditorHtml(string $tipo, string $keyword, string $draftHtml, string $noRepetir): string
    {
        return "Eres un consultor SEO senior. Tu tarea es AUDITAR y MEJORAR el contenido entregado y devolver UNA VERSIÓN FINAL.

Devuelve SOLO HTML válido listo para WordPress.
NO incluyas <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>.
NO uses markdown.
NO expliques nada.
NO uses headings: Introducción, Conclusión, ¿Qué es...?
NO uses casos de éxito ni testimonios.
NO uses 'guía práctica' ni variantes.

Títulos ya usados (NO repetir ni hacer muy similares):
{$noRepetir}

Objetivo:
- Mejorar intención de búsqueda, profundidad semántica y estructura
- Reducir relleno y repetición
- H2/H3 más específicos (menos genéricos)
- Mejorar gancho inicial
- Añadir FAQ si falta o mejorarla
- CTA final breve y profesional

Tipo: {$tipo}
Keyword principal: {$keyword}

HTML A MEJORAR (reescribe y devuelve el HTML final):
{$draftHtml}";
    }
}
