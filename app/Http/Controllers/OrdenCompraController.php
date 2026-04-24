<?php

namespace App\Http\Controllers;

use App\Exceptions\MercadoPublicoException;
use App\Models\Container;
use App\Models\Factura;
use App\Models\GuiaDespacho;
use App\Models\HistorialCambio;
use App\Models\OrdenCompra;
use App\Models\Sicd;
use App\Services\MercadoPublicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class OrdenCompraController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);
        $ordenes = OrdenCompra::with(['usuario', 'sicds', 'factura', 'guia'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.ordenes.index', compact('ordenes'));
    }

    public function create()
    {
        $sicdsPendientes = Sicd::where('estado', 'pendiente')
            ->with('detalles')
            ->orderByDesc('created_at')
            ->get();

        return view('admin.ordenes.crear', compact('sicdsPendientes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_oc'  => ['required', 'string', 'max:100', 'unique:ordenes_compra,numero_oc'],
            'sicd_ids'   => ['required', 'array', 'min:1'],
            'sicd_ids.*' => ['integer', 'exists:sicds,id'],
            'archivo_oc' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'numero_oc.required' => 'El número de OC es obligatorio.',
            'numero_oc.unique'   => 'Ya existe una OC con ese número.',
            'sicd_ids.required'  => 'Debes seleccionar al menos un SICD.',
        ]);

        $rutaOc   = null;
        $nombreOc = null;

        $tempPath = $request->input('archivo_oc_temp');
        if ($tempPath && Storage::disk('local')->exists($tempPath)) {
            // Mover desde temp a destino definitivo
            $nombreOc = basename($tempPath);
            $destino  = 'documentos/oc/' . basename($tempPath);
            Storage::disk('local')->move($tempPath, $destino);
            $rutaOc   = $destino;
        } elseif ($request->hasFile('archivo_oc')) {
            $archivo  = $request->file('archivo_oc');
            $nombreOc = $archivo->getClientOriginalName();
            $rutaOc   = $archivo->store('documentos/oc', 'local');
        }

        DB::transaction(function () use ($data, $rutaOc, $nombreOc) {
            $oc = OrdenCompra::create([
                'numero_oc'      => strtoupper(trim($data['numero_oc'])),
                'archivo_nombre' => $nombreOc,
                'archivo_ruta'   => $rutaOc,
                'estado'         => 'pendiente',
                'usuario_id'     => Auth::id(),
            ]);

            $oc->sicds()->attach($data['sicd_ids']);
            Sicd::whereIn('id', $data['sicd_ids'])->update(['estado' => 'agrupado']);
        });

        $oc = OrdenCompra::where('numero_oc', strtoupper(trim($data['numero_oc'])))->first();

        return redirect()->route('admin.ordenes.show', $oc->id)
            ->with('success', "OC {$oc->numero_oc} creada con " . count($data['sicd_ids']) . " SICD(s).");
    }

    public function show(int $id)
    {
        $oc = OrdenCompra::with(['usuario', 'sicds.detalles.producto', 'factura', 'guia'])->findOrFail($id);

        return view('admin.ordenes.show', compact('oc'));
    }

    /**
     * Sube la factura de la OC (solo una por OC).
     */
    public function subirFactura(int $id, Request $request)
    {
        $oc = OrdenCompra::with('factura')->findOrFail($id);

        if ($oc->estado === 'recibido') {
            return back()->with('error', 'No se pueden subir documentos a una OC ya procesada.');
        }

        if ($oc->factura) {
            return back()->with('error', 'Esta OC ya tiene una factura registrada.');
        }

        $request->validate([
            'factura' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'factura.required' => 'Debes seleccionar el archivo de factura.',
            'factura.mimes'    => 'El archivo debe ser PDF, JPG o PNG.',
        ]);

        $archivo = $request->file('factura');
        $ruta    = $archivo->store("documentos/facturas/{$oc->id}", 'local');

        Factura::create([
            'orden_compra_id' => $oc->id,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta'            => $ruta,
            'subido_por'      => Auth::user()->name,
            'usuario_id'      => Auth::id(),
        ]);

        return back()->with('success', 'Factura subida correctamente.');
    }

    /**
     * Sube la guía de despacho de la OC (solo una por OC, opcional).
     */
    public function subirGuia(int $id, Request $request)
    {
        $oc = OrdenCompra::with('guia')->findOrFail($id);

        if ($oc->guia) {
            return back()->with('error', 'Esta OC ya tiene una guía de despacho registrada.');
        }

        $request->validate([
            'guia' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], [
            'guia.required' => 'Debes seleccionar el archivo de guía de despacho.',
            'guia.mimes'    => 'El archivo debe ser PDF, JPG o PNG.',
        ]);

        $archivo = $request->file('guia');
        $ruta    = $archivo->store("documentos/guias/{$oc->id}", 'local');

        GuiaDespacho::create([
            'orden_compra_id' => $oc->id,
            'nombre_original' => $archivo->getClientOriginalName(),
            'ruta'            => $ruta,
            'subido_por'      => Auth::user()->name,
            'usuario_id'      => Auth::id(),
        ]);

        return back()->with('success', 'Guía de despacho subida correctamente.');
    }

    public function recepcion(int $id)
    {
        $oc = OrdenCompra::with(['sicds.detalles.producto', 'factura'])->findOrFail($id);

        if ($oc->estado !== 'pendiente') {
            return redirect()->route('admin.ordenes.show', $oc->id)
                ->with('error', 'Esta OC ya fue procesada.');
        }

        if (!$oc->tieneFactura()) {
            return redirect()->route('admin.ordenes.show', $oc->id)
                ->with('error', 'Debes subir la factura antes de registrar la recepción.');
        }

        $containers = Container::orderBy('nombre')->get(['id', 'nombre']);

        return view('admin.ordenes.recepcion', compact('oc', 'containers'));
    }

    public function procesarRecepcion(int $id, Request $request)
    {
        $oc = OrdenCompra::with(['sicds.detalles.producto', 'factura'])->findOrFail($id);

        if ($oc->estado !== 'pendiente') {
            return back()->with('error', 'Esta OC ya fue procesada.');
        }

        if (!$oc->tieneFactura()) {
            return back()->with('error', 'Sube la factura antes de procesar la recepción.');
        }

        DB::transaction(function () use ($oc, $request) {
            foreach ($oc->sicds as $sicd) {
                foreach ($sicd->detalles as $detalle) {
                    $recibido = (int) $request->input("recibido.{$detalle->id}", 0);

                    $detalle->cantidad_recibida = $recibido;

                    $precioRaw = preg_replace('/[^0-9]/', '', $request->input("precio_neto.{$detalle->id}", ''));
                    $totalRaw  = preg_replace('/[^0-9]/', '', $request->input("total_neto.{$detalle->id}", ''));
                    if ($precioRaw !== '') {
                        $detalle->precio_neto = (float) $precioRaw;
                    }
                    if ($totalRaw !== '') {
                        $detalle->total_neto = (float) $totalRaw;
                    }

                    $detalle->save();

                    if ($recibido > 0 && $detalle->producto) {
                        $detalle->producto->stock_actual += $recibido;

                        $containerId = $request->input("container.{$detalle->id}");
                        if ($containerId && Container::find((int) $containerId)) {
                            $detalle->producto->contenedor = (int) $containerId;
                        }

                        $detalle->producto->actualizarFechasStock();
                        $detalle->producto->save();

                        $motivoExtra = $request->input("motivo_recepcion.{$detalle->id}");
                        if ($motivoExtra) {
                            $detalle->motivo_recepcion = $motivoExtra;
                            $detalle->save();
                        }
                        $motivoBase = "OC {$oc->numero_oc} – SICD {$sicd->codigo_sicd}";
                        if ($recibido != $detalle->cantidad_solicitada && $motivoExtra) {
                            $motivoBase .= " (solicitado: {$detalle->cantidad_solicitada}, recibido: {$recibido})";
                        }

                        HistorialCambio::create([
                            'producto_id'  => $detalle->producto_id,
                            'contenedor_id'=> $detalle->producto->contenedor,
                            'cantidad'     => $recibido,
                            'tipo'         => 'entrada',
                            'motivo'       => $motivoBase,
                            'aprobado_por' => Auth::user()->name,
                            'usuario_id'   => Auth::id(),
                            'origen'       => 'sicd',
                            'origen_id'    => $sicd->id,
                        ]);
                    }
                }

                $sicd->estado = 'recibido';
                $sicd->save();
            }

            $oc->estado        = 'recibido';
            $oc->procesado_por = Auth::user()->name;
            $oc->procesado_at  = now();
            $oc->save();
        });

        return redirect()->route('admin.ordenes.show', $oc->id)
            ->with('success', "Recepción de OC {$oc->numero_oc} registrada. Stock actualizado.");
    }

    public function descargarFactura(int $id)
    {
        $oc = OrdenCompra::with('factura')->findOrFail($id);
        abort_unless($oc->factura, 404, 'Esta OC no tiene factura.');

        return Storage::disk('local')->download($oc->factura->ruta, $oc->factura->nombre_original);
    }

    public function descargarGuia(int $id)
    {
        $oc = OrdenCompra::with('guia')->findOrFail($id);
        abort_unless($oc->guia, 404, 'Esta OC no tiene guía de despacho.');

        return Storage::disk('local')->download($oc->guia->ruta, $oc->guia->nombre_original);
    }

    public function descargarOc(int $id)
    {
        $oc = OrdenCompra::findOrFail($id);
        abort_unless($oc->archivo_ruta, 404, 'Esta OC no tiene archivo adjunto.');

        return Storage::disk('local')->download($oc->archivo_ruta, $oc->archivo_nombre);
    }

    public function subirArchivoTemp(Request $request)
    {
        try {
            $request->validate(['archivo_oc' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240']);

            $file     = $request->file('archivo_oc');
            $tempPath = $file->store('oc_temp', 'local');
            $fullPath = Storage::disk('local')->path($tempPath);

            $numeroOc = null;

            if (strtolower($file->getClientOriginalExtension()) === 'pdf') {
                $ocr      = new \App\Services\PDFOcrService();
                $texto    = $ocr->extraerTexto($fullPath);
                $numeroOc = $ocr->extraerNumeroOC($fullPath);

                \Illuminate\Support\Facades\Log::info('OCR Debug', [
                    'fullPath'     => $fullPath,
                    'file_exists'  => file_exists($fullPath),
                    'texto_inicio' => $texto ? substr($texto, 0, 500) : 'NULL',
                    'numero_oc'    => $numeroOc,
                ]);
            }

            return response()->json([
                'temp_path' => $tempPath,
                'nombre'    => $file->getClientOriginalName(),
                'numero_oc' => $numeroOc,
            ]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('subirArchivoTemp error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // ── Validación contra API Mercado Público ─────────────────────────────────

    public function validarMercadoPublico(OrdenCompra $orden, MercadoPublicoService $mp)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);

        if ($orden->estado === 'recibido') {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Esta OC ya fue procesada y no puede re-validarse.',
            ], 422);
        }

        $orden->increment('api_intentos');

        try {
            $data = $mp->consultarOC($orden->numero_oc);

            if (!$data) {
                $orden->update(['api_error' => 'OC no encontrada en Mercado Público.']);
                return response()->json([
                    'ok'      => false,
                    'mensaje' => "La OC «{$orden->numero_oc}» no fue encontrada en Mercado Público.",
                    'fila'    => $this->filaParaDataTable($orden->fresh()),
                ]);
            }

            // Verificar que el código retornado coincide
            if (!empty($data['codigo']) && strtoupper($data['codigo']) !== strtoupper($orden->numero_oc)) {
                $msg = "Inconsistencia: la API devolvió el código «{$data['codigo']}» pero se consultó «{$orden->numero_oc}».";
                $orden->update(['api_error' => $msg]);
                return response()->json([
                    'ok'      => false,
                    'mensaje' => $msg,
                    'fila'    => $this->filaParaDataTable($orden->fresh()),
                ], 422);
            }

            $orden->update([
                'estado'                => 'validado',
                'api_codigo'            => $data['codigo'],
                'api_licitacion_codigo' => $data['codigo_licitacion'] ?: null,
                'api_items'             => $data['items'] ?: null,
                'api_nombre'            => $data['nombre'],
                'api_descripcion'       => $data['descripcion'],
                'api_tipo'              => $data['tipo'],
                'api_tipo_moneda'       => $data['tipo_moneda'],
                'api_estado_mp'         => $data['estado'],
                'api_fecha_envio'       => $data['fecha_envio'],
                'api_total'             => $data['total'],
                'api_impuestos'         => $data['impuestos'],
                'api_proveedor_nombre'  => $data['proveedor_nombre'],
                'api_proveedor_rut'     => $data['proveedor_rut'],
                'api_contacto'          => $data['contacto'],
                'api_validado_at'       => now(),
                'api_error'             => null,
            ]);

            return response()->json([
                'ok'      => true,
                'mensaje' => "OC validada correctamente. Proveedor: {$data['proveedor_nombre']}",
                'data'    => $data,
                'fila'    => $this->filaParaDataTable($orden->fresh()),
            ]);

        } catch (MercadoPublicoException $e) {
            $orden->update(['api_error' => $e->getMessage()]);
            return response()->json([
                'ok'      => false,
                'mensaje' => $e->getMessage(),
                'fila'    => $this->filaParaDataTable($orden->fresh()),
            ], 422);
        }
    }

    public function estadoApi()
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);
        $viva = app(MercadoPublicoService::class)->ping();
        return response()->json(['activa' => $viva]);
    }

    // ── Búsqueda inteligente para el formulario de nueva OC ──────────────────

    public function buscarEnMercadoPublico(Request $request, MercadoPublicoService $mp)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);
        $request->validate(['codigo' => ['required', 'string', 'max:150']]);

        $codigo  = strtoupper(trim($request->input('codigo')));
        $sicdIds = array_filter((array) $request->input('sicd_ids', []), 'is_numeric');

        // Cargar SICDs para comparación de montos (opcionales)
        $sicdResumen = [];
        $totalSicd   = 0;
        $itemCount   = 0;

        if (!empty($sicdIds)) {
            $sicds = Sicd::with('detalles')->whereIn('id', $sicdIds)->get();
            foreach ($sicds as $sicd) {
                $parcial    = (float) $sicd->detalles->sum('total_neto');
                $totalSicd += $parcial;
                $itemCount += $sicd->detalles->count();
                $sicdResumen[] = [
                    'codigo'    => $sicd->codigo_sicd,
                    'productos' => $sicd->detalles->count(),
                    'total'     => $parcial,
                ];
            }
        }

        try {
            $resultado    = $mp->consultarCualquierCodigo($codigo);
            $encontrado   = $resultado['encontrado'];
            $tipoBusqueda = $resultado['tipo_busqueda']; // 'oc' | 'licitacion' | null

            if (!$encontrado) {
                return response()->json([
                    'encontrado'   => false,
                    'mensaje'      => "«{$codigo}» no fue encontrado en Mercado Público (ni como OC ni como licitación).",
                    'sicd_resumen' => $sicdResumen,
                    'total_sicd'   => $totalSicd,
                    'item_count'   => $itemCount,
                ]);
            }

            $data     = $resultado['data'];
            $tipoInfo = $mp->detectarTipoProceso($data['tipo'] ?? '');

            // Clasificación basada en UTM actual (solo para OC con total neto)
            if ($tipoBusqueda === 'oc' && !empty($data['total_neto'])) {
                $utm = $this->getUTMActual();
                if ($utm) {
                    if ($data['total_neto'] < $utm * 100) {
                        $tipoInfo = ['label' => 'Compra Ágil', 'icono' => '⚡', 'clase' => 'bg-green-100 text-green-800 border-green-200'];
                    } else {
                        $tipoInfo = ['label' => 'Orden de Compra', 'icono' => '📋', 'clase' => 'bg-indigo-100 text-indigo-800 border-indigo-200'];
                    }
                }
            }

            // Para comparación usamos el total disponible según tipo
            $apiTotal = $tipoBusqueda === 'oc'
                ? ($data['total']            ?? null)
                : ($data['total_adjudicado'] ?? null);

            $coincide = $apiTotal && $totalSicd > 0
                && abs($totalSicd - $apiTotal) <= ($apiTotal * 0.05);

            return response()->json([
                'encontrado'    => true,
                'tipo_busqueda' => $tipoBusqueda,
                'codigo_oc'     => $resultado['codigo_oc'],
                'codigo_lic'    => $resultado['codigo_lic'],
                'tipo_info'     => $tipoInfo,
                'api_data'      => $data,
                'sicd_resumen'  => $sicdResumen,
                'total_sicd'    => $totalSicd,
                'item_count'    => $itemCount,
                'comparacion'   => [
                    'total_mp'   => $apiTotal,
                    'total_sicd' => $totalSicd,
                    'coincide'   => $coincide,
                ],
            ]);

        } catch (MercadoPublicoException $e) {
            return response()->json([
                'encontrado'   => false,
                'error'        => true,
                'mensaje'      => $e->getMessage(),
                'sicd_resumen' => $sicdResumen,
                'total_sicd'   => $totalSicd,
                'item_count'   => $itemCount,
            ], 422);
        }
    }

    private function getUTMActual(): ?float
    {
        return Cache::remember('utm_actual', now()->addHours(6), function () {
            try {
                $r = Http::withOptions(['verify' => false])->timeout(6)->get('https://mindicador.cl/api/utm');
                if ($r->ok()) {
                    $serie = $r->json('serie', []);
                    if (!empty($serie[0]['valor'])) {
                        return (float) $serie[0]['valor'];
                    }
                }
            } catch (\Exception) {}
            return null;
        });
    }

    private function filaParaDataTable(OrdenCompra $oc): array
    {
        return [
            'id'                    => $oc->id,
            'numero_oc'             => $oc->numero_oc,
            'estado'                => $oc->estado,
            'api_proveedor_nombre'  => $oc->api_proveedor_nombre,
            'api_estado_mp'         => $oc->api_estado_mp,
            'api_total_fmt'         => $oc->totalFormateado(),
            'api_validado_at'       => $oc->api_validado_at?->format('d/m/Y H:i'),
            'api_error'             => $oc->api_error,
            'api_intentos'          => $oc->api_intentos,
            'api_licitacion_codigo' => $oc->api_licitacion_codigo,
        ];
    }
}
