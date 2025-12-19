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

    // ✅ En prod 2 llamadas + red puede pasar 5 min. Sube.
    public $timeout = 900;
    public $tries = 1;

    public function __construct(
        public string $idDominio,
        public string $idDominioContenido,
        public string $tipo,
        public string $keyword
    ) {}

    public function handle(): void
    {
        $registro = Dominios_Contenido_DetallesModel::create([
            'id_dominio_contenido' => (int)$this->idDominioContenido,
            'id_dominio' => (int)$this->idDominio,
            'tipo' => $this->tipo,
            'keyword' => $this->keyword,
            'estatus' => 'en_proceso',
            'modelo' => env('DEEPSEEK_MODEL', 'deepseek-chat'),
        ]);

        try {
            $apiKey = (string) env('DEEPSEEK_API_KEY', '');
            $model  = (string) env('DEEPSEEK_MODEL', 'deepseek-chat');

            if ($apiKey === '') {
                throw new \RuntimeException('DEEPSEEK_API_KEY no configurado');
            }

            $existentes = Dominios_Contenido_DetallesModel::where('id_dominio_contenido', (int)$this->idDominioContenido)
                ->whereNotNull('title')
                ->orderByDesc('id_dominio_contenido_detalle')
                ->limit(12)
                ->pluck('title')
                ->toArray();

            $noRepetir = implode(' | ', array_filter($existentes));

            // 1) Redactor: genera ya con estructura Nictorys
            $draftPrompt = $this->promptRedactor($this->tipo, $this->keyword, $noRepetir);
            $draftHtml   = $this->deepseekText($apiKey, $model, $draftPrompt, maxTokens: 4500);

            $draftHtml = $this->ensureNictorysWrappers($draftHtml);

            // 2) Auditor SOLO si hace falta (ahorra tiempo y evita kill)
            $finalHtml = $draftHtml;

            if (!$this->looksLikeNictorys($draftHtml)) {
                $draftShort  = mb_substr($draftHtml, 0, 12000);
                $auditPrompt = $this->promptAuditorHtml($this->tipo, $this->keyword, $draftShort, $noRepetir);
                $finalHtml   = $this->deepseekText($apiKey, $model, $auditPrompt, maxTokens: 4500);
                $finalHtml   = $this->ensureNictorysWrappers($finalHtml);
            }

            // 3) Repair pass si todavía no cumple
            if (!$this->looksLikeNictorys($finalHtml)) {
                $repairPrompt = $this->promptRepairNictorys($this->keyword, mb_substr($finalHtml, 0, 14000));
                $finalHtml = $this->deepseekText($apiKey, $model, $repairPrompt, maxTokens: 4500);
                $finalHtml = $this->ensureNictorysWrappers($finalHtml);
            }

            // Title desde H1
            $title = null;
            if (preg_match('~<h1[^>]*>(.*?)</h1>~is', $finalHtml, $m)) {
                $title = trim(strip_tags($m[1]));
            }

            $slugBase = $title ? Str::slug($title) : Str::slug($this->keyword);
            $slug = $slugBase . '-' . $registro->id_dominio_contenido_detalle;

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
     * DeepSeek (OpenAI compatible)
     */
    private function deepseekText(string $apiKey, string $model, string $prompt, int $maxTokens = 3500): string
    {
        $resp = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->connectTimeout(15)
            ->timeout(160)     // no lo dejes gigante
            ->retry(0, 0)      // en generación es mejor no alargar con retries
            ->post('https://api.deepseek.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.7,
                'max_tokens'  => $maxTokens,
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
     * Fuerza wrappers necesarios para que el CSS del template funcione mejor:
     * - <div class="nictorys-content">
     * - <div class="page-wrapper">
     */
    private function ensureNictorysWrappers(string $html): string
    {
        $html = trim($html);

        if (!str_contains($html, 'nictorys-content')) {
            $html = '<div class="nictorys-content">' . $html . '</div>';
        }

        // si ya trae page-wrapper, no lo dupliques
        if (!str_contains($html, 'page-wrapper')) {
            // mete page-wrapper justo dentro del nictorys-content
            $html = preg_replace(
                '~<div class="nictorys-content">\s*~i',
                '<div class="nictorys-content"><div class="page-wrapper">',
                $html,
                1
            );
            // cierra page-wrapper antes del cierre final
            $html = preg_replace(
                '~</div>\s*$~',
                '</div></div>',
                $html,
                1
            );
        }

        return $html;
    }

    private function looksLikeNictorys(string $html): bool
    {
        if ($html === '') return false;

        // Must-have sections (mínimo)
        $must = [
            'nictorys-content',
            'hero-slider hero-style-2',
            'features-section-s2',
            'about-us-section-s2',
            'services-section-s2',
            'contact-section',
            'cta-section-s2',
            'latest-projects-section-s2',
            'why-choose-us-section',
            'team-section',
            'testimonials-section',
            'blog-section',
        ];

        foreach ($must as $n) {
            if (!str_contains($html, $n)) return false;
        }

        // 1 solo h1
        preg_match_all('~<h1\b~i', $html, $m);
        if (count($m[0] ?? []) !== 1) return false;

        // no scripts/styles/links
        if (preg_match('~<(script|style|link)\b~i', $html)) return false;

        return true;
    }

    private function promptRedactor(string $tipo, string $keyword, string $noRepetir): string
    {
        $base = "Devuelve SOLO HTML válido.
NO incluyas <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>.
NO uses markdown. NO expliques nada.
NO uses headings: Introducción, Conclusión, ¿Qué es...?
NO uses casos de éxito ni testimonios.
NO uses el texto 'guía práctica' ni variantes.
NO uses Lorem ipsum.

Títulos ya usados (NO repetir ni hacer muy similares):
{$noRepetir}

REGLA CLAVE:
Aunque la keyword sea la misma, crea una versión totalmente distinta:
- título diferente
- orden distinto
- argumentos y ejemplos distintos
- evita frases tipo: 'en este artículo veremos...'.";

        return "{$base}

Keyword objetivo: {$keyword}
Tipo: {$tipo}

" . $this->nictorysContract() . "

INSTRUCCIONES EXTRA:
- El <h1> debe ir dentro del HERO (hero-slider).
- El HTML final debe incluir además: <div class=\"page-wrapper\"> dentro de nictorys-content (si no lo pones, igual lo forzaremos).
- Enlaces siempre href=\"#\".
- Imágenes siempre assets/images/... (WordPress plugin reescribe).

Devuelve SOLO el HTML.";
    }

    /**
     * Auditor corto (NO repite el contrato completo para no inflar contexto)
     */
    private function promptAuditorHtml(string $tipo, string $keyword, string $draftHtml, string $noRepetir): string
    {
        return "Eres un consultor SEO senior. Reescribe el HTML para mejor conversión y claridad, manteniendo EXACTAMENTE la maqueta Nictorys.

Devuelve SOLO HTML.
No incluyas <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>.
No uses markdown. No expliques nada.
No headings genéricos: Introducción, Conclusión, ¿Qué es...?
No casos de éxito ni testimonios reales.
No 'guía práctica' ni variantes.
No <script>, <style>, <link>, header, footer.

Títulos ya usados (NO repetir ni hacer muy similares):
{$noRepetir}

REGLAS OBLIGATORIAS:
- Debe estar envuelto en <div class=\"nictorys-content\"><div class=\"page-wrapper\"> ... </div></div>
- Debe contener estas secciones en orden:
  hero-slider hero-style-2 (1 solo <h1> dentro del hero)
  features-section-s2
  about-us-section-s2
  services-section-s2 (id services)
  contact-section (id contact)
  cta-section-s2
  latest-projects-section-s2
  why-choose-us-section
  team-section
  testimonials-section (como Garantías/Compromisos, sin testimonios)
  blog-section
- Imágenes: assets/images/...
- Enlaces: href=\"#\"

Keyword: {$keyword}
Tipo: {$tipo}

HTML a mejorar:
{$draftHtml}";
    }

    private function promptRepairNictorys(string $keyword, string $html): string
    {
        return "Convierte este HTML a la plantilla Nictorys obligatoria.

Devuelve SOLO HTML.
Debe iniciar con:
<div class=\"nictorys-content\"><div class=\"page-wrapper\">
y cerrar ambos div al final.
No incluyas scripts/styles/links ni header/footer.

Debe incluir estas secciones EN ORDEN:
hero-slider hero-style-2 (con 1 solo <h1>)
features-section-s2
about-us-section-s2
services-section-s2 (id services)
contact-section (id contact)
cta-section-s2
latest-projects-section-s2
why-choose-us-section
team-section
testimonials-section (Garantías)
blog-section

Keyword: {$keyword}

HTML:
{$html}";
    }

    private function nictorysContract(): string
    {
        return <<<TXT
DEVUELVE SOLO HTML (contenido). NO incluyas <!DOCTYPE>, <html>, <head>, <body>, <script>, <link>, <style>, header, footer.

OBLIGATORIO: envuelve TODO en:
<div class="nictorys-content"><div class="page-wrapper"> ... </div></div>

Usa EXACTAMENTE estas secciones y clases (en este orden):
1) <section class="hero-slider hero-style-2"> ... (AQUÍ va el ÚNICO <h1>)
   - Debe incluir .swiper-container, .swiper-wrapper, .swiper-slide
   - Dentro: .slide-inner.slide-bg-image con data-background="assets/images/slider/slide-1.jpg"
   - Incluye 2 CTAs con clases theme-btn y theme-btn-s2

2) <section class="features-section-s2"> ... (4 features .grid)

3) <section class="about-us-section-s2 section-padding p-t-0"> ...
   - Incluye .img-holder y .about-details + <ul><li>...

4) <section class="services-section-s2 section-padding" id="services"> ...
   - 6 cards .grid con .img-holder + .details + icon <i class="fi ..."></i>

5) <section class="contact-section section-padding" id="contact"> ...
   - Form similar (inputs + textarea) action="#"

6) <section class="cta-section-s2"> ... (CTA fuerte)

7) <section class="latest-projects-section-s2 section-padding"> ...
   - 6 items .grid con imagen + título

8) <section class="why-choose-us-section section-padding p-t-0"> ...
   - skills con progress-bar data-percent

9) <section class="team-section section-padding p-t-0"> ... (4 miembros)

10) <section class="testimonials-section section-padding"> ...
   - NO testimonios reales. Úsalo como Garantías/Compromisos (2 bloques)

11) <section class="blog-section section-padding"> ...
   - 3 entradas sugeridas

REGLAS:
- Español orientado a conversión.
- No headings genéricos: “Introducción”, “Conclusión”, “¿Qué es…?”
- No “guía práctica” ni variantes.
- Enlaces href="#".
- Imágenes assets/images/...
TXT;
    }
}
