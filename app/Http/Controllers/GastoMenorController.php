<?php

namespace App\Http\Controllers;

use App\Models\Container;
use App\Models\GastoMenor;
use App\Models\HistorialCambio;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GastoMenorController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user  = auth()->user();
        $query = GastoMenor::with(['producto', 'user', 'historialCambio.container'])
            ->orderByDesc('created_at');

        $ccId = $user->ccFiltro();
        if ($ccId) {
            $query->whereHas('user', fn($q) => $q->where('centro_costo_id', $ccId));
        }

        $registros  = $query->get()->groupBy('folio');
        $productos  = Producto::orderBy('nombre')->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->get();
        $containers = Container::orderBy('nombre')->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))->get(['id', 'nombre']);

        return view('admin.gastos-menores.index', compact('registros', 'productos', 'containers'));
    }

    public function edit(Request $request, string $folio)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $folio = urldecode($folio);
        $items = GastoMenor::with(['producto', 'historialCambio'])
            ->where('folio', $folio)
            ->orderBy('id')
            ->get();
        abort_if($items->isEmpty(), 404);
        $containers = Container::orderBy('nombre')->get(['id', 'nombre']);

        if ($request->ajax()) {
            return view('admin.gastos-menores._editar-form', compact('folio', 'items', 'containers'));
        }
        return view('admin.gastos-menores.editar', compact('folio', 'items', 'containers'));
    }

    public function update(Request $request, string $folio)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $folio = urldecode($folio);

        $request->validate([
            'rut_proveedor' => ['required', 'string', 'max:20'],
            'fecha_emision' => ['required', 'date'],
            'documento'     => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
            'items'         => ['required', 'array'],
            'items.*.id'    => ['required', 'integer', 'exists:gastos_menores,id'],
            'items.*.cantidad'   => ['required', 'integer', 'min:1'],
            'items.*.monto'      => ['required', 'integer', 'min:0'],
            'items.*.precio_neto'=> ['nullable', 'integer', 'min:0'],
        ]);

        $rutaDoc = null;
        if ($request->hasFile('documento')) {
            $rutLimpio = preg_replace('/[^0-9kK]/', '', $request->rut_proveedor);
            $nombre    = "boleta_{$folio}_{$rutLimpio}.pdf";
            $rutaDoc   = $request->file('documento')->storeAs('gastos_menores', $nombre, 'local');
        }

        DB::transaction(function () use ($request, $folio, $rutaDoc) {
            foreach ($request->items as $itemData) {
                $gasto = GastoMenor::findOrFail($itemData['id']);

                // Revertir stock anterior y aplicar el nuevo
                $producto = $gasto->producto;
                $producto->stock_actual = $producto->stock_actual - $gasto->cantidad + (int) $itemData['cantidad'];
                $producto->actualizarFechasStock();
                $producto->save();

                $gasto->rut_proveedor = $request->rut_proveedor;
                $gasto->fecha_emision = $request->fecha_emision;
                $gasto->cantidad      = $itemData['cantidad'];
                $gasto->monto         = $itemData['monto'];
                $gasto->precio_neto   = $itemData['precio_neto'] ?? null;
                if ($rutaDoc) $gasto->documento_path = $rutaDoc;
                $gasto->save();
            }
        });

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('admin.gastos-menores.index')
            ->with('success', "Folio {$folio} actualizado correctamente.");
    }

    public function actualizarContenedor(Request $request, int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $request->validate(['contenedor_id' => ['required', 'integer', 'exists:containers,id']]);
        $gasto = GastoMenor::findOrFail($id);
        if ($gasto->historialCambio) {
            $gasto->historialCambio->contenedor_id = $request->contenedor_id;
            $gasto->historialCambio->save();
        }
        return response()->json(['ok' => true]);
    }

    public function descargarBoleta(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);
        $gasto = GastoMenor::findOrFail($id);
        abort_unless($gasto->documento_path && Storage::disk('local')->exists($gasto->documento_path), 404);
        return response()->file(
            Storage::disk('local')->path($gasto->documento_path),
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline']
        );
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $request->validate([
            'rut_proveedor'     => ['required', 'string', 'max:20'],
            'folio'             => ['required', 'string', 'max:50', 'unique:gastos_menores,folio'],
            'fecha_emision'     => ['required', 'date', 'before_or_equal:now'],
            'documento'         => ['required', 'file', 'mimes:pdf', 'max:10240'],
            'items'             => ['required', 'array', 'min:1', 'max:25'],
            'items.*.producto_id' => ['required', 'integer', 'exists:productos,id'],
            'items.*.cantidad'    => ['required', 'integer', 'min:1'],
            'items.*.monto'       => ['required', 'numeric', 'min:0'],
            'items.*.precio_neto'   => ['nullable', 'numeric', 'min:0'],
            'items.*.contenedor_id' => ['nullable', 'integer', 'exists:containers,id'],
        ], [
            'rut_proveedor.required'       => 'El RUT del proveedor es obligatorio.',
            'folio.required'               => 'El folio de la boleta es obligatorio.',
            'folio.unique'                 => 'El folio ingresado ya existe en el sistema.',
            'fecha_emision.required'       => 'La fecha de emisión es obligatoria.',
            'fecha_emision.before_or_equal'=> 'La fecha de emisión no puede ser futura.',
            'documento.mimes'              => 'El documento debe ser un archivo PDF.',
            'documento.max'                => 'El documento no puede superar los 10 MB.',
            'items.required'               => 'Debes agregar al menos un producto.',
            'items.max'                    => 'No se pueden registrar más de 25 productos por compra.',
            'items.*.producto_id.required' => 'Cada fila debe tener un producto.',
            'items.*.cantidad.min'         => 'La cantidad debe ser al menos 1.',
            'items.*.monto.required'       => 'El monto es obligatorio por producto.',
        ]);

        // Guardar archivo una sola vez
        $rutaDoc = null;
        if ($request->hasFile('documento')) {
            $rutLimpio = preg_replace('/[^0-9kK]/', '', $request->rut_proveedor);
            $nombre    = "boleta_{$request->folio}_{$rutLimpio}.pdf";
            $rutaDoc   = $request->file('documento')->storeAs('gastos_menores', $nombre, 'local');
        }

        // Un único número por boleta (folio), calculado antes del loop
        $nextNumero = (GastoMenor::max('id_gm') ?? 0) + 1;

        DB::transaction(function () use ($request, $rutaDoc, $nextNumero) {
            foreach ($request->items as $item) {
                $producto = Producto::findOrFail($item['producto_id']);
                $producto->stock_actual += (int) $item['cantidad'];
                $producto->actualizarFechasStock();
                $producto->save();

                // Crear GastoMenor primero para obtener su ID real
                $gasto = GastoMenor::create([
                    'id_gm'         => $nextNumero,
                    'producto_id'   => $producto->id,
                    'user_id'       => Auth::id(),
                    'rut_proveedor' => $request->rut_proveedor,
                    'folio'         => $request->folio,
                    'monto'         => $item['monto'],
                    'cantidad'      => $item['cantidad'],
                    'precio_neto'   => $item['precio_neto'] ?? null,
                    'fecha_emision' => $request->fecha_emision,
                    'documento_path'=> $rutaDoc,
                ]);

                $historial = HistorialCambio::create([
                    'producto_id'     => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'contenedor_id'   => $item['contenedor_id'] ?? $producto->contenedor,
                    'cantidad'     => $item['cantidad'],
                    'tipo'         => 'entrada',
                    'motivo'       => "Compra de gasto menor — Folio {$request->folio}",
                    'aprobado_por' => Auth::user()->name,
                    'usuario_id'   => Auth::id(),
                    'origen'       => 'gasto_menor',
                    'origen_id'    => $gasto->id,
                ]);

                $gasto->historial_cambio_id = $historial->id;
                $gasto->save();
            }
        });

        return redirect()->route('admin.gastos-menores.index')
            ->with('success', 'Compra de gasto menor registrada y stock actualizado correctamente.');
    }
}
