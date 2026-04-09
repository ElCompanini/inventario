<?php

namespace App\Http\Controllers;

use App\Imports\SicdDetallesImport;
use App\Models\HistorialCambio;
use App\Models\Producto;
use App\Models\Sicd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SicdController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('sicd'), 403);
        $sicds = Sicd::with(['usuario', 'detalles', 'ordenesCompra'])
            ->orderByDesc('created_at')
            ->paginate(20);

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
        $productosDB = Producto::whereNotNull('descripcion')->get(['id', 'nombre', 'descripcion']);

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
                $dist   = levenshtein($descNorm, strtolower($p->descripcion));
                $maxLen = max(strlen($descNorm), strlen($p->descripcion));
                $pct    = $maxLen > 0 ? (1 - $dist / $maxLen) * 100 : 0;
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejorProd = $p; }
            }

            if ($mejorPct == 100) {
                $item['producto_id'] = $mejorProd->id;
                $exactos[] = $item;
            } else {
                $item['similitud']         = round($mejorPct, 1);
                $item['sugerencia_id']     = $mejorProd?->id;
                $item['sugerencia_nombre'] = $mejorProd?->descripcion;
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
        Storage::disk('local')->delete($rutaExcelTemp);
        $rutaFinal = Storage::disk('local')->move($rutaSicdTemp, 'documentos/sicd/' . basename($rutaSicdTemp))
            ? 'documentos/sicd/' . basename($rutaSicdTemp)
            : $rutaSicdTemp;

        return $this->crearSicd($codigo, $nombreOriginal, $rutaSicdTemp, $data['descripcion'] ?? null, $exactos);
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

        $productos = Producto::whereNotNull('descripcion')
            ->orderBy('descripcion')
            ->get(['id', 'nombre', 'descripcion']);

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
        // Mover archivo de temp a ubicación final
        $rutaFinal = 'documentos/sicd/' . basename($rutaTemp);
        if (Storage::disk('local')->exists($rutaTemp) && $rutaTemp !== $rutaFinal) {
            Storage::disk('local')->move($rutaTemp, $rutaFinal);
        }

        $sicd = Sicd::create([
            'codigo_sicd'    => $codigo,
            'archivo_nombre' => $nombreOriginal,
            'archivo_ruta'   => $rutaFinal,
            'descripcion'    => $descripcion,
            'estado'         => 'pendiente',
            'usuario_id'     => Auth::id(),
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
        $productosDB = Producto::whereNotNull('descripcion')->get(['id', 'nombre', 'descripcion']);

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
                $dist   = levenshtein($descNorm, strtolower($p->descripcion));
                $maxLen = max(strlen($descNorm), strlen($p->descripcion));
                $pct    = $maxLen > 0 ? (1 - $dist / $maxLen) * 100 : 0;
                if ($pct > $mejorPct) { $mejorPct = $pct; $mejorProd = $p; }
            }

            $item['producto_id'] = $mejorPct == 100 ? $mejorProd->id : null;
            $items[] = $item;
        }

        // Mover archivo a ubicación final
        $rutaFinal = 'documentos/sicd/' . basename($rutaSicdTemp);
        if (Storage::disk('local')->exists($rutaSicdTemp)) {
            Storage::disk('local')->move($rutaSicdTemp, $rutaFinal);
        }

        DB::transaction(function () use ($codigo, $nombreOriginal, $rutaFinal, $data, $items) {
            $sicd = Sicd::create([
                'codigo_sicd'    => $codigo,
                'archivo_nombre' => $nombreOriginal,
                'archivo_ruta'   => $rutaFinal,
                'descripcion'    => $data['descripcion'] ?? null,
                'estado'         => 'recibido',
                'usuario_id'     => Auth::id(),
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
        $sicd = Sicd::with(['usuario', 'detalles.producto', 'ordenesCompra'])->findOrFail($id);
        return view('admin.sicd.show', compact('sicd'));
    }

    public function descargar(int $id)
    {
        $sicd = Sicd::findOrFail($id);
        return Storage::disk('local')->download($sicd->archivo_ruta, $sicd->archivo_nombre);
    }
}
