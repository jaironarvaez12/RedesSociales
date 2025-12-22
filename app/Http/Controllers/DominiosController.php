<?php

namespace App\Http\Controllers;

use App\Models\Dominios_ContenidoModel;
use App\Models\Dominios_Contenido_DetallesModel;
use App\Models\DominiosModel;
use Illuminate\Http\Request;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use App\Services\WordpressService;
use Illuminate\Support\Facades\Http;
use App\Jobs\GenerarContenidoKeywordJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
class DominiosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        
  
        $dominios = DominiosModel::all();
        return view('Dominios.Dominio',compact('dominios'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
          return view('Dominios.DominioCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //dd($request);
         $IdDominio= DominiosModel::max('id_dominio')+1;
        try 
         {
             DB::transaction(function () use ($request, $IdDominio)
            {
                  
                DominiosModel::create([
                    'id_dominio' =>    $IdDominio,
                    'url' =>    $request['url'],
                    'nombre' =>strtoupper($request['nombre']),
                    'estatus' =>strtoupper('SI'),
                      'usuario' => $request['usuario'],
                      'password'=> Crypt::encryptString($request->input('password'))
                ]);
            });
 
         } 
         catch (Exception $ex) 
         {
             return back()->withError('Ocurrio Un Error al Crear el Dominio ' . $ex->getMessage())->withInput();
         }
          return redirect("dominios")->withSuccess('El Dominio Se Ha Creado Exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $IdDominio)
    {
           $dominio = DominiosModel::find($IdDominio);
       $generadores=Dominios_ContenidoModel::all()->where('id_dominio','=',$IdDominio);
       return view('Dominios.DominioShow',compact('dominio','generadores'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $IdDominio)
    {
       $dominio = DominiosModel::find($IdDominio);
       
       return view('Dominios.DominioEdit',compact('dominio'));
    }

    /**
     * Update the specified resource in storage.
     */
   public function update(Request $request, string $id)
    {
        try {
            $dominios = DominiosModel::findOrFail($id);

            // (Opcional pero recomendado)
            $request->validate([
                'usuario' => ['nullable','string','max:255'],
                'password' => ['nullable','string','max:255'],
                'elementor_template_path' => ['nullable','string','max:255'],
            ]);

            $dominios->fill([
                'usuario' => $request->input('usuario'),
                'elementor_template_path' => $request->input('elementor_template_path'), // 👈 NUEVO
            ]);

            if ($request->filled('password')) {
                $dominios->password = Crypt::encryptString($request->input('password'));
            }

            $dominios->save();

        } catch (\Throwable $ex) {
            return redirect()->back()
                ->withError('Ha Ocurrido Un Error Al Actualizar El Dominio ' . $ex->getMessage())
                ->withInput();
        }

        return redirect()->route('dominios.edit', $id)->withSuccess('El Dominio Se Ha Actualizado Exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
public function Crearcontenido(string $IdDominio)
{
    // Tipos permitidos
    $tiposPermitidos = ['POST', 'PAGINAS'];

    // Tipos que ya existen para ese dominio
    $tiposExistentes = Dominios_ContenidoModel::where('id_dominio', $IdDominio)
        ->pluck('tipo')            // trae solo la columna tipo
        ->map(fn($t) => strtoupper(trim($t)))
        ->toArray();

    // Tipos faltantes (los que NO tiene aún)
    $tiposDisponibles = array_values(array_diff($tiposPermitidos, $tiposExistentes));

    return view('Dominios.DominioCrearContenido', compact('IdDominio', 'tiposDisponibles'));
}
    public function GeneradorContenido(Request $request,string  $IdDominio)
    {
        $palabras = json_decode($request['palabras_clave']); // ["seo","paginas"]
        if($palabras  == NULL) //Valida que el arreglo de las herramientas no este vacio
        {
            return back()->withErrors(['palabras_clave'=> 'Para  crea un tipo de generador debe seleccionar una o varias palabras clave'])->withInput();
        }
        $tipo=$request['tipo'];
        if($tipo  == 0) //Valida que el arreglo de las herramientas no este vacio
        {
            return back()->withErrors(['Este Dominio ya tiene ambos tipos de generadores de contenido'])->withInput();
        }
        $palabras_clave_cadena = implode(',', $palabras);   // "seo, paginas"
 
         try 
         {
           
                $IdDominioContenido= Dominios_ContenidoModel::max('id_dominio_contenido')+1;
                Dominios_ContenidoModel::create([
                    'id_dominio_contenido' =>    $IdDominioContenido,
                    'id_dominio' =>    $IdDominio,
                    'tipo' =>    $tipo,
                    'palabras_claves' => $palabras_clave_cadena,
                    'estatus' =>strtoupper('SI'),
                  
                ]);
            
 
         } 
         catch (Exception $ex) 
         {
             return back()->withError('Ocurrio Un Error al Crear el Generador de Contenido ' . $ex->getMessage())->withInput();
         }
          return redirect("dominios")->withSuccess('El Generador Contenido Se Ha Creado Exitosamente');


        
    }
public function verWp($id, WordpressService $wp)
{
    $dominio = DominiosModel::findOrFail($id);

    $site = rtrim((string)$dominio->url, '/');
    $siteKey = md5($site);

    // Raw snapshots (pueden ser [] si aún no llega nada)
    $postsRaw = Cache::get("inv:{$siteKey}:post", []);
    $pagesRaw = Cache::get("inv:{$siteKey}:page", []);

    $postsRaw = is_array($postsRaw) ? $postsRaw : [];
    $pagesRaw = is_array($pagesRaw) ? $pagesRaw : [];

    // Meta para mostrar "sincronizando / parcial"
    $metaPosts = Cache::get("inv_meta:{$siteKey}:post", []);
    $metaPages = Cache::get("inv_meta:{$siteKey}:page", []);

    $metaPosts = is_array($metaPosts) ? $metaPosts : [];
    $metaPages = is_array($metaPages) ? $metaPages : [];

    $syncPosts = [
        'has_data'    => !empty($postsRaw),
        'complete'    => (bool)($metaPosts['is_complete'] ?? false),
        'updated_at'  => $metaPosts['updated_at'] ?? null,
        'run_id'      => $metaPosts['run_id'] ?? null,
    ];

    $syncPages = [
        'has_data'    => !empty($pagesRaw),
        'complete'    => (bool)($metaPages['is_complete'] ?? false),
        'updated_at'  => $metaPages['updated_at'] ?? null,
        'run_id'      => $metaPages['run_id'] ?? null,
    ];

    // Ordenar por modified desc (por seguridad)
    usort($postsRaw, fn($a, $b) => strcmp((string)($b['modified'] ?? ''), (string)($a['modified'] ?? '')));
    usort($pagesRaw, fn($a, $b) => strcmp((string)($b['modified'] ?? ''), (string)($a['modified'] ?? '')));

    // Mapear al formato que tu Blade espera (id + title.rendered)
    $posts = array_map(function ($x) {
        $title = $x['title'] ?? 'Sin título';
        return [
            'id'     => $x['wp_id'] ?? null,
            'slug'   => $x['slug'] ?? null,
            'status' => $x['status'] ?? null,
            'date'   => $x['date'] ?? null,
            'link'   => $x['link'] ?? null,
            'title'  => ['rendered' => $title],
            // opcional: por si quieres mostrarlo después
            'modified' => $x['modified'] ?? null,
        ];
    }, $postsRaw);

    $pages = array_map(function ($x) {
        $title = $x['title'] ?? 'Sin título';
        return [
            'id'     => $x['wp_id'] ?? null,
            'slug'   => $x['slug'] ?? null,
            'status' => $x['status'] ?? null,
            'date'   => $x['date'] ?? null,
            'link'   => $x['link'] ?? null,
            'title'  => ['rendered' => $title],
            'modified' => $x['modified'] ?? null,
        ];
    }, $pagesRaw);

    // Counts (si no han llegado aún, serán [])
    $countPosts = Cache::get("inv_counts:{$siteKey}:post", []);
    $countPages = Cache::get("inv_counts:{$siteKey}:page", []);

    $countPosts = is_array($countPosts) ? $countPosts : [];
    $countPages = is_array($countPages) ? $countPages : [];

    foreach (['publish','draft','future','pending','private'] as $st) {
        $countPosts[$st] = (int)($countPosts[$st] ?? 0);
        $countPages[$st] = (int)($countPages[$st] ?? 0);
    }

    // Para que tu vista no truene (no paginamos “real” aquí)
    $perPagePosts = 50; $perPagePages = 50; $pagePosts = 1; $pagePages = 1;

    return view('Dominios.DominioContenido', compact(
        'dominio',
        'posts',
        'pages',
        'countPosts',
        'countPages',
        'syncPosts',
        'syncPages',
        'perPagePosts',
        'perPagePages',
        'pagePosts',
        'pagePages'
    ));
}




    public function Generador(string $IdDominio)
{
    $configs = Dominios_ContenidoModel::select('id_dominio_contenido','tipo','palabras_claves')
        ->where('id_dominio', $IdDominio)
        ->orderByDesc('id_dominio_contenido')
        ->get();

    if ($configs->isEmpty()) {
        return back()->withError('No hay configuración para este dominio.');
    }

    // Normaliza tipo a 'post' o 'page' y deja solo esas configs
    $configs = $configs->map(function ($c) {
        $tipoRaw = strtolower(trim((string)$c->tipo));

        $tipo = match ($tipoRaw) {
            'post', 'posts' => 'post',
            'page', 'pagina', 'página', 'paginas', 'páginas' => 'page',
            default => null,
        };

        $c->tipo_normalizado = $tipo;
        return $c;
    })->filter(fn($c) => in_array($c->tipo_normalizado, ['post','page'], true));

    if ($configs->isEmpty()) {
        return back()->withError('No hay configuraciones válidas (post/page) para este dominio.');
    }

    foreach ($configs as $config) {
        $tipo = $config->tipo_normalizado;

        $raw = (string)$config->palabras_claves;
        $palabras = json_decode($raw, true);

        if (!is_array($palabras)) {
            $palabras = array_values(array_filter(array_map('trim', explode(',', $raw))));
        }

        if (count($palabras) === 0) {
            // si quieres que falle si UNA config no tiene palabras, cambia a return back()->withError(...)
            continue;
        }

        // ✅ límite por click
        $palabras = array_slice($palabras, 0, 5);

        foreach ($palabras as $keyword) {
            dispatch(new GenerarContenidoKeywordJob(
                $IdDominio,
                (string)$config->id_dominio_contenido,
                $tipo,
                $keyword
            ));
        }
    }

    return back()->withSuccess('Generación iniciada. Se está procesando en segundo plano.');
}



private function promptHtml(string $tipo, string $keyword): string
{
    $base = "Devuelve SOLO HTML para pegar en WordPress.
NO incluyas: <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>, <main>.
Devuelve únicamente el contenido: <h1>, <h2>, <h3>, <p>, <ul><li>, etc.";

    if ($tipo === 'post') {
        return "{$base}
Escribe un POST SEO en español para: {$keyword}.
Reglas:
- No uses títulos genéricos como 'Introducción' o 'Conclusión'.
- Incluye H1 y secciones útiles con H2/H3.";
    }

    return "{$base}
Crea una PÁGINA/LANDING SEO en español para: {$keyword}.
Reglas:
- Enfocada a conversión: beneficios, proceso, FAQ, CTA.
- No uses 'Introducción', 'Conclusión' ni '¿Qué es...?'.";
}


private function promptAuditorHtml(string $tipo, string $keyword, string $draftHtml): string
{
    return "Eres un consultor SEO senior especializado en análisis técnico y de contenido.
Tu tarea es AUDITAR y MEJORAR el contenido entregado y devolver UNA VERSIÓN FINAL.

Devuelve SOLO HTML válido listo para WordPress.
NO incluyas <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>.
NO uses markdown.
NO expliques nada.
NO uses headings: Introducción, Conclusión, ¿Qué es...?
NO uses casos de éxito ni testimonios.
NO uses el título 'guía práctica' ni variantes.

Objetivo:
- Mejorar intención de búsqueda, profundidad semántica y estructura
- Reducir relleno y repetición
- Hacer headings más específicos y no genéricos
- Mejorar el gancho inicial (primeros 2 párrafos)
- Añadir FAQ (2-5) si aporta
- Añadir CTA breve al final

Tipo: {$tipo}
Keyword principal: {$keyword}

HTML A MEJORAR (reescribe y devuelve el HTML final):
{$draftHtml}";
}

private function openaiText(string $apiKey, string $model, string $prompt): string
{
    $resp = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Accept' => 'application/json',
        ])
        ->connectTimeout(10)
        ->timeout(120)
        ->retry(1, 500)
        ->post('https://api.openai.com/v1/responses', [
            'model' => $model,
            'input' => $prompt,
        ]);

    if (!$resp->successful()) {
        dd('Error OpenAI', $resp->status(), $resp->body());
    }

    $data = $resp->json();

    $text = '';
    foreach (($data['output'] ?? []) as $item) {
        foreach (($item['content'] ?? []) as $c) {
            if (($c['type'] ?? '') === 'output_text') {
                $text .= ($c['text'] ?? '');
            }
        }
    }

    if (trim($text) === '') {
        dd('No encontré texto en respuesta', $data);
    }

    return trim($text);
}
private function promptRedactor(string $tipo, string $keyword, string $enfoque): string
{
    $base = "Devuelve SOLO HTML válido listo para pegar en WordPress.
NO incluyas <!DOCTYPE>, <html>, <head>, <meta>, <title>, <body>.
NO uses markdown. NO expliques nada.
NO uses el texto 'guía práctica' ni variantes.
NO uses headings genéricos: 'Introducción', 'Conclusión', '¿Qué es...?'.
NO uses casos de éxito ni testimonios.
Usa lenguaje claro, semántico y profundo.";

    if ($tipo === 'post') {
        return "{$base}

Actúa como Redactor SEO profesional (España).
Keyword: {$keyword}
Enfoque: {$enfoque}

Estructura obligatoria:
- 1 <h1> (título atractivo y natural)
- 6 a 9 <h2> (no genéricos, distintos entre sí)
- 1 a 2 <h3> dentro de varios <h2>
- Párrafos reales (sin relleno)
- Usa <ul><li> cuando aporte claridad
- Añade 2 a 5 FAQs (preguntas + respuesta en <p>)
- Cierra con CTA breve en <p><strong>...</strong></p>

Reglas de estilo:
- No empieces con definiciones tipo diccionario.
- Evita frases: 'en este artículo veremos...'.
- No repitas encabezados entre secciones.";
    }

    // default: page/landing
    return "{$base}

Actúa como Redactor SEO experto en conversión (España).
Keyword: {$keyword}
Enfoque: {$enfoque}

Estructura obligatoria (landing):
- 1 <h1> potente
- 8 a 12 <h2> orientados a conversión (beneficios, servicios, proceso, objeciones, FAQ, CTA)
- Incluye varios <h3> para profundizar
- CTA al inicio, a mitad y al final
- Usa bloques con <div> si ayuda a maquetar (sin CSS, solo estructura)
- Añade 3 a 6 FAQs
- Cierra con CTA breve en <p><strong>...</strong></p>

Reglas:
- No uses 'Introducción'/'Conclusión'/'¿Qué es...?'
- No uses casos de éxito ni testimonios
- No repitas encabezados.";
}


public function ContenidoGenerado(Request $request, string $IdDominio)
{
    $tipo = $request->get('tipo');
    $estatus = $request->get('estatus');
    $dominio = DominiosModel::find($IdDominio);

    $query = Dominios_Contenido_DetallesModel::where('id_dominio', (int)$IdDominio)
        ->orderByDesc('id_dominio_contenido_detalle');

    if ($tipo) {
        $query->where('tipo', $tipo);
    }

    if ($estatus) {
        $query->where('estatus', $estatus);
    }

    $items = $query->get(); // ✅ DataTables se encarga de paginar

    return view('Dominios.ContenidoGenerado', compact('IdDominio', 'items', 'tipo', 'estatus', 'dominio'));
}
    public function EditarTipoGenerador(Request $request, $IdDominioGenerador)
{
    $generador = Dominios_ContenidoModel::findOrFail($IdDominioGenerador);

    $tiposPermitidos = ['POST', 'PAGINAS'];

    // Tipos existentes en el dominio de este generador
    $tiposExistentes = Dominios_ContenidoModel::where('id_dominio', $generador->id_dominio)
        ->pluck('tipo')
        ->map(fn($t) => strtoupper(trim($t)))
        ->toArray();

    // El tipo "otro" que se podría elegir
    $tipoActual = strtoupper(trim($generador->tipo));
    $otroTipo = $tipoActual === 'POST' ? 'PAGINAS' : 'POST';

    // Solo se puede cambiar al otro si NO existe ya en el dominio
    $puedeCambiar = !in_array($otroTipo, $tiposExistentes, true);

    // Opciones del select: siempre incluye el actual; incluye el otro solo si puede
    $tiposDisponibles = $puedeCambiar ? [$tipoActual, $otroTipo] : [$tipoActual];

    return view('Dominios.GeneradorEditar', compact('generador', 'tiposDisponibles', 'puedeCambiar'));
}

    



    public function GuardarEditarTipoGenerador(Request $request, $IdDominioGenerador)
    {
        $generador = Dominios_ContenidoModel::findOrFail($IdDominioGenerador);

        // 1) Validación básica
        $request->validate([
            'tipo' => ['required', 'in:POST,PAGINAS'],
            'palabras_claves' => ['nullable'], // viene en hidden como JSON normalmente
        ]);

        $nuevoTipo = strtoupper(trim($request->input('tipo')));

        // 2) Bloqueo de duplicados dentro del mismo dominio (excluyendo el mismo registro)
        $existeEnDominio = Dominios_ContenidoModel::where('id_dominio', $generador->id_dominio)
            ->where('tipo', $nuevoTipo)
            ->where('id_dominio_contenido', '!=', $generador->id_dominio_contenido)
            ->exists();

        if ($existeEnDominio) {
            return back()
                ->withErrors(['tipo' => 'Ese tipo ya existe para este dominio. No puedes duplicarlo.'])
                ->withInput();
        }

        $palabras = json_decode($request['palabras_claves']); // ["seo","paginas"]
        
        $palabras_clave_cadena = implode(',', $palabras);   // "seo, paginas"

        try 
        {
            $generador->fill([
                 'tipo' => $nuevoTipo,
                'palabras_claves' => $palabras_clave_cadena,
            ]);
            $generador->save(); //actualizar empresa
                
                
    
        } 
        catch (Exception $ex) 
        {
            return back()->withError('Ocurrio Un Error al Editar el Tipo Generador de Contenido ' . $ex->getMessage())->withInput();
        }
        return redirect()->route('dominios.show', $generador->id_dominio)->withSuccess('El Tipo de Generador Contenido Se Ha Editado Exitosamente');

    }









public function publicar($dominio, int $detalle): RedirectResponse
{
    $dom = DominiosModel::findOrFail($dominio);
    $it  = Dominios_Contenido_DetallesModel::findOrFail($detalle);

    $it->estatus = 'en_proceso';
    $it->error = null;
    $it->save();

    try {
        $secret = (string) env('WP_WEBHOOK_SECRET'); // DEBE ser el mismo que el plugin
        if ($secret === '') {
            throw new \RuntimeException('WP_WEBHOOK_SECRET no configurado en .env');
        }

        $wpBase = rtrim((string)$dom->url, '/');

        $urlRest = $wpBase . '/wp-json/lws/v1/upsert';
        $urlFallback = $wpBase . '/wp-admin/admin-post.php?action=lws_upsert';

        $type = ($it->tipo === 'page') ? 'page' : 'post';

        if (empty($it->contenido_html)) {
            throw new \RuntimeException('contenido_html está vacío (no hay nada que publicar).');
        }

        $payload = [
            'type'  => $type,
            'wp_id' => $it->wp_id ?: null,

            'title'   => $it->title ?: ($it->keyword ?: 'Sin título'),
            'content' => $it->contenido_html,  // 👈 JSON Elementor guardado en BD

            // ✅ importante para el plugin
            'builder' => 'elementor',

            // ✅ evita el “encajonado” por el header/theme
            // si quieres header/footer: usa 'elementor_full_width'
            'wp_page_template' => ($type === 'page') ? 'elementor_canvas' : '',

            // status normal
            'status' => 'publish',

            // programar si quieres:
            // 'schedule_at' => '2025-12-19 10:00:00',
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('No se pudo serializar payload JSON');
        }

        $ts = time();
        $sig = hash_hmac('sha256', $ts . '.' . $body, $secret);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Timestamp'  => (string)$ts,
            'X-Signature'  => $sig,
        ];

        $resp = Http::timeout(25)->withHeaders($headers)->send('POST', $urlRest, ['body' => $body]);

        // fallback si REST no existe / bloqueado
        if (in_array($resp->status(), [404, 405], true)) {
            $resp = Http::timeout(25)->withHeaders($headers)->send('POST', $urlFallback, ['body' => $body]);
        }

        $json = $resp->json();

        if (!$resp->ok() || !is_array($json) || empty($json['ok'])) {
            $msg = is_array($json) ? ($json['message'] ?? 'Error desconocido') : ('HTTP ' . $resp->status());
            $it->estatus = 'error';
            $it->error = $msg;
            $it->save();
            return back()->with('error', 'No se pudo publicar: ' . $msg);
        }

        // OK
        $it->estatus = (($json['status'] ?? '') === 'publish') ? 'publicado' : 'generado';
        $it->wp_id   = (int)($json['wp_id'] ?? 0) ?: $it->wp_id;
        $it->wp_link = (string)($json['link'] ?? '');
        $it->save();

        return back()->with('exito', 'Contenido enviado y publicado en WordPress.');
    } catch (\Throwable $e) {
        $it->estatus = 'error';
        $it->error = $e->getMessage();
        $it->save();

        return back()->with('error', 'Error publicando en WordPress: ' . $e->getMessage());
    }
}



public function programar(Request $request, $dominio, int $detalle): RedirectResponse
{
    $dom = DominiosModel::findOrFail($dominio);
    $it  = Dominios_Contenido_DetallesModel::findOrFail($detalle);

    $request->validate([
        'schedule_at' => ['required', 'string'], // ISO UTC desde el hidden (ej: ...Z)
    ]);

    $it->estatus = 'en_proceso';
    $it->error = null;
    $it->save();

    try {
        $secret = (string) env('WP_WEBHOOK_SECRET');
        if ($secret === '') {
            throw new \RuntimeException('WP_WEBHOOK_SECRET no configurado en .env');
        }

        // schedule_at viene en ISO UTC (Z). Parse robusto y normalización.
        $scheduleAtRaw = (string) $request->input('schedule_at');

        try {
            $dtUtc = Carbon::parse($scheduleAtRaw)->setTimezone('UTC');
        } catch (\Throwable $e) {
            throw new \RuntimeException('Fecha inválida en schedule_at: ' . $scheduleAtRaw);
        }

        // ISO limpio para enviar al plugin
        $scheduleAtUtcIso = $dtUtc->toIso8601String(); // 2025-12-19T16:30:00+00:00

        $wpBase = rtrim((string) $dom->url, '/');
        $urlRest = $wpBase . '/wp-json/lws/v1/upsert';
        $urlFallback = $wpBase . '/wp-admin/admin-post.php?action=lws_upsert';

        $type = ($it->tipo === 'page') ? 'page' : 'post';

        $payload = [
            'type'        => $type,
            'wp_id'       => $it->wp_id ?: null,
            'title'       => $it->title ?: ($it->keyword ?: 'Sin título'),
            'content'     => $it->contenido_html ?: '',
            'status'      => 'future',
            'schedule_at' => $scheduleAtUtcIso,
        ];

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $ts = time();
        $sig = hash_hmac('sha256', $ts . '.' . $body, $secret);

        $headers = [
            'Content-Type' => 'application/json',
            'X-Timestamp'  => (string) $ts,
            'X-Signature'  => $sig,
        ];

        $resp = Http::timeout(25)->withHeaders($headers)->send('POST', $urlRest, ['body' => $body]);

        // fallback si wp-json está bloqueado
        if (in_array($resp->status(), [404, 405], true)) {
            $resp = Http::timeout(25)->withHeaders($headers)->send('POST', $urlFallback, ['body' => $body]);
        }

        $json = $resp->json();

        if (!$resp->ok() || !is_array($json) || empty($json['ok'])) {
            $msg = is_array($json) ? ($json['message'] ?? 'Error desconocido') : ('HTTP ' . $resp->status());
            $it->estatus = 'error';
            $it->error = $msg;
            $it->save();
            return back()->with('error', 'No se pudo programar: ' . $msg);
        }

        // Guardar ID y link siempre que existan
        $it->wp_id   = (int) ($json['wp_id'] ?? 0) ?: $it->wp_id;
        $it->wp_link = (string) ($json['link'] ?? '');

        // Guardar estatus según WP + scheduled_at
        $wpStatus = (string) ($json['status'] ?? '');

        if ($wpStatus === 'future') {
            $it->estatus = 'programado';

            // ✅ ideal: usar fecha exacta que WP guardó (si plugin la devuelve)
            if (!empty($json['scheduled_gmt'])) {
                $it->scheduled_at = Carbon::parse($json['scheduled_gmt'], 'UTC')
                    ->setTimezone(config('app.timezone'));
            } else {
                // fallback: usa lo que tú mandaste
                $it->scheduled_at = $dtUtc->copy()->setTimezone(config('app.timezone'));
            }
        } elseif ($wpStatus === 'publish') {
            $it->estatus = 'publicado';
            $it->scheduled_at = null;
        } else {
            $it->estatus = 'generado';
            $it->scheduled_at = null;
        }

        $it->save();

        return back()->with('exito', 'Contenido programado correctamente en WordPress.');
    } catch (\Throwable $e) {
        $it->estatus = 'error';
        $it->error = $e->getMessage();
        $it->save();

        return back()->with('error', 'Error programando en WordPress: ' . $e->getMessage());
    }
}
}
