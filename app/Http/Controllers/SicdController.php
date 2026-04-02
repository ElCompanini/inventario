<?php

namespace App\Http\Controllers;

use App\Imports\SicdDetallesImport;
use App\Models\Producto;
use App\Models\Sicd;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class SicdController extends Controller
{
    public function index()
    {
        $sicds = Sicd::with(['usuario', 'detalles', 'ordenesCompra'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.sicd.index', compact('sicds'));
    }

    public function create()
    {
        return view('admin.sicd.crear');
    }

    /**
     * Crea el SICD: sube el archivo de respaldo + Excel con productos/cantidades.
     * Extrae código de formato TIC(RAMO)-NUMERO → guarda como TIC(RAMO)/NUMERO.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'archivo_sicd' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'archivo_excel' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'codigo_sicd'  => ['required', 'string', 'max:100', 'regex:/^TIC\([^)]+\)\d+$/i'],
            'descripcion'  => ['nullable', 'string', 'max:500'],
        ], [
            'archivo_sicd.required'  => 'Debes adjuntar el documento SICD.',
            'archivo_sicd.mimes'     => 'El archivo SICD debe ser PDF, JPG o PNG.',
            'archivo_excel.required' => 'Debes adjuntar el Excel con el detalle de productos.',
            'archivo_excel.mimes'    => 'El archivo Excel debe ser XLSX, XLS o CSV.',
            'codigo_sicd.required'   => 'El código SICD es obligatorio.',
            'codigo_sicd.regex'      => 'El formato debe ser TIC(RAMO)NUMERO (ej: TIC(ROMA)12345).',
        ]);

        $archivo        = $request->file('archivo_sicd');
        $nombreOriginal = $archivo->getClientOriginalName();
        // Normaliza TIC(RAMO)NUMERO → TIC(RAMO)/NUMERO para almacenamiento y visualización
        $codigo = strtoupper(trim($data['codigo_sicd']));
        $codigo = preg_replace('/^(TIC\([^)]+\))(\d+)$/i', '$1/$2', $codigo);
        $rutaSicd       = $archivo->store('documentos/sicd', 'local');

        $sicd = Sicd::create([
            'codigo_sicd'    => $codigo,
            'archivo_nombre' => $nombreOriginal,
            'archivo_ruta'   => $rutaSicd,
            'descripcion'    => $data['descripcion'] ?? null,
            'estado'         => 'pendiente',
            'usuario_id'     => Auth::id(),
        ]);

        // Parsear Excel y crear sicd_detalles
        $rows = Excel::toCollection(new SicdDetallesImport, $request->file('archivo_excel'))->first();

        foreach ($rows as $row) {
            // A: descripcion, B: unidad, C: cantidad_solicitada, D: precio_neto, E: total_neto
            $descripcion  = trim((string) ($row[0] ?? ''));
            $unidad       = trim((string) ($row[1] ?? ''));
            $cantidad     = (int) ($row[2] ?? 0);
            $precioNeto   = is_numeric($row[3] ?? '') ? (float) $row[3] : null;
            $totalNeto    = is_numeric($row[4] ?? '') ? (float) $row[4] : null;

            if ($descripcion === '' || $cantidad <= 0) {
                continue;
            }

            // Buscar producto por nombre (case-insensitive)
            $producto = Producto::whereRaw('LOWER(nombre) = ?', [strtolower($descripcion)])->first();

            $sicd->detalles()->create([
                'producto_id'           => $producto?->id,
                'nombre_producto_excel' => $descripcion,
                'unidad'                => $unidad ?: null,
                'cantidad_solicitada'   => $cantidad,
                'cantidad_recibida'     => 0,
                'precio_neto'           => $precioNeto,
                'total_neto'            => $totalNeto,
            ]);
        }

        return redirect()->route('admin.sicd.show', $sicd->id)
            ->with('success', "SICD {$codigo} creado con {$sicd->detalles()->count()} producto(s). Pendiente de agrupar en una OC.");
    }

    public function show(int $id)
    {
        $sicd = Sicd::with(['usuario', 'detalles.producto', 'ordenesCompra'])->findOrFail($id);

        return view('admin.sicd.show', compact('sicd'));
    }

    public function descargar(int $id)
    {
        $sicd = Sicd::findOrFail($id);

        return Storage::disk('local')->download($sicd->archivo_ruta, $sicd->archivo_nombre);
    }
}
