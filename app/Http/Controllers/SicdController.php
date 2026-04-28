<?php

namespace App\Http\Controllers;

use App\Imports\SicdDetallesImport;
use App\Models\Boleta;
use App\Models\HistorialCambio;
use App\Models\Producto;
use App\Models\Sicd;
use App\Models\SicdExterno;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SicdController extends Controller
{
    public function validarCodigo(Request $request)
    {
        $input = trim($request->query('codigo', ''));

        if ($input === '') {
            return response()->json(['valido' => false, 'mensaje' => 'Ingresa un código.']);
        }

        try {
            // Si el input es numérico, buscar por id; si no, por num_int_sol
            if (ctype_digit($input)) {
                $solicitud = SicdExterno::find((int) $input);
            } else {
                $solicitud = SicdExterno::buscar($input);
            }
        } catch (\Exception) {
            return response()->json(['valido' => false, 'mensaje' => 'No se pudo conectar al sistema externo.']);
        }

        if (!$solicitud) {
            return response()->json(['valido' => false, 'mensaje' => 'Código SICD no encontrado en el sistema.']);
        }

        // El código real siempre es num_int_sol del registro encontrado
        $codigoReal = $solicitud->num_int_sol;

        // Filtro por centro de costo: usar el prefijo del código real
        $user = auth()->user();
        if ($user && $user->tieneFiltroCC()) {
            $prefix  = strtoupper($user->centroCostoPrefix());
            $prefijo = strtoupper(trim(preg_replace('/[^A-Za-z].*$/u', '', $codigoReal)));
            if ($prefijo !== $prefix) {
                return response()->json(['valido' => false, 'mensaje' => "Este código no pertenece a tu centro de costo ({$prefix})."]);
            }
        }

        $detalles = DB::connection('sicd_externa')
            ->table('detalle_solicitud')
            ->where('num_int_sol_comp', $codigoReal)
            ->select('item_presup', 'cantidad', 'unidad', 'detalle', 'valor_unitario', 'total_neto', 'estado')
            ->get();

        return response()->json([
            'valido'          => true,
            'mensaje'         => 'Código válido.',
            'codigo_resuelto' => $codigoReal,
            'centro_costo'    => $solicitud->centro_costo,
            'estado'          => $solicitud->estado,
            'fecha'           => $solicitud->fecha_creacion,
            'detalles'        => $detalles,
        ]);
    }

    public function verificarPdf(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $codigo = strtoupper(trim($request->query('codigo', '')));
        if ($codigo === '') return response()->json(['tiene_pdf' => false]);

        $cacheKey = 'sicd_tiene_pdf_' . md5($codigo);
        $tienePdf = Cache::remember($cacheKey, now()->addHours(6), function () use ($codigo) {
            try {
                // MAX_EXECUTION_TIME hint: aborta la query en 4 segundos si la BD externa es lenta
                $row = DB::connection('sicd_externa')
                    ->selectOne('SELECT /*+ MAX_EXECUTION_TIME(4000) */ 1 AS tiene FROM solicitud_full WHERE num_int_sol = ? AND pdf IS NOT NULL LIMIT 1', [$codigo]);
                return $row !== null;
            } catch (\Exception) {
                return false;
            }
        });

        return response()->json(['tiene_pdf' => $tienePdf]);
    }

    public function verPdfExterno(Request $request)
    {
        abort_unless(auth()->check(), 403);
        $codigo = strtoupper(trim($request->query('codigo', '')));
        abort_if($codigo === '', 400);

        $row = DB::connection('sicd_externa')
            ->table('solicitud_full')
            ->where('num_int_sol', $codigo)
            ->select('pdf')
            ->first();

        abort_if(!$row || empty($row->pdf), 404);

        return response($row->pdf, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="SICD_' . preg_replace('/[^A-Z0-9_\-]/i', '_', $codigo) . '.pdf"');
    }

    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('sicd'), 403);
        $user  = auth()->user();
        $query = Sicd::with(['usuario', 'detalles', 'ordenesCompra'])->orderByDesc('created_at');

        if ($user->tieneFiltroCC()) {
            $prefix = $user->centroCostoPrefix();
            // Extrae solo las letras iniciales del codigo_sicd (ignora paréntesis, / y números)
            $query->whereRaw("REGEXP_REPLACE(codigo_sicd, '[^A-Za-z].*', '') = ?", [$prefix]);
        }

        $sicds = $query->paginate(20);
        return view('admin.sicd.index', compact('sicds'));
    }

    public function create()
    {
        abort_unless(auth()->user()->tienePermiso('sicd'), 403);
        return view('admin.sicd.crear');
    }

    /**
     * Fase 1: valida, guarda archivos en temp, analiza el Excel.
     * Si hay conflictos → redirige a la vista de resolución.
     * Si todo concuerda → crea el SICD directamente.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'archivo_sicd'  => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'archivo_excel' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'codigo_sicd'   => ['required', 'string', 'max:100', 'regex:/^TIC\([^)]+\)\/?\\d+$/i'],
            'descripcion'   => ['nullable', 'string', 'max:500'],
        ], [
            'archivo_sicd.required'  => 'Debes adjuntar el documento SICD.',
            'archivo_sicd.mimes'     => 'El archivo SICD debe ser PDF, JPG o PNG.',
            'archivo_excel.required' => 'Debes adjuntar el Excel con el detalle de productos.',
            'archivo_excel.mimes'    => 'El archivo Excel debe ser XLSX, XLS o CSV.',
            'codigo_sicd.required'   => 'El código SICD es obligatorio.',
            'codigo_sicd.regex'      => 'El formato debe ser TIC(RAMO)/NUMERO (ej: TIC(RAMO)/12345).',
        ]);

        $codigo         = strtoupper(trim($data['codigo_sicd']));
        $codigo         = preg_replace('/^(TIC\([^)]+\))\/?(\d+)$/i', '$1/$2', $codigo);
        $nombreOriginal = $request->file('archivo_sicd')->getClientOriginalName();

        // Guardar archivos en carpeta temporal
        $rutaSicdTemp  = $request->file('archivo_sicd')->store('temp/sicd', 'local');
        $rutaExcelTemp = $request->file('archivo_excel')->store('temp/excel', 'local');

        // Parsear Excel
        $rows        = Excel::toCollection(new SicdDetallesImport, $request->file('archivo_excel'))->first();
        $productosDB = Producto::get(['id', 'nombre']);

        $exactos    = [];
        $conflictos = [];

        foreach ($rows as $row) {
            $descripcion = trim((string) ($row[0] ?? ''));
            $unidad      = trim((string) ($row[1] ?? ''));
            $cantidad    = (int) ($row[2] ?? 0);
            $precioNeto  = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $totalNeto   = is_numeric($row[4] ?? '') ? (float) $row[4] : null;

            if ($descripcion === '' || $cantidad <= 0) continue;

            $item = compact('descripcion', 'unidad', 'cantidad', 'precioNeto', 'totalNeto');

            // Fuzzy matching
            $descNorm  = strtolower($descripcion);
            $mejorPct  = 0;
            $mejorProd = null;

            foreach ($productosDB as $p) {
                $dist   = levenshtein($descNorm, strtolower($p->nombre));
                $maxLen = max(strlen($descNorm), strlen($p->nombre));
                $pct    = $maxLen > 0 ? (1 - $dist / $maxLen) * 100 : 0;
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejorProd = $p; }
            }

            if ($mejorPct == 100) {
                $item['producto_id'] = $mejorProd->id;
                $exactos[] = $item;
            } else {
                $item['similitud']         = round($mejorPct, 1);
                $item['sugerencia_id']     = $mejorProd?->id;
                $item['sugerencia_nombre'] = $mejorProd?->nombre;
                $conflictos[] = $item;
            }
        }

        // Si hay conflictos → guardar estado en sesión y resolver
        if (!empty($conflictos)) {
            session([
                'sicd_pendiente' => [
                    'codigo'          => $codigo,
                    'nombre_original' => $nombreOriginal,
                    'ruta_sicd'       => $rutaSicdTemp,
                    'descripcion'     => $data['descripcion'] ?? null,
                    'exactos'         => $exactos,
                    'conflictos'      => $conflictos,
                ],
            ]);

            return redirect()->route('admin.sicd.resolver');
        }

        // Sin conflictos → crear SICD directamente
        Storage::disk('local')->move($rutaSicdTemp, 'documentos/sicd/' . basename($rutaSicdTemp));
        $rutaFinal = 'documentos/sicd/' . basename($rutaSicdTemp);

        return $this->crearSicd($codigo, $nombreOriginal, $rutaFinal, $data['descripcion'] ?? null, $exactos);
    }

    /**
     * Fase 2: muestra la vista para resolver conflictos.
     */
    public function resolver()
    {
        $pendiente = session('sicd_pendiente');
        if (!$pendiente) {
            return redirect()->route('admin.sicd.create')
                ->with('error', 'Sesión expirada. Vuelve a cargar el SICD.');
        }

        $ccId      = auth()->user()->tieneFiltroCC() ? auth()->user()->centro_costo_id : null;
        $productos = Producto::orderBy('nombre')->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->get(['id', 'nombre']);

        return view('admin.sicd.resolver', compact('pendiente', 'productos'));
    }

    /**
     * Fase 3: confirma las resoluciones y crea el SICD definitivamente.
     */
    public function confirmar(Request $request)
    {
        $pendiente = session('sicd_pendiente');
        if (!$pendiente) {
            return redirect()->route('admin.sicd.create')
                ->with('error', 'Sesión expirada. Vuelve a cargar el SICD.');
        }

        $resoluciones = $request->input('resoluciones', []);

        // Combinar exactos + conflictos resueltos
        $items = $pendiente['exactos'];

        foreach ($pendiente['conflictos'] as $idx => $conflicto) {
            $res = $resoluciones[$idx] ?? [];
            $accion = $res['accion'] ?? 'nuevo';

            $conflicto['producto_id'] = ($accion === 'enlazar' && !empty($res['producto_id']))
                ? (int) $res['producto_id']
                : null;

            $items[] = $conflicto;
        }

        // Limpiar temp excel (ya no se necesita)
        $sicd = $this->crearSicd(
            $pendiente['codigo'],
            $pendiente['nombre_original'],
            $pendiente['ruta_sicd'],
            $pendiente['descripcion'],
            $items
        );

        session()->forget('sicd_pendiente');

        return $sicd;
    }

    // ── Helpers privados ────────────────────────────────────────────────────

    private function crearSicd(string $codigo, string $nombreOriginal, string $rutaTemp, ?string $descripcion, array $items)
    {
        $boletaId = null;
        if (Storage::disk('local')->exists($rutaTemp)) {
            $rutaAbsoluta = Storage::disk('local')->path($rutaTemp);
            \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
            $boleta   = Boleta::create([
                'archivo_nombre' => $nombreOriginal,
                'archivo_blob'   => base64_encode(file_get_contents($rutaAbsoluta)),
                'archivo_mime'   => mime_content_type($rutaAbsoluta) ?: 'application/octet-stream',
            ]);
            $boletaId = $boleta->id;
        }

        $sicd = Sicd::create([
            'codigo_sicd' => $codigo,
            'boleta_id'   => $boletaId,
            'descripcion' => $descripcion,
            'estado'      => 'pendiente',
            'usuario_id'  => Auth::id(),
        ]);

        foreach ($items as $item) {
            $sicd->detalles()->create([
                'producto_id'           => $item['producto_id'] ?? null,
                'nombre_producto_excel' => $item['descripcion'],
                'unidad'                => $item['unidad'] ?: null,
                'cantidad_solicitada'   => $item['cantidad'],
                'cantidad_recibida'     => 0,
                'precio_neto'           => $item['precioNeto'] ?? null,
                'total_neto'            => $item['totalNeto'] ?? null,
            ]);
        }

        return redirect()->route('admin.sicd.show', $sicd->id)
            ->with('success', "SICD {$codigo} creado con {$sicd->detalles()->count()} producto(s).");
    }

    /**
     * Recepción directa desde el modal de Inventario.
     * modo=nuevo  → crea el SICD desde archivos, actualiza stock, marca recibido.
     * modo=existente → usa un SICD ya creado, actualiza stock de sus detalles, marca recibido.
     */
    public function recibirDirecto(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $modo = $request->input('_modo', 'nuevo');

        if ($modo === 'existente') {
            $request->validate([
                'sicd_existente_id' => ['required', 'integer', 'exists:sicds,id'],
            ], [
                'sicd_existente_id.required' => 'Selecciona un SICD existente.',
                'sicd_existente_id.exists'   => 'El SICD seleccionado no existe.',
            ]);

            $sicd = Sicd::with('detalles.producto')->findOrFail($request->sicd_existente_id);

            if ($sicd->estado === 'recibido') {
                return back()->with('error', "El SICD {$sicd->codigo_sicd} ya está marcado como recibido.");
            }

            DB::transaction(function () use ($sicd) {
                foreach ($sicd->detalles as $detalle) {
                    if (!$detalle->producto_id || !$detalle->producto) continue;
                    $cantidad = $detalle->cantidad_solicitada;
                    if ($cantidad <= 0) continue;

                    $detalle->cantidad_recibida = $cantidad;
                    $detalle->save();

                    $detalle->producto->stock_actual += $cantidad;
                    $detalle->producto->actualizarFechasStock();
                    $detalle->producto->save();

                    HistorialCambio::create([
                        'producto_id'  => $detalle->producto_id,
                        'contenedor_id'=> $detalle->producto->contenedor,
                        'cantidad'     => $cantidad,
                        'tipo'         => 'entrada',
                        'motivo'       => "Recepción directa – SICD {$sicd->codigo_sicd}",
                        'aprobado_por' => Auth::user()->name,
                        'usuario_id'   => Auth::id(),
                        'origen'       => 'sicd',
                        'origen_id'    => $sicd->id,
                    ]);
                }

                $sicd->estado = 'recibido';
                $sicd->save();
            });

            return redirect()->route('admin.sicd.show', $sicd->id)
                ->with('success', "SICD {$sicd->codigo_sicd} marcado como recibido y stock actualizado.");
        }

        // ── modo=nuevo ──────────────────────────────────────────────────────
        $data = $request->validate([
            'archivo_sicd'  => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'archivo_excel' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'codigo_sicd'   => ['required', 'string', 'max:100', 'regex:/^TIC\([^)]+\)\/?\\d+$/i'],
            'descripcion'   => ['nullable', 'string', 'max:500'],
        ], [
            'archivo_sicd.required'  => 'Debes adjuntar el documento SICD.',
            'archivo_sicd.mimes'     => 'El archivo SICD debe ser PDF, JPG o PNG.',
            'archivo_excel.required' => 'Debes adjuntar el Excel con el detalle de productos.',
            'archivo_excel.mimes'    => 'El archivo Excel debe ser XLSX, XLS o CSV.',
            'codigo_sicd.required'   => 'El código SICD es obligatorio.',
            'codigo_sicd.regex'      => 'El formato debe ser TIC(RAMO)/NUMERO (ej: TIC(RAMO)/12345).',
        ]);

        $codigo         = strtoupper(trim($data['codigo_sicd']));
        $codigo         = preg_replace('/^(TIC\([^)]+\))\/?(\d+)$/i', '$1/$2', $codigo);
        $nombreOriginal = $request->file('archivo_sicd')->getClientOriginalName();

        $rutaSicdTemp = $request->file('archivo_sicd')->store('temp/sicd', 'local');

        // Parsear Excel
        $rows        = Excel::toCollection(new SicdDetallesImport, $request->file('archivo_excel'))->first();
        $productosDB = Producto::get(['id', 'nombre']);

        $items = [];
        foreach ($rows as $row) {
            $descripcion = trim((string) ($row[0] ?? ''));
            $unidad      = trim((string) ($row[1] ?? ''));
            $cantidad    = (int) ($row[2] ?? 0);
            $precioNeto  = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $totalNeto   = is_numeric($row[4] ?? '') ? (float) $row[4] : null;

            if ($descripcion === '' || $cantidad <= 0) continue;

            $item = compact('descripcion', 'unidad', 'cantidad', 'precioNeto', 'totalNeto');

            // Fuzzy matching
            $descNorm  = strtolower($descripcion);
            $mejorPct  = 0;
            $mejorProd = null;
            foreach ($productosDB as $p) {
                $dist   = levenshtein($descNorm, strtolower($p->nombre));
                $maxLen = max(strlen($descNorm), strlen($p->nombre));
                $pct    = $maxLen > 0 ? (1 - $dist / $maxLen) * 100 : 0;
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejorProd = $p; }
            }

            $item['producto_id'] = $mejorPct == 100 ? $mejorProd->id : null;
            $items[] = $item;
        }

        $boletaId = null;
        if (Storage::disk('local')->exists($rutaSicdTemp)) {
            $rutaAbsoluta = Storage::disk('local')->path($rutaSicdTemp);
            \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
            $boleta   = Boleta::create([
                'archivo_nombre' => $nombreOriginal,
                'archivo_blob'   => base64_encode(file_get_contents($rutaAbsoluta)),
                'archivo_mime'   => mime_content_type($rutaAbsoluta) ?: 'application/octet-stream',
            ]);
            $boletaId = $boleta->id;
        }

        DB::transaction(function () use ($codigo, $boletaId, $data, $items) {
            $sicd = Sicd::create([
                'codigo_sicd' => $codigo,
                'boleta_id'   => $boletaId,
                'descripcion' => $data['descripcion'] ?? null,
                'estado'      => 'recibido',
                'usuario_id'  => Auth::id(),
            ]);

            foreach ($items as $item) {
                $cantidad = $item['cantidad'];

                $sicd->detalles()->create([
                    'producto_id'           => $item['producto_id'],
                    'nombre_producto_excel' => $item['descripcion'],
                    'unidad'                => $item['unidad'] ?: null,
                    'cantidad_solicitada'   => $cantidad,
                    'cantidad_recibida'     => $item['producto_id'] ? $cantidad : 0,
                    'precio_neto'           => $item['precioNeto'] ?? null,
                    'total_neto'            => $item['totalNeto'] ?? null,
                ]);

                if ($item['producto_id']) {
                    $producto = Producto::find($item['producto_id']);
                    if ($producto) {
                        $producto->stock_actual += $cantidad;
                        $producto->actualizarFechasStock();
                        $producto->save();

                        HistorialCambio::create([
                            'producto_id'  => $producto->id,
                            'contenedor_id'=> $producto->contenedor,
                            'cantidad'     => $cantidad,
                            'tipo'         => 'entrada',
                            'motivo'       => "Recepción directa – SICD {$codigo}",
                            'aprobado_por' => Auth::user()->name,
                            'usuario_id'   => Auth::id(),
                            'origen'       => 'sicd',
                            'origen_id'    => $sicd->id,
                        ]);
                    }
                }
            }

            return $sicd;
        });

        $sicd = Sicd::where('codigo_sicd', $codigo)->latest()->first();
        return redirect()->route('admin.sicd.show', $sicd->id)
            ->with('success', "SICD {$codigo} creado, recibido y stock actualizado.");
    }

    public function show(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('sicd'), 403);
        $sicd = Sicd::with(['usuario', 'boleta', 'detalles.producto', 'ordenesCompra'])->findOrFail($id);
        return view('admin.sicd.show', compact('sicd'));
    }

    public function descargar(int $id)
    {
        $sicd   = Sicd::with('boleta')->findOrFail($id);
        $boleta = $sicd->boleta;

        if ($boleta?->archivo_blob) {
            $contenido = base64_decode($boleta->archivo_blob);
            $mime      = $boleta->archivo_mime ?: 'application/octet-stream';
            $nombre    = $boleta->archivo_nombre ?: 'boleta';
            return response($contenido, 200)
                ->header('Content-Type', $mime)
                ->header('Content-Disposition', 'inline; filename="' . $nombre . '"')
                ->header('Content-Length', strlen($contenido));
        }

        if ($boleta?->archivo_ruta && Storage::disk('local')->exists($boleta->archivo_ruta)) {
            return Storage::disk('local')->download($boleta->archivo_ruta, $boleta->archivo_nombre ?: basename($boleta->archivo_ruta));
        }

        return back()->with('error', 'El archivo no está disponible.');
    }

    public function buscarPorCodigo(Request $request)
    {
        $codigo = strtoupper(trim($request->query('codigo', '')));

        // Prefer the record that still doesn't have documento_blob
        $sicd = Sicd::where('codigo_sicd', $codigo)->whereNull('documento_blob')->latest()->first()
             ?? Sicd::where('codigo_sicd', $codigo)->latest()->first();

        if (!$sicd) {
            return response()->json(['encontrado' => false]);
        }

        return response()->json([
            'encontrado'    => true,
            'id'            => $sicd->id,
            'ya_enlazado'   => !empty($sicd->documento_blob),
            'url'           => route('admin.sicd.show', $sicd->id),
            'enlazar_url'   => route('admin.sicd.enlazar-pdf', $sicd->id),
        ]);
    }

    public function crearYEnlazar(Request $request)
    {
        $codigo = strtoupper(trim($request->input('codigo', '')));
        if ($codigo === '') {
            return response()->json(['ok' => false, 'msg' => 'Código requerido.'], 422);
        }

        // Verificar que el código existe en el sistema externo
        try {
            $externa = SicdExterno::buscar($codigo);
        } catch (\Exception $e) {
            return response()->json(['ok' => false, 'msg' => 'No se pudo conectar al sistema externo.'], 500);
        }
        if (!$externa) {
            return response()->json(['ok' => false, 'msg' => 'Código no encontrado en el sistema externo.'], 404);
        }

        // Obtener o crear el SICD en la BD interna
        $sicd = Sicd::where('codigo_sicd', $codigo)->whereNull('documento_blob')->latest()->first()
             ?? Sicd::where('codigo_sicd', $codigo)->latest()->first();

        if (!$sicd) {
            $sicd = Sicd::create([
                'codigo_sicd' => $codigo,
                'estado'      => 'pendiente',
                'usuario_id'  => Auth::id(),
            ]);
        }

        // Enlazar PDF si aún no tiene
        if (!$sicd->documento_blob) {
            try {
                $pdf = SicdExterno::obtenerPdf($codigo);
            } catch (\Exception $e) {
                return response()->json(['ok' => false, 'msg' => 'Error al obtener el PDF externo.'], 500);
            }

            if (!$pdf) {
                return response()->json(['ok' => false, 'msg' => 'No se encontró PDF en el sistema externo.'], 404);
            }

            try {
                \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
                \DB::table('sicds')->where('id', $sicd->id)->update([
                    'documento_blob' => base64_encode($pdf),
                    'documento_mime' => 'application/pdf',
                    'updated_at'     => now(),
                ]);
            } catch (\Exception $e) {
                \Log::error('crearYEnlazar: error guardando blob', ['id' => $sicd->id, 'error' => $e->getMessage()]);
                return response()->json(['ok' => false, 'msg' => 'Error al guardar el documento.'], 500);
            }
        }

        return response()->json([
            'ok'  => true,
            'id'  => $sicd->id,
            'url' => route('admin.sicd.show', $sicd->id),
        ]);
    }

    public function enlazarPdf(int $id)
    {
        $sicd = Sicd::findOrFail($id);

        if ($sicd->documento_blob) {
            return response()->json(['ok' => true, 'ya_tenia' => true, 'msg' => 'Ya tiene documento SICD enlazado.']);
        }

        try {
            $pdf = SicdExterno::obtenerPdf($sicd->codigo_sicd);
        } catch (\Exception $e) {
            \Log::error('enlazarPdf: error externo', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'msg' => 'No se pudo conectar al sistema externo: ' . $e->getMessage()], 500);
        }

        if (!$pdf) {
            return response()->json(['ok' => false, 'msg' => 'No se encontró PDF en el sistema externo para "' . $sicd->codigo_sicd . '".'], 404);
        }

        try {
            $blob = base64_encode($pdf);
            \DB::unprepared('SET GLOBAL max_allowed_packet=67108864');
            \DB::table('sicds')->where('id', $id)->update([
                'documento_blob' => $blob,
                'documento_mime' => 'application/pdf',
                'updated_at'     => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('enlazarPdf: error guardando blob', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'msg' => 'Error al guardar el documento: ' . $e->getMessage()], 500);
        }

        return response()->json(['ok' => true, 'id' => $sicd->id, 'url' => route('admin.sicd.show', $sicd->id)]);
    }

    public function cancelar(int $id)
    {
        $sicd = Sicd::findOrFail($id);
        $sicd->estado = 'cancelado';
        $sicd->save();
        return response()->json(['ok' => true]);
    }

    public function verDocumento(int $id)
    {
        $sicd = Sicd::findOrFail($id);
        abort_unless($sicd->documento_blob, 404);

        $contenido = base64_decode($sicd->documento_blob);
        $nombre    = $sicd->codigo_sicd . '.pdf';
        return response($contenido, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="' . $nombre . '"')
            ->header('Content-Length', strlen($contenido));
    }

    public function descargarExterno(int $id)
    {
        $sicd = Sicd::findOrFail($id);

        $pdf = SicdExterno::obtenerPdf($sicd->codigo_sicd);

        if (!$pdf) {
            return back()->with('error', 'No se encontró documento PDF en el sistema externo para este SICD.');
        }

        $nombre = $sicd->codigo_sicd . '.pdf';
        $nombre = str_replace(['/', '\\', '(', ')'], ['-', '-', '', ''], $nombre);

        return response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Content-Length'      => strlen($pdf),
        ]);
    }
}
