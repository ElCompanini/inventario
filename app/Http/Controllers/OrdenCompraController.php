<?php

namespace App\Http\Controllers;

use App\Exceptions\MercadoPublicoException;
use App\Models\Container;
use App\Models\Factura;
use App\Models\GuiaDespacho;
use App\Models\HistorialCambio;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Precio;
use App\Models\Producto;
use App\Models\Sicd;
use App\Models\SicdDetalle;
use App\Models\SicdExterno;
use App\Services\MercadoPublicoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrdenCompraController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);

        $user  = auth()->user();
        $filtroFactura = $request->query('factura');
        $query = OrdenCompra::with(['usuario', 'sicds', 'factura', 'guia'])
            ->orderByDesc('created_at');

        if ($filtroFactura === 'pendiente') {
            $query->whereDoesntHave('factura')
                ->whereNotIn('estado', ['recibido', 'cerrado', 'cancelado', 'anulado']);
        }

        if ($user->tieneFiltroCC()) {
            $prefix = strtoupper($user->centroCostoPrefix());
            $query->whereHas('sicds', function ($q) use ($prefix) {
                $q->whereRaw("REGEXP_REPLACE(codigo_sicd, '[^A-Za-z].*', '') = ?", [$prefix]);
            });
        }

        $ordenes = $query->paginate(20);

        $codigosSicd     = $ordenes->flatMap(fn($oc) => $oc->sicds->pluck('codigo_sicd'))->unique()->values()->toArray();
        $estadosExternos = SicdExterno::estadosBulk($codigosSicd);

        return view('admin.ordenes.index', compact('ordenes', 'estadosExternos', 'filtroFactura'));
    }

    public function create()
    {
        $sicdsPendientes = Sicd::where(function ($q) {
                $q->where('estado', 'pendiente')
                  ->orWhere(function ($q2) {
                      $q2->where('permite_mas_oc', true)
                         ->whereNotIn('estado', ['cancelado']);
                  });
            })
            ->with(['detalles.ocDetalles'])
            ->withCount(['detalles as pendientes_count' => fn($q) => $q->where('clasificacion_pendiente', true)])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.ordenes.crear', compact('sicdsPendientes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_oc'                       => ['required', 'string', 'max:100'],
            'sicd_ids'                        => ['required', 'array', 'min:1'],
            'sicd_ids.*'                      => ['integer', 'exists:sicds,id'],
            'archivo_oc'                      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'oc_detalles'                     => ['nullable', 'array'],
            'oc_detalles.*.sicd_detalle_id'   => ['required', 'integer', 'exists:sicd_detalles,id'],
            'oc_detalles.*.cantidad_asignada' => ['required', 'integer', 'min:1'],
            'oc_detalles.*.precio_neto'       => ['nullable', 'numeric', 'min:0'],
            'oc_detalles.*.total_neto'        => ['nullable', 'numeric', 'min:0'],
        ], [
            'numero_oc.required'                  => 'El número de OC es obligatorio.',
            'sicd_ids.required'                   => 'Debes seleccionar al menos un SICD.',
            'oc_detalles.*.cantidad_asignada.min' => 'La cantidad debe ser al menos 1.',
        ]);

        // Si la OC ya existe (doble envío o navegación hacia atrás), redirigir a ella
        $ocExistente = OrdenCompra::where('numero_oc', strtoupper(trim($data['numero_oc'])))->first();
        if ($ocExistente) {
            return redirect()->route('admin.ordenes.show', $ocExistente->id)
                ->with('info', "La OC {$ocExistente->numero_oc} ya estaba registrada.");
        }

        $ocDetalles = $data['oc_detalles'] ?? [];

        // Rechazar si alguno de los detalles SELECCIONADOS es pendiente (integridad — no debería ocurrir desde la UI)
        if (!empty($ocDetalles)) {
            $selectedDetalleIds = array_column($ocDetalles, 'sicd_detalle_id');
            $detallePendiente = SicdDetalle::whereIn('id', $selectedDetalleIds)
                ->where('clasificacion_pendiente', true)
                ->first();

            if ($detallePendiente) {
                return back()->withInput()->withErrors([
                    'oc_detalles' => "El ítem «{$detallePendiente->nombre_producto_excel}» está pendiente de clasificación y no puede incluirse en una OC.",
                ]);
            }
        }

        // Validar que las cantidades no excedan el disponible en cada detalle
        foreach ($ocDetalles as $det) {
            $detalle = SicdDetalle::with('ocDetalles')->findOrFail($det['sicd_detalle_id']);
            $disponible = $detalle->cantidad_disponible;
            if ((int) $det['cantidad_asignada'] > $disponible) {
                return back()->withErrors([
                    'oc_detalles' => "El producto «{$detalle->nombre_producto_excel}» solo tiene {$disponible} unidad(es) disponible(s).",
                ])->withInput();
            }
        }

        $rutaOc   = null;
        $nombreOc = null;

        $tempPath = $request->input('archivo_oc_temp');
        if ($tempPath) {
            // SEC-01: solo aceptar paths generados por subirArchivoTemp() deben estar en oc_temp/
            $safePath = ltrim(str_replace('\\', '/', $tempPath), '/');
            if (
                str_starts_with($safePath, 'oc_temp/') &&
                !str_contains($safePath, '..') &&
                preg_match('/^oc_temp\/[A-Za-z0-9_\-\.]+$/', $safePath) &&
                Storage::disk('local')->exists($safePath)
            ) {
                $nombreOc = basename($safePath);
                $destino  = 'documentos/oc/' . basename($safePath);
                Storage::disk('local')->move($safePath, $destino);
                $rutaOc   = $destino;
            }
        } elseif ($request->hasFile('archivo_oc')) {
            $archivo  = $request->file('archivo_oc');
            $nombreOc = $archivo->getClientOriginalName();
            $rutaOc   = $archivo->store('documentos/oc', 'local');
        }

        $permiteMasOc = $request->boolean('permite_mas_oc');

        DB::transaction(function () use ($data, $ocDetalles, $rutaOc, $nombreOc, $permiteMasOc) {
            $oc = OrdenCompra::create([
                'numero_oc'      => strtoupper(trim($data['numero_oc'])),
                'archivo_nombre' => $nombreOc,
                'archivo_ruta'   => $rutaOc,
                'estado'         => 'pendiente',
                'usuario_id'     => Auth::id(),
            ]);

            $oc->sicds()->attach($data['sicd_ids']);

            Sicd::whereIn('id', $data['sicd_ids'])->update([
                'estado'         => 'agrupado',
                'permite_mas_oc' => $permiteMasOc,
            ]);

            // Guardar asignación granular de productos a esta OC (puede ser vacío si todos son pendientes)
            foreach ($ocDetalles as $det) {
                OrdenCompraDetalle::create([
                    'orden_compra_id'  => $oc->id,
                    'sicd_detalle_id'  => $det['sicd_detalle_id'],
                    'cantidad_asignada'=> (int) $det['cantidad_asignada'],
                    'precio_neto'      => isset($det['precio_neto']) && $det['precio_neto'] !== '' ? (float) $det['precio_neto'] : null,
                    'total_neto'       => isset($det['total_neto'])  && $det['total_neto']  !== '' ? (float) $det['total_neto']  : null,
                ]);
            }
        });

        $oc = OrdenCompra::where('numero_oc', strtoupper(trim($data['numero_oc'])))->first();

        // Auto-validar contra Mercado Público al crear
        try {
            $mp   = app(MercadoPublicoService::class);
            $mpData = $mp->consultarOC($oc->numero_oc);

            if ($mpData && (empty($mpData['codigo']) || strtoupper($mpData['codigo']) === strtoupper($oc->numero_oc))) {
                $clasificacion = $this->clasificarTipoAdquisicion($mpData, 'oc', $oc->numero_oc);

                DB::transaction(function () use ($oc, $mpData, $clasificacion) {
                    $locked = OrdenCompra::whereKey($oc->id)->lockForUpdate()->firstOrFail();
                    $locked->increment('api_intentos');
                    $locked->update([
                        'estado'                => 'validado',
                        'api_codigo'            => $mpData['codigo'],
                        'api_licitacion_codigo' => $mpData['codigo_licitacion'] ?: null,
                        'api_items'             => $mpData['items'] ?: null,
                        'api_nombre'            => $mpData['nombre'],
                        'api_descripcion'       => $mpData['descripcion'],
                        'api_tipo'              => $mpData['tipo'],
                        'api_tipo_moneda'       => $mpData['tipo_moneda'],
                        'api_estado_mp'         => $mpData['estado'],
                        'api_fecha_envio'       => $mpData['fecha_envio'],
                        'api_total'             => $mpData['total'],
                        'api_impuestos'         => $mpData['impuestos'],
                        'api_proveedor_nombre'  => $mpData['proveedor_nombre'],
                        'api_proveedor_rut'     => $mpData['proveedor_rut'],
                        'api_contacto'          => $mpData['contacto'],
                        'api_validado_at'       => now(),
                        'api_error'             => null,
                        'mp_estado_proceso'     => 'adjuntado',
                        'mp_error_at'           => null,
                        ...$this->clasificacionAplicable($locked, $clasificacion),
                    ]);
                });

                $this->registrarLogTipoAdquisicion($oc->fresh(), $clasificacion, 'auto_validacion_creacion');
            }
        } catch (\Throwable $e) {
            \Log::warning('mercado_publico_auto_validacion_error', [
                'orden_compra_id' => $oc->id ?? null,
                'numero_oc' => $oc->numero_oc ?? null,
                'usuario_id' => Auth::id(),
                'estado_proceso' => 'reintento_pendiente',
                'error' => $e->getMessage(),
                'fecha_hora' => now()->toDateTimeString(),
            ]);
            // Si la API falla, la OC queda pendiente y se puede validar manualmente
        }

        return redirect()->route('admin.ordenes.show', $oc->id)
            ->with('success', "OC {$oc->numero_oc} creada con " . count($data['sicd_ids']) . " SICD(s).");
    }

    public function show(int $id)
    {
        $oc = OrdenCompra::with([
            'usuario',
            'sicds.detalles.producto',
            'detalles.sicdDetalle',   // incluir sicdDetalle para detectar items sin producto
            'factura',
            'guia',
        ])->findOrFail($id);

        // IDs de sicd_detalles asignados a ESTA OC
        $idsEstaOc = $oc->detalles->pluck('sicd_detalle_id')->toArray();

        // Mapa sicd_detalle_id → OrdenCompraDetalle (para el botón "Marcar pendiente")
        $ocDetallePorSicdDet = $oc->detalles->keyBy('sicd_detalle_id');

        // Ítems ya marcados como pendientes de esta OC (sicd_detalle_id → motivo)
        $pendientesOcItems = \App\Models\OcItemPendiente::where('orden_compra_id', $oc->id)
            ->whereNull('resuelto_at')
            ->whereNull('deleted_at')
            ->pluck('motivo', 'sicd_detalle_id');

        // Para sicd_detalles de este mismo SICD que están en OTRA OC, obtener el número de OC
        $allSicdDetalleIds = $oc->sicds
            ->flatMap(fn($s) => $s->detalles->pluck('id'))
            ->toArray();

        $otraOcPorDetalle = OrdenCompraDetalle::whereNotIn('orden_compra_id', [$oc->id])
            ->whereIn('sicd_detalle_id', $allSicdDetalleIds)
            ->with('ordenCompra:id,numero_oc')
            ->get()
            ->groupBy('sicd_detalle_id')
            ->map(fn($g) => $g->first()->ordenCompra->numero_oc ?? '—');

        // Items en oc_detalles sin producto enlazado y sin OcItemPendiente (solo OCs nuevas con detalles)
        $ocDetSinProducto = 0;
        if (count($idsEstaOc) > 0) {
            $pendientesIds = $pendientesOcItems->keys()->toArray();
            $ocDetSinProducto = $oc->detalles->filter(fn($d) =>
                !$d->sicdDetalle?->producto_id && !in_array($d->sicd_detalle_id, $pendientesIds)
            )->count();
        }

        return view('admin.ordenes.show', compact(
            'oc', 'idsEstaOc', 'otraOcPorDetalle', 'ocDetallePorSicdDet',
            'pendientesOcItems', 'ocDetSinProducto'
        ));
    }

    /**
     * Sube la factura de la OC (solo una por OC).
     */
    public function subirFactura(int $id, Request $request)
    {
        $request->validate([
            'factura' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
        ], [
            'factura.required' => 'Debes seleccionar el archivo de factura.',
            'factura.mimes'    => 'El archivo debe ser PDF, JPG o PNG.',
        ]);

        $archivo = $request->file('factura');
        $ruta = null;

        try {
            $ruta = $this->guardarArchivoDocumento($archivo, "documentos/facturas/{$id}");

            DB::transaction(function () use ($id, $archivo, $ruta) {
                $oc = OrdenCompra::whereKey($id)->lockForUpdate()->firstOrFail();
                $oc->load('factura');

                if ($oc->estado === 'recibido') {
                    throw new \RuntimeException('No se pueden subir documentos a una OC ya procesada.');
                }

                if ($oc->factura) {
                    throw new \RuntimeException('Esta OC ya tiene una factura registrada.');
                }

                Factura::create([
                    'orden_compra_id' => $oc->id,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'ruta'            => $ruta,
                    'documento_estado'=> 'adjuntado',
                    'documento_error' => null,
                    'subido_por'      => Auth::user()->name,
                    'usuario_id'      => Auth::id(),
                ]);

                $oc->forceFill([
                    'factura_pendiente'     => false,
                    'factura_pendiente_at'  => null,
                    'factura_pendiente_por' => null,
                ])->save();
            });

            $oc = OrdenCompra::with('factura')->findOrFail($id);
            if (!$oc->factura || !Storage::disk('local')->exists($oc->factura->ruta)) {
                throw new \RuntimeException('La factura no quedo persistida correctamente.');
            }

            \Log::info('documento_oc_adjuntado', [
                'accion' => 'factura_adjuntada',
                'orden_compra_id' => $oc->id,
                'numero_oc' => $oc->numero_oc,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now()->toDateTimeString(),
                'archivo' => $oc->factura->nombre_original,
            ]);

            return back()->with('success', 'Factura subida y verificada correctamente.');
        } catch (\Throwable $e) {
            if ($ruta) {
                Factura::where('orden_compra_id', $id)->where('ruta', $ruta)->delete();
            }

            if ($ruta && Storage::disk('local')->exists($ruta)) {
                Storage::disk('local')->delete($ruta);
            }

            \Log::error('subirFactura ERROR', [
                'orden_compra_id' => $id,
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo subir la factura: ' . $e->getMessage());
        }
    }

    public function marcarFacturaPendiente(int $id)
    {
        $oc = OrdenCompra::with([
            'factura',
            'detalles.sicdDetalle.producto',
            'detalles.sicdDetalle.sicd.detalles',
        ])->findOrFail($id);

        if ($oc->estado === 'recibido') {
            return back()->with('error', 'La OC ya fue procesada; no puede quedar con factura pendiente.');
        }

        if ($oc->factura) {
            return back()->with('info', 'Esta OC ya tiene factura adjunta.');
        }

        // Verificar items sin producto y sin OcItemPendiente (solo OCs nuevas con oc_detalles)
        if ($oc->detalles->isNotEmpty()) {
            $pendientesIds = \App\Models\OcItemPendiente::where('orden_compra_id', $oc->id)
                ->whereNull('resuelto_at')
                ->whereNull('deleted_at')
                ->pluck('sicd_detalle_id')
                ->toArray();
            $sinDefinir = $oc->detalles->filter(fn($d) =>
                !$d->sicdDetalle?->producto_id && !in_array($d->sicd_detalle_id, $pendientesIds)
            )->count();
            if ($sinDefinir > 0) {
                return back()->with('error',
                    "Hay {$sinDefinir} producto(s) sin definir en esta OC. " .
                    'Debe marcar cada ítem como pendiente (botón ⚠ en la tabla de ítems) antes de continuar.'
                );
            }
        }

        try {
            DB::transaction(function () use ($id, &$oc) {
                $locked = OrdenCompra::whereKey($id)->lockForUpdate()->firstOrFail();
                $locked->load([
                    'factura',
                    'detalles.sicdDetalle.producto',
                    'detalles.sicdDetalle.sicd.detalles',
                ]);

                if ($locked->estado === 'recibido') {
                    throw new \RuntimeException('La OC ya fue procesada; no puede quedar con factura pendiente.');
                }

                if ($locked->factura) {
                    throw new \RuntimeException('Esta OC ya tiene factura adjunta.');
                }

                if ($locked->estado !== 'validado') {
                    throw new \RuntimeException('La OC debe estar validada en Mercado Público antes de procesar la recepción.');
                }

                $locked->forceFill([
                    'factura_pendiente'     => true,
                    'factura_pendiente_at'  => now(),
                    'factura_pendiente_por' => Auth::id(),
                ])->save();

                $this->recibirOcConFacturaPendiente($locked);
                $oc = $locked->fresh(['factura']);
            });
        } catch (\Throwable $e) {
            \Log::error('factura_pendiente_stock_error', [
                'orden_compra_id' => $id,
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo recibir la OC con factura pendiente: ' . $e->getMessage());
        }

        \Log::warning('factura_pendiente_oc', [
            'accion' => 'factura_pendiente',
            'orden_compra_id' => $oc->id,
            'numero_oc' => $oc->numero_oc,
            'usuario_id' => Auth::id(),
            'fecha_hora' => now()->toDateTimeString(),
        ]);

        return redirect()
            ->route('dashboard')
            ->with('success', 'OC recibida con factura pendiente. Stock actualizado; podrás adjuntar la factura después.');
    }

    /**
     * Sube la guia de despacho de la OC (solo una por OC, opcional).
     */
    public function subirGuia(int $id, Request $request)
    {
        $request->validate([
            'guia' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:15360'],
        ], [
            'guia.required' => 'Debes seleccionar el archivo de guia de despacho.',
            'guia.mimes'    => 'El archivo debe ser PDF, JPG o PNG.',
        ]);

        $archivo = $request->file('guia');
        $ruta = null;

        try {
            $ruta = $this->guardarArchivoDocumento($archivo, "documentos/guias/{$id}");

            DB::transaction(function () use ($id, $archivo, $ruta) {
                $oc = OrdenCompra::whereKey($id)->lockForUpdate()->firstOrFail();
                $oc->load('guia');

                if ($oc->guia) {
                    throw new \RuntimeException('Esta OC ya tiene una guia de despacho registrada.');
                }

                GuiaDespacho::create([
                    'orden_compra_id' => $oc->id,
                    'nombre_original' => $archivo->getClientOriginalName(),
                    'ruta'            => $ruta,
                    'documento_estado'=> 'adjuntado',
                    'documento_error' => null,
                    'subido_por'      => Auth::user()->name,
                    'usuario_id'      => Auth::id(),
                ]);
            });

            $oc = OrdenCompra::with('guia')->findOrFail($id);
            if (!$oc->guia || !Storage::disk('local')->exists($oc->guia->ruta)) {
                throw new \RuntimeException('La guia no quedo persistida correctamente.');
            }

            \Log::info('documento_oc_adjuntado', [
                'accion' => 'guia_adjuntada',
                'orden_compra_id' => $oc->id,
                'numero_oc' => $oc->numero_oc,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now()->toDateTimeString(),
                'archivo' => $oc->guia->nombre_original,
            ]);

            return back()->with('success', 'Guia de despacho subida y verificada correctamente.');
        } catch (\Throwable $e) {
            if ($ruta) {
                GuiaDespacho::where('orden_compra_id', $id)->where('ruta', $ruta)->delete();
            }

            if ($ruta && Storage::disk('local')->exists($ruta)) {
                Storage::disk('local')->delete($ruta);
            }

            \Log::error('subirGuia ERROR', [
                'orden_compra_id' => $id,
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'No se pudo subir la guia: ' . $e->getMessage());
        }
    }
    public function recepcion(int $id)
    {
        $oc = OrdenCompra::with([
            'detalles.sicdDetalle.producto',
            'detalles.sicdDetalle.sicd',
            'factura',
        ])->findOrFail($id);

        if ($oc->estado === 'recibido') {
            return redirect()->route('admin.ordenes.show', $oc->id)
                ->with('error', 'Esta OC ya fue procesada.');
        }

        if ($oc->estado !== 'validado') {
            return redirect()->route('admin.ordenes.show', $oc->id)
                ->with('error', 'La OC debe estar validada en Mercado Público antes de poder recepcionar.');
        }

        if (!$oc->tieneFactura()) {
            return redirect()->route('admin.ordenes.show', $oc->id)
                ->with('error', 'Debes subir la factura antes de registrar la recepción.');
        }

        $containers = Container::with('centroCosto')->orderBy('nombre')->get(['id', 'nombre', 'centro_costo_id']);

        $ocPendientesCount = \App\Models\OcItemPendiente::where('orden_compra_id', $oc->id)
            ->whereNull('resuelto_at')
            ->whereNull('deleted_at')
            ->count();

        return view('admin.ordenes.recepcion', compact('oc', 'containers', 'ocPendientesCount'));
    }

    public function procesarRecepcion(int $id, Request $request)
    {
        try {
        $oc = OrdenCompra::with([
            'detalles.sicdDetalle.producto',
            'detalles.sicdDetalle.sicd',
            'factura',
            'guia',
        ])->findOrFail($id);

        if ($oc->estado === 'recibido') {
            return back()->with('error', 'Esta OC ya fue procesada.');
        }

        if ($oc->estado !== 'validado') {
            return back()->with('error', 'La OC debe estar validada en Mercado Público antes de procesar la recepción.');
        }

        if (!$oc->tieneFactura()) {
            return back()->with('error', 'Sube la factura antes de procesar la recepción.');
        }

        $rules = [];
        foreach ($oc->detalles as $ocDetalle) {
            $rules["recibido.{$ocDetalle->id}"] = [
                'required',
                'integer',
                'min:0',
                'max:' . (int) $ocDetalle->cantidad_asignada,
            ];
            $rules["precio_neto.{$ocDetalle->id}"] = ['nullable', 'string', 'max:50'];
            $rules["total_neto.{$ocDetalle->id}"] = ['nullable', 'string', 'max:50'];
            $rules["container.{$ocDetalle->id}"] = ['nullable', 'integer'];
            $rules["motivo_recepcion.{$ocDetalle->id}"] = ['nullable', 'string', 'max:500'];
        }
        $request->validate($rules);

        $userName = Auth::user()->name;
        $userId   = Auth::id();

        DB::transaction(function () use ($id, $request, $userName, $userId, &$oc) {
            $oc = OrdenCompra::whereKey($id)->lockForUpdate()->firstOrFail();
            $oc->load([
                'detalles.sicdDetalle.producto',
                'detalles.sicdDetalle.sicd',
                'factura',
                'guia',
            ]);

            if ($oc->estado === 'recibido') {
                throw new \RuntimeException('Esta OC ya fue procesada.');
            }

            if ($oc->estado !== 'validado') {
                throw new \RuntimeException('La OC debe estar validada en Mercado Publico antes de procesar la recepcion.');
            }

            if (!$oc->tieneFactura()) {
                throw new \RuntimeException('Sube la factura antes de procesar la recepcion.');
            }

            // Agrupar los oc_detalles por SICD para poder actualizar el estado de cada SICD
            $sicdIds = [];

            foreach ($oc->detalles as $ocDetalle) {
                $detalle = $ocDetalle->sicdDetalle;
                $sicd    = $detalle->sicd;
                $recibido = (int) $request->input("recibido.{$ocDetalle->id}", 0);

                if ($recibido < 0 || $recibido > (int) $ocDetalle->cantidad_asignada) {
                    throw ValidationException::withMessages([
                        "recibido.{$ocDetalle->id}" => 'La cantidad recibida no puede ser negativa ni superar la cantidad asignada.',
                    ]);
                }

                $precioRaw = preg_replace('/[^0-9]/', '', $request->input("precio_neto.{$ocDetalle->id}", ''));
                $totalRaw  = preg_replace('/[^0-9]/', '', $request->input("total_neto.{$ocDetalle->id}", ''));

                // Guardar lo recibido en oc_detalle (para multi-OC: cada OC registra su parte)
                // NOTA: NO se sobreescriben precio_neto/total_neto del sicd_detalle.
                // esos campos conservan el valor referencial original de la SICD para trazabilidad.
                $ocDetalle->cantidad_recibida = $recibido;
                if ($precioRaw !== '') {
                    $ocDetalle->precio_neto = (float) $precioRaw;
                }
                if ($totalRaw !== '') {
                    $ocDetalle->total_neto = (float) $totalRaw;
                }
                $ocDetalle->save();

                // Calcular total recibido en sicd_detalle como suma de TODOS los oc_detalles
                // (correcto para multi-OC: cada OC suma su parte, no se acumula sobre valor anterior)
                $recibidoOtrosOcs = OrdenCompraDetalle::where('sicd_detalle_id', $detalle->id)
                    ->where('orden_compra_id', '!=', $oc->id)
                    ->whereNotNull('cantidad_recibida')
                    ->sum('cantidad_recibida');

                $detalle->cantidad_recibida = $recibidoOtrosOcs + $recibido;
                $detalle->save();

                $sicdIds[$sicd->id] = $sicd;

                if ($recibido > 0 && $detalle->producto_id) {
                    $producto = Producto::withoutGlobalScopes()
                        ->whereKey($detalle->producto_id)
                        ->lockForUpdate()
                        ->first();

                    if (!$producto) {
                        continue;
                    }

                    $stockAntes = $producto->stock_actual;
                    $producto->stock_actual += $recibido;

                    $containerId = $request->input("container.{$ocDetalle->id}");
                    if ($containerId && Container::withoutGlobalScope('con_cc')->find((int) $containerId)) {
                        $producto->contenedor = (int) $containerId;
                    }

                    $producto->actualizarFechasStock();
                    $producto->save();

                    $motivoExtra = $request->input("motivo_recepcion.{$ocDetalle->id}");
                    if ($motivoExtra) {
                        $detalle->motivo_recepcion = $motivoExtra;
                        $detalle->save();
                    }
                    $motivoBase = "OC {$oc->numero_oc} - SICD {$sicd->codigo_sicd}";
                    if ($recibido != $ocDetalle->cantidad_asignada && $motivoExtra) {
                        $motivoBase .= " (asignado: {$ocDetalle->cantidad_asignada}, recibido: {$recibido})";
                    }

                    HistorialCambio::create([
                        'producto_id'        => $detalle->producto_id,
                        'nombre_producto'    => $producto->nombre,
                        'contenedor_id'      => $producto->contenedor,
                        'cantidad'           => $recibido,
                        'tipo'               => 'entrada',
                        'motivo'             => $motivoBase,
                        'aprobado_por'       => $userName,
                        'usuario_id'         => $userId,
                        'origen'             => 'sicd',
                        'origen_id'          => $sicd->id,
                        'origen_tipo'        => 'sicd',
                        'orden_compra_id'    => $oc->id,
                        'doc_origen'         => 'OC ' . $oc->numero_oc,
                        'doc_referencia'     => 'SICD ' . $sicd->codigo_sicd,
                        'stock_anterior'     => $stockAntes,
                        'stock_posterior'    => $producto->stock_actual,
                        'usuario_ejecutor_id'=> $userId,
                    ]);

                    $precioNeto = $ocDetalle->precio_neto ?? $detalle->precio_neto;
                    $totalNeto  = $ocDetalle->total_neto  ?? $detalle->total_neto;
                    if ($precioNeto && $precioNeto > 0) {
                        Precio::registrar(
                            producto:      $producto,
                            precioNeto:    (float) $precioNeto,
                            cantidad:      $recibido,
                            fuente:        'sicd_masiva',
                            origenId:      $sicd->id,
                            origenTipo:    'Sicd',
                            precioTotal:   $totalNeto ? (float) $totalNeto : null,
                            notas:         "OC {$oc->numero_oc} · SICD {$sicd->codigo_sicd}",
                            usuarioId:     $userId,
                            ordenCompraId: $oc->id,
                        );
                    }
                }
            }

            // Marcar la OC actual como recibida
            $oc->estado        = 'recibido';
            $oc->procesado_por = $userName;
            $oc->procesado_at  = now();
            $oc->save();

            // Actualizar estado de cada SICD involucrado y auto-cerrar OCs hermanas
            foreach ($sicdIds as $sicd) {
                // ¿Están todos los sicd_detalles completamente recibidos?
                $sicdCompleto = !$sicd->detalles()
                    ->whereRaw('COALESCE(cantidad_recibida, 0) < cantidad_solicitada')
                    ->exists();

                if ($sicdCompleto) {
                    $sicd->estado = 'recibido';
                    // Auto-cerrar OCs hermanas que aún estén pendientes
                    OrdenCompra::whereHas('sicds', fn($q) => $q->where('sicds.id', $sicd->id))
                        ->where('id', '!=', $oc->id)
                        ->whereNotIn('estado', ['recibido'])
                        ->update([
                            'estado'        => 'recibido',
                            'procesado_por' => $userName,
                            'procesado_at'  => now(),
                        ]);
                } else {
                    $sicd->estado = 'agrupado';
                }
                $sicd->save();
            }
        });

        return redirect()->route('admin.ordenes.show', $oc->id)
            ->with('success', "Recepción de OC {$oc->numero_oc} registrada. Stock actualizado.");

        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            \Log::error('procesarRecepcion ERROR', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
            ]);
            return back()->with('error', 'Error al procesar la recepción: ' . $e->getMessage());
        }
    }

    public function descargarFactura(int $id)
    {
        $oc = OrdenCompra::with('factura')->findOrFail($id);
        abort_unless($oc->factura, 404, 'Esta OC no tiene factura.');
        abort_unless(Storage::disk('local')->exists($oc->factura->ruta), 404, 'El archivo de factura no existe en almacenamiento.');

        return Storage::disk('local')->download($oc->factura->ruta, $oc->factura->nombre_original);
    }

    public function descargarGuia(int $id)
    {
        $oc = OrdenCompra::with('guia')->findOrFail($id);
        abort_unless($oc->guia, 404, 'Esta OC no tiene guía de despacho.');
        abort_unless(Storage::disk('local')->exists($oc->guia->ruta), 404, 'El archivo de guia no existe en almacenamiento.');

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

    // Validación contra API Mercado Público

    public function validarMercadoPublico(OrdenCompra $orden, MercadoPublicoService $mp)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);

        if ($orden->estado === 'recibido') {
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Esta OC ya fue procesada y no puede re-validarse.',
            ], 422);
        }

        DB::transaction(function () use ($orden) {
            $locked = OrdenCompra::whereKey($orden->id)->lockForUpdate()->firstOrFail();
            $locked->forceFill([
                'mp_estado_proceso' => 'procesando',
                'api_error' => null,
            ])->save();
            $locked->increment('api_intentos');
        });

        try {
            $data = null;
            $intentos = 0;
            while ($intentos <= 3) {
                try {
                    $data = $mp->consultarOC($orden->numero_oc);
                    break;
                } catch (MercadoPublicoException $e) {
                    if ($intentos < 3 && str_contains($e->getMessage(), 'simult')) {
                        $intentos++;
                        usleep(1_000_000 * $intentos);
                        continue;
                    }
                    throw $e;
                }
            }

            if (!$data) {
                $orden = $this->registrarErrorMercadoPublico($orden->id, 'OC no encontrada en Mercado Publico.', 'reintento_pendiente');
                return response()->json([
                    'ok'      => false,
                    'mensaje' => "La OC {$orden->numero_oc} no fue encontrada en Mercado Publico.",
                    'fila'    => $this->filaParaDataTable($orden),
                ]);
            }

            if (!empty($data['codigo']) && strtoupper($data['codigo']) !== strtoupper($orden->numero_oc)) {
                $msg = "Inconsistencia: la API devolvio el codigo {$data['codigo']} pero se consulto {$orden->numero_oc}.";
                $orden = $this->registrarErrorMercadoPublico($orden->id, $msg, 'error');
                return response()->json([
                    'ok'      => false,
                    'mensaje' => $msg,
                    'fila'    => $this->filaParaDataTable($orden),
                ], 422);
            }

            $clasificacion = $this->clasificarTipoAdquisicion($data, 'oc', $orden->numero_oc);

            $orden = DB::transaction(function () use ($orden, $data, $clasificacion) {
                $locked = OrdenCompra::whereKey($orden->id)->lockForUpdate()->firstOrFail();
                $locked->update([
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
                    'mp_estado_proceso'     => 'adjuntado',
                    'mp_error_at'           => null,
                    ...$this->clasificacionAplicable($locked, $clasificacion),
                ]);

                return $locked->fresh();
            });

            if (!$orden->api_validado_at || !$orden->api_codigo) {
                throw new \RuntimeException('Los datos de Mercado Publico no quedaron persistidos correctamente.');
            }

            \Log::info('mercado_publico_oc_validada', [
                'accion' => 'mercado_publico_oc_validada',
                'orden_compra_id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'usuario_id' => Auth::id(),
                'estado_proceso' => 'adjuntado',
                'fecha_hora' => now()->toDateTimeString(),
            ]);

            $this->registrarLogTipoAdquisicion($orden, $clasificacion, 'validacion_mercado_publico');

            return response()->json([
                'ok'      => true,
                'mensaje' => "OC validada correctamente. Proveedor: {$data['proveedor_nombre']}",
                'data'    => $data,
                'fila'    => $this->filaParaDataTable($orden),
            ]);

        } catch (MercadoPublicoException $e) {
            $orden = $this->registrarErrorMercadoPublico($orden->id, $e->getMessage(), 'reintento_pendiente');
            return response()->json([
                'ok'      => false,
                'mensaje' => $e->getMessage(),
                'fila'    => $this->filaParaDataTable($orden),
            ], 422);
        } catch (\Throwable $e) {
            $orden = $this->registrarErrorMercadoPublico($orden->id, $e->getMessage(), 'error');
            return response()->json([
                'ok'      => false,
                'mensaje' => 'Error al persistir Mercado Publico: ' . $e->getMessage(),
                'fila'    => $this->filaParaDataTable($orden),
            ], 500);
        }
    }
    public function estadoApi()
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);
        $viva = Cache::remember('mp_api_ping', 60, fn() => app(MercadoPublicoService::class)->ping());
        return response()->json(['activa' => $viva]);
    }

    // Búsqueda inteligente para el formulario de nueva OC

    public function actualizarTipoAdquisicion(Request $request, OrdenCompra $orden)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes') && auth()->user()->esAdmin(), 403);

        if ($orden->estado === 'recibido') {
            return back()->with('error', 'No se puede corregir el tipo de adquisicion de una OC ya recibida.');
        }

        $data = $request->validate([
            'tipo_adquisicion' => ['required', 'in:compra_agil,licitacion,indeterminado'],
            'tipo_adquisicion_confianza' => ['required', 'in:alta,media,baja'],
            'tipo_adquisicion_observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $orden->forceFill([
            'tipo_adquisicion' => $data['tipo_adquisicion'],
            'tipo_adquisicion_origen' => 'manual',
            'tipo_adquisicion_confianza' => $data['tipo_adquisicion_confianza'],
            'tipo_adquisicion_observacion' => $data['tipo_adquisicion_observacion'] ?: 'Correccion manual realizada por ' . auth()->user()->name,
        ])->save();

        \Log::info('oc_tipo_adquisicion_manual', [
            'accion' => 'oc_tipo_adquisicion_manual',
            'orden_compra_id' => $orden->id,
            'numero_oc' => $orden->numero_oc,
            'tipo_adquisicion' => $orden->tipo_adquisicion,
            'usuario_id' => Auth::id(),
            'fecha_hora' => now()->toDateTimeString(),
        ]);

        $this->registrarLogTipoAdquisicion($orden->fresh(), [
            'tipo_adquisicion' => $data['tipo_adquisicion'],
            'tipo_adquisicion_origen' => 'manual',
            'tipo_adquisicion_confianza' => $data['tipo_adquisicion_confianza'],
            'tipo_adquisicion_observacion' => $data['tipo_adquisicion_observacion'] ?: 'Correccion manual realizada por ' . auth()->user()->name,
        ], 'correccion_manual');

        return back()->with('success', 'Tipo de adquisicion actualizado manualmente.');
    }

    public function recalcularTipoAdquisicion(OrdenCompra $orden, MercadoPublicoService $mp)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes') && auth()->user()->esAdmin(), 403);

        if ($orden->estado === 'recibido') {
            return back()->with('error', 'No se puede recalcular el tipo de adquisicion de una OC ya recibida.');
        }

        try {
            $data = $mp->consultarOC($orden->numero_oc);

            if (!$data) {
                return back()->with('error', 'No se pudo recalcular: OC no encontrada en Mercado Publico.');
            }

            if (!empty($data['codigo']) && strtoupper($data['codigo']) !== strtoupper($orden->numero_oc)) {
                return back()->with('error', "No se pudo recalcular: Mercado Publico devolvio {$data['codigo']} para {$orden->numero_oc}.");
            }

            $clasificacion = $this->clasificarTipoAdquisicion($data, 'oc', $orden->numero_oc);

            DB::transaction(function () use ($orden, $clasificacion) {
                $locked = OrdenCompra::whereKey($orden->id)->lockForUpdate()->firstOrFail();
                $locked->forceFill($this->clasificacionAplicable($locked, $clasificacion, true))->save();
            });

            \Log::info('oc_tipo_adquisicion_recalculado', [
                'accion' => 'oc_tipo_adquisicion_recalculado',
                'orden_compra_id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'tipo_adquisicion' => $clasificacion['tipo_adquisicion'] ?? null,
                'usuario_id' => Auth::id(),
                'fecha_hora' => now()->toDateTimeString(),
            ]);

            $this->registrarLogTipoAdquisicion($orden->fresh(), $clasificacion, 'recalculo_manual');

            return back()->with('success', 'Tipo de adquisicion recalculado correctamente.');
        } catch (\Throwable $e) {
            \Log::warning('oc_tipo_adquisicion_recalculo_error', [
                'accion' => 'oc_tipo_adquisicion_recalculo_error',
                'orden_compra_id' => $orden->id,
                'numero_oc' => $orden->numero_oc,
                'usuario_id' => Auth::id(),
                'error' => $e->getMessage(),
                'fecha_hora' => now()->toDateTimeString(),
            ]);

            return back()->with('error', 'No se pudo recalcular el tipo de adquisicion: ' . $e->getMessage());
        }
    }

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
                        $tipoInfo = ['label' => 'Licitación', 'icono' => '📋', 'clase' => 'bg-indigo-100 text-indigo-800 border-indigo-200'];
                    }
                }
            }

            // Para comparación usamos el total disponible según tipo
            $clasificacion = $this->clasificarTipoAdquisicion($data, $tipoBusqueda, $codigo);
            $tipoInfo = $this->tipoInfoDesdeClasificacion($clasificacion, $mp, $data['tipo'] ?? '');

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
                'tipo_adquisicion' => $clasificacion,
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

    private function clasificarTipoAdquisicion(array $data, ?string $tipoBusqueda, ?string $codigo): array
    {
        $tipoApi = $this->normalizarTipoMercadoPublico((string)($data['tipo'] ?? ''));
        $codigoOc = strtoupper(trim((string)($data['codigo'] ?? $codigo ?? '')));
        $codigoLicitacion = strtoupper(trim((string)($data['codigo_licitacion'] ?? '')));

        if ($this->tipoMercadoPublicoEsCompraAgil($tipoApi)) {
            return [
                'tipo_adquisicion' => 'compra_agil',
                'tipo_adquisicion_origen' => 'api_mp',
                'tipo_adquisicion_confianza' => 'alta',
                'tipo_adquisicion_observacion' => 'Mercado Publico informa tipo compatible con Compra Agil.',
            ];
        }

        if ($this->tipoMercadoPublicoEsLicitacion($tipoApi)) {
            return [
                'tipo_adquisicion' => 'licitacion',
                'tipo_adquisicion_origen' => 'api_mp',
                'tipo_adquisicion_confianza' => 'alta',
                'tipo_adquisicion_observacion' => 'Mercado Publico informa tipo compatible con Licitacion.',
            ];
        }

        if ($tipoBusqueda === 'licitacion' || $codigoLicitacion !== '') {
            return [
                'tipo_adquisicion' => 'licitacion',
                'tipo_adquisicion_origen' => 'codigo_mp',
                'tipo_adquisicion_confianza' => 'alta',
                'tipo_adquisicion_observacion' => 'Codigo de licitacion informado por Mercado Publico.',
            ];
        }

        if (preg_match('/-(AG|CA)\d*$/i', $codigoOc)) {
            return [
                'tipo_adquisicion' => 'compra_agil',
                'tipo_adquisicion_origen' => 'codigo_mp',
                'tipo_adquisicion_confianza' => 'alta',
                'tipo_adquisicion_observacion' => 'Codigo OC compatible con Compra Agil.',
            ];
        }

        if (preg_match('/-(LE|LP|L1|LR|LD)\d*$/i', $codigoOc)) {
            return [
                'tipo_adquisicion' => 'licitacion',
                'tipo_adquisicion_origen' => 'codigo_mp',
                'tipo_adquisicion_confianza' => 'media',
                'tipo_adquisicion_observacion' => 'Codigo OC compatible con proceso de licitacion.',
            ];
        }

        $totalNeto = $data['total_neto'] ?? null;
        if ($tipoBusqueda === 'oc' && is_numeric($totalNeto) && (float) $totalNeto > 0) {
            $utm = $this->getUTMActual();
            if ($utm) {
                $esCompraAgil = (float) $totalNeto < ($utm * 100);

                return [
                    'tipo_adquisicion' => $esCompraAgil ? 'compra_agil' : 'licitacion',
                    'tipo_adquisicion_origen' => 'utm_estimado',
                    'tipo_adquisicion_confianza' => 'media',
                    'tipo_adquisicion_observacion' => 'Estimacion por UTM vigente. Respaldo usado por ausencia de tipo/codigo MP confiable.',
                ];
            }
        }

        return [
            'tipo_adquisicion' => 'indeterminado',
            'tipo_adquisicion_origen' => 'manual',
            'tipo_adquisicion_confianza' => 'baja',
            'tipo_adquisicion_observacion' => 'No existe dato confiable de Mercado Publico ni estimacion UTM suficiente.',
        ];
    }

    private function clasificacionAplicable(OrdenCompra $orden, array $clasificacion, bool $forzar = false): array
    {
        if ($forzar) {
            return $clasificacion;
        }

        $tipoActual = $orden->tipo_adquisicion;
        $origenActual = $orden->tipo_adquisicion_origen;

        if ($tipoActual === null || $tipoActual === '' || $tipoActual === 'indeterminado') {
            return $clasificacion;
        }

        if ($origenActual === 'manual') {
            return [];
        }

        return $clasificacion;
    }

    private function registrarLogTipoAdquisicion(?OrdenCompra $orden, array $clasificacion, string $contexto): void
    {
        if (!$orden) {
            return;
        }

        \Log::info('oc_tipo_adquisicion_evaluada', [
            'accion' => 'oc_tipo_adquisicion_evaluada',
            'contexto' => $contexto,
            'oc_evaluada' => $orden->numero_oc,
            'orden_compra_id' => $orden->id,
            'tipo_detectado' => $clasificacion['tipo_adquisicion'] ?? 'indeterminado',
            'origen_deteccion' => $clasificacion['tipo_adquisicion_origen'] ?? 'manual',
            'confianza' => $clasificacion['tipo_adquisicion_confianza'] ?? 'baja',
            'usuario_id' => Auth::id(),
            'usuario' => Auth::user()?->name,
            'fecha' => now()->toDateTimeString(),
        ]);
    }

    private function tipoMercadoPublicoEsCompraAgil(string $tipo): bool
    {
        return str_contains($tipo, 'compra agil')
            || str_contains($tipo, 'agil')
            || in_array($tipo, ['ag', 'ca'], true);
    }

    private function normalizarTipoMercadoPublico(string $tipo): string
    {
        $tipo = mb_strtolower(trim($tipo));

        return strtr($tipo, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'Á' => 'a',
            'É' => 'e',
            'Í' => 'i',
            'Ó' => 'o',
            'Ú' => 'u',
        ]);
    }

    private function tipoMercadoPublicoEsLicitacion(string $tipo): bool
    {
        return str_contains($tipo, 'licitac')
            || in_array($tipo, ['le', 'lp', 'l1', 'lr', 'ld'], true);
    }

    private function tipoInfoDesdeClasificacion(array $clasificacion, MercadoPublicoService $mp, string $tipoOriginal): array
    {
        return match ($clasificacion['tipo_adquisicion'] ?? 'indeterminado') {
            'compra_agil' => ['label' => 'Compra Agil', 'icono' => 'CA', 'clase' => 'bg-green-100 text-green-800 border-green-200'],
            'licitacion' => ['label' => 'Licitacion', 'icono' => 'LIC', 'clase' => 'bg-indigo-100 text-indigo-800 border-indigo-200'],
            default => $mp->detectarTipoProceso($tipoOriginal),
        };
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

    private function registrarErrorMercadoPublico(int $ordenId, string $mensaje, string $estadoProceso): OrdenCompra
    {
        $orden = DB::transaction(function () use ($ordenId, $mensaje, $estadoProceso) {
            $locked = OrdenCompra::whereKey($ordenId)->lockForUpdate()->firstOrFail();
            $locked->forceFill([
                'api_error' => $mensaje,
                'mp_estado_proceso' => $estadoProceso,
                'mp_error_at' => now(),
            ])->save();

            return $locked->fresh();
        });

        \Log::error('mercado_publico_oc_error', [
            'accion' => 'mercado_publico_oc_error',
            'orden_compra_id' => $orden->id,
            'numero_oc' => $orden->numero_oc,
            'usuario_id' => Auth::id(),
            'estado_proceso' => $estadoProceso,
            'error' => $mensaje,
            'fecha_hora' => now()->toDateTimeString(),
        ]);

        return $orden;
    }

    private function recibirOcConFacturaPendiente(OrdenCompra $oc): void
    {
        $userName = Auth::user()->name;
        $userId   = Auth::id();
        $sicds    = [];

        foreach ($oc->detalles as $ocDetalle) {
            $detalle = $ocDetalle->sicdDetalle;
            if (!$detalle) {
                continue;
            }

            $sicd = $detalle->sicd;
            if ($sicd) {
                $sicds[$sicd->id] = $sicd;
            }

            $recibido = (int) $ocDetalle->cantidad_asignada;
            if ($recibido <= 0) {
                continue;
            }

            $ocDetalle->cantidad_recibida = $recibido;
            $ocDetalle->save();

            $recibidoOtrosOcs = OrdenCompraDetalle::where('sicd_detalle_id', $detalle->id)
                ->where('orden_compra_id', '!=', $oc->id)
                ->whereNotNull('cantidad_recibida')
                ->sum('cantidad_recibida');

            $detalle->cantidad_recibida = $recibidoOtrosOcs + $recibido;
            $detalle->save();

            if (!$detalle->producto_id) {
                continue;
            }

            $producto = Producto::withoutGlobalScopes()
                ->whereKey($detalle->producto_id)
                ->lockForUpdate()
                ->first();

            if (!$producto) {
                continue;
            }

            $stockAntes = $producto->stock_actual;
            $producto->stock_actual += $recibido;
            $producto->actualizarFechasStock();
            $producto->save();

            HistorialCambio::create([
                'producto_id'        => $producto->id,
                'nombre_producto'    => $producto->nombre,
                'contenedor_id'      => $producto->contenedor,
                'cantidad'           => $recibido,
                'tipo'               => 'entrada',
                'motivo'             => "OC {$oc->numero_oc} - factura pendiente",
                'aprobado_por'       => $userName,
                'usuario_id'         => $userId,
                'origen'             => 'sicd',
                'origen_id'          => $sicd?->id,
                'origen_tipo'        => 'sicd',
                'orden_compra_id'    => $oc->id,
                'doc_origen'         => 'OC ' . $oc->numero_oc,
                'doc_referencia'     => $sicd ? 'SICD ' . $sicd->codigo_sicd : null,
                'stock_anterior'     => $stockAntes,
                'stock_posterior'    => $producto->stock_actual,
                'usuario_ejecutor_id'=> $userId,
            ]);

            $precioNeto = $ocDetalle->precio_neto ?? $detalle->precio_neto;
            $totalNeto  = $ocDetalle->total_neto  ?? $detalle->total_neto;
            if ($precioNeto && $precioNeto > 0) {
                Precio::registrar(
                    producto:      $producto,
                    precioNeto:    (float) $precioNeto,
                    cantidad:      $recibido,
                    fuente:        'sicd_masiva',
                    origenId:      $sicd?->id,
                    origenTipo:    $sicd ? 'Sicd' : null,
                    precioTotal:   $totalNeto ? (float) $totalNeto : null,
                    notas:         "OC {$oc->numero_oc} · factura pendiente",
                    usuarioId:     $userId,
                    ordenCompraId: $oc->id,
                );
            }
        }

        $oc->estado        = 'recibido';
        $oc->procesado_por = $userName;
        $oc->procesado_at  = now();
        $oc->save();

        foreach ($sicds as $sicd) {
            $sicdCompleto = !$sicd->detalles()
                ->whereRaw('COALESCE(cantidad_recibida, 0) < cantidad_solicitada')
                ->exists();

            $sicd->estado = $sicdCompleto ? 'recibido' : 'agrupado';
            $sicd->save();
        }

        \Log::info('oc_recibida_factura_pendiente', [
            'accion' => 'oc_recibida_factura_pendiente',
            'orden_compra_id' => $oc->id,
            'numero_oc' => $oc->numero_oc,
            'usuario_id' => $userId,
            'fecha_hora' => now()->toDateTimeString(),
        ]);
    }

    private function guardarArchivoDocumento(\Illuminate\Http\UploadedFile $archivo, string $directorio): string
    {
        $ruta = $archivo->store($directorio, 'local');

        if (!$ruta || !Storage::disk('local')->exists($ruta)) {
            throw new \RuntimeException('El archivo no se guardo en disco.');
        }

        if (Storage::disk('local')->size($ruta) < 1) {
            Storage::disk('local')->delete($ruta);
            throw new \RuntimeException('El archivo guardado esta vacio.');
        }

        return $ruta;
    }
}

