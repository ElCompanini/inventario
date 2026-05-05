<?php

namespace App\Http\Controllers;

use App\Models\HistorialCambio;
use App\Models\Producto;
use App\Models\Solicitud;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RetiroController extends Controller
{
    public function form()
    {
        return view('retiro.form');
    }

    /**
     * Búsqueda AJAX de productos por descripción o categoría.
     */
    public function buscar(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $user = Auth::user();
        $ccId = $user->ccFiltro();

        $productos = Producto::with(['categoria.familia'])
            ->where('nombre', 'like', "%{$q}%")
            ->when($ccId, fn($q2) => $q2->where('centro_costo_id', $ccId))
            ->select('id', 'nombre', 'stock_actual', 'categoria_id', 'centro_costo_id')
            ->orderBy('nombre')
            ->limit(12)
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'nombre'      => $p->nombre,
                'stock_actual'=> $p->stock_actual,
                'categoria'   => $p->categoria?->nombre ?? '',
                'familia'     => $p->categoria?->familia?->nombre ?? '',
            ]);

        return response()->json($productos);
    }

    /**
     * Procesa el retiro múltiple.
     * - Usuario normal: crea solicitudes pendientes (activa el círculo naranja).
     * - Admin: descuenta stock directamente y limpia alertas pendientes.
     */
    public function procesar(Request $request)
    {
        $request->validate([
            'items'               => 'required|array|min:1',
            'items.*.producto_id' => 'required|integer|exists:productos,id',
            'items.*.cantidad'    => 'required|integer|min:1',
            'motivo_retiro'       => 'required|string|min:5|max:500',
        ], [
            'items.required'               => 'Debes agregar al menos un producto.',
            'items.min'                    => 'Debes agregar al menos un producto.',
            'items.*.producto_id.required' => 'Producto inválido en el carrito.',
            'items.*.cantidad.min'         => 'La cantidad mínima es 1.',
            'motivo_retiro.required'       => 'El motivo de retiro es obligatorio.',
            'motivo_retiro.min'            => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        $user   = Auth::user();
        $ccId   = $user->ccFiltro();
        $items  = $request->items;
        $motivo = $request->motivo_retiro;

        try {
            DB::transaction(function () use ($user, $ccId, $items, $motivo) {
                foreach ($items as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);

                    if ($ccId && $producto->centro_costo_id !== $ccId) {
                        throw new \Exception("No tienes acceso al producto \"{$producto->nombre}\".");
                    }

                    if ($producto->stock_actual < (int) $item['cantidad']) {
                        throw new \Exception(
                            "Stock insuficiente para \"{$producto->nombre}\". " .
                            "Disponible: {$producto->stock_actual}, solicitado: {$item['cantidad']}."
                        );
                    }

                    if ($user->esAdmin()) {
                        // — ADMIN: descuenta stock directamente —
                        $producto->stock_actual -= (int) $item['cantidad'];
                        $producto->actualizarFechasStock();
                        $producto->save();

                        // Resolver solicitudes de salida pendientes para este producto
                        Solicitud::where('producto_id', $producto->id)
                            ->where('tipo', 'salida')
                            ->where('estado', 'pendiente')
                            ->update([
                                'estado'       => 'aprobado',
                                'rechazado_por' => null,
                            ]);

                        // Registrar en historial
                        HistorialCambio::create([
                            'producto_id'     => $producto->id,
                            'nombre_producto' => $producto->nombre,
                            'contenedor_id'   => $producto->contenedor,
                            'usuario_id'      => $user->id,
                            'tipo'            => 'salida',
                            'cantidad'     => (int) $item['cantidad'],
                            'motivo'       => $motivo,
                            'aprobado_por' => $user->name,
                            'origen'       => 'solicitud',
                        ]);
                    } else {
                        // — USUARIO NORMAL: crea solicitud pendiente (activa círculo naranja) —
                        Solicitud::create([
                            'producto_id' => $producto->id,
                            'cantidad'    => (int) $item['cantidad'],
                            'tipo'        => 'salida',
                            'motivo'      => $motivo,
                            'estado'      => 'pendiente',
                            'usuario_id'  => $user->id,
                        ]);
                    }
                }
            });

            $msg = $user->esAdmin()
                ? 'Retiro procesado. Stock actualizado y alertas pendientes resueltas.'
                : 'Solicitud de retiro enviada. Queda pendiente de aprobación del administrador.';

            return redirect()->route('dashboard')->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}
