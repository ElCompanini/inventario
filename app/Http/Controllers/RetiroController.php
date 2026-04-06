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

        $productos = Producto::where('descripcion', 'like', "%{$q}%")
            ->orWhere('nombre', 'like', "%{$q}%")
            ->select('id', 'nombre', 'descripcion', 'stock_actual')
            ->orderBy('descripcion')
            ->limit(12)
            ->get();

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
        $items  = $request->items;
        $motivo = $request->motivo_retiro;

        try {
            DB::transaction(function () use ($user, $items, $motivo) {
                foreach ($items as $item) {
                    $producto = Producto::lockForUpdate()->findOrFail($item['producto_id']);

                    if ($producto->stock_actual < (int) $item['cantidad']) {
                        throw new \Exception(
                            "Stock insuficiente para \"{$producto->descripcion}\". " .
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
                            'producto_id' => $producto->id,
                            'usuario_id'  => $user->id,
                            'tipo'        => 'salida',
                            'cantidad'    => (int) $item['cantidad'],
                            'motivo'      => $motivo,
                            'aprobado_por' => $user->name,
                            'origen'      => 'solicitud',
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
