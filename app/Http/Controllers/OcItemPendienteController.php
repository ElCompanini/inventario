<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Container;
use App\Models\Familia;
use App\Models\HistorialCambio;
use App\Models\Marca;
use App\Models\OcItemPendiente;
use App\Models\OrdenCompra;
use App\Models\OrdenCompraDetalle;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OcItemPendienteController extends Controller
{
    /** POST /admin/ordenes/{oc}/items-pendientes — marcar ítem como pendiente */
    public function store(Request $request, int $ocId)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);

        $oc = OrdenCompra::with('sicds')->findOrFail($ocId);

        $data = $request->validate([
            'sicd_detalle_id' => ['required', 'integer', 'exists:sicd_detalles,id'],
            'motivo'          => ['required', 'string', 'in:cambio,producto_erroneo,faltante,desistimiento,otro'],
            'notas'           => ['nullable', 'string', 'max:500'],
        ]);

        // Verificar que el sicd_detalle pertenece a un SICD de esta OC
        $sicdIds = $oc->sicds->pluck('id')->toArray();
        $sicdDetalle = \App\Models\SicdDetalle::whereIn('sicd_id', $sicdIds)
            ->findOrFail($data['sicd_detalle_id']);

        // Buscar oc_detalle correspondiente si existe (nuevo sistema)
        $ocDetalle = OrdenCompraDetalle::where('orden_compra_id', $ocId)
            ->where('sicd_detalle_id', $data['sicd_detalle_id'])
            ->first();

        $yaExiste = OcItemPendiente::where('orden_compra_id', $ocId)
            ->where('sicd_detalle_id', $data['sicd_detalle_id'])
            ->whereNull('resuelto_at')
            ->whereNull('deleted_at')
            ->exists();

        if ($yaExiste) {
            return back()->with('error', 'Este ítem ya está marcado como pendiente de revisión.');
        }

        OcItemPendiente::create([
            'orden_compra_id'   => $ocId,
            'oc_detalle_id'     => $ocDetalle?->id,
            'sicd_detalle_id'   => $data['sicd_detalle_id'],
            'motivo'            => $data['motivo'],
            'notas'             => $data['notas'] ?? null,
            'pendiente_user_id' => Auth::id(),
            'pendiente_at'      => now(),
        ]);

        return back()->with('success', 'Ítem marcado como pendiente de revisión.');
    }

    /** POST /admin/ordenes/{oc}/detalles/{det}/confirmar — confirmar recibimiento de un ítem */
    public function confirmarDetalle(Request $request, int $ocId, int $detId)
    {
        abort_unless(auth()->user()->tienePermiso('ordenes'), 403);

        $oc      = OrdenCompra::with('sicds')->findOrFail($ocId);
        $sicdIds = $oc->sicds->pluck('id');

        $det = \App\Models\SicdDetalle::whereIn('sicd_id', $sicdIds)->findOrFail($detId);

        if ((int) $det->cantidad_recibida >= (int) $det->cantidad_solicitada) {
            return back()->with('error', 'Este ítem ya fue confirmado.');
        }

        $det->update([
            'cantidad_recibida'       => $det->cantidad_solicitada,
            'clasificacion_pendiente' => false,
        ]);

        return back()->with('success', 'Ítem confirmado como recibido correctamente.');
    }

    /** GET /admin/oc-pendientes — página de resolución */
    public function index()
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $user   = auth()->user();
        $prefix = $user->centroCostoPrefix();

        $query = OcItemPendiente::with([
            'ordenCompra:id,numero_oc,estado',
            'sicdDetalle',
            'ocDetalle.sicdDetalle',
            'pendienteUser:id,name',
        ])
        ->whereNull('resuelto_at')
        ->whereNull('deleted_at');

        if ($user->tieneFiltroCC()) {
            if ($prefix) {
                $query->whereHas('sicdDetalle.sicd', fn($q) =>
                    $q->withTrashed()->withoutGlobalScope('sin_temporales')
                      ->whereRaw("REGEXP_REPLACE(codigo_sicd, '[^A-Za-z].*', '') = ?", [$prefix])
                );
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        $pendientes = $query->latest('pendiente_at')->get();

        $familias   = Familia::orderBy('nombre')->get(['id', 'nombre']);
        $categorias = Categoria::orderBy('nombre')->get(['id', 'nombre', 'familia_id']);
        $marcas     = Marca::orderBy('nombre')->get(['id', 'nombre']);
        $containers = Container::orderBy('nombre')->with('centroCosto:id,acronimo')->get();

        return view('admin.oc-pendientes.index', compact(
            'pendientes',
            'familias',
            'categorias',
            'marcas',
            'containers',
        ));
    }

    /** GET /admin/oc-pendientes/buscar-producto */
    public function buscarProducto(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json([]);
        }

        $ccId = auth()->user()->ccFiltro();

        $productos = Producto::where('activo', true)
            ->where('es_servicio', false)
            ->when($ccId, fn($query) => $query->where('centro_costo_id', $ccId))
            ->where(fn($query) => $query
                ->where('nombre', 'like', "%{$q}%")
                ->orWhere('codigo_barras', 'like', "%{$q}%")
            )
            ->with(['categoria.familia', 'marca'])
            ->take(15)
            ->get(['id', 'nombre', 'codigo_barras', 'stock_actual', 'categoria_id', 'marca_id', 'contenedor']);

        return response()->json($productos->map(fn($p) => [
            'id'        => $p->id,
            'nombre'    => $p->nombre,
            'codigo'    => $p->codigo_barras,
            'stock'     => (int) $p->stock_actual,
            'categoria' => $p->categoria?->nombre,
            'familia'   => $p->categoria?->familia?->nombre,
            'marca'     => $p->marca?->nombre,
        ]));
    }

    /** POST /admin/oc-pendientes/{id}/resolver — vincular producto y actualizar stock */
    public function resolver(Request $request, int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $pendiente = OcItemPendiente::whereNull('resuelto_at')->whereNull('deleted_at')->findOrFail($id);

        $data = $request->validate([
            'producto_id'  => ['required', 'integer', 'exists:productos,id'],
            'container_id' => ['nullable', 'integer', 'exists:containers,id'],
            'cantidad'     => ['required', 'integer', 'min:1'],
        ]);

        DB::transaction(function () use ($pendiente, $data) {
            $producto  = Producto::withoutGlobalScope('activo')->findOrFail($data['producto_id']);
            $cantidad  = (int) $data['cantidad'];
            $stockAnt  = (int) $producto->stock_actual;

            $producto->increment('stock_actual', $cantidad);

            if (!empty($data['container_id'])) {
                $producto->update(['contenedor' => $data['container_id']]);
            }

            // Si el sicd_detalle aún no tiene producto enlazado, enlazarlo
            $ocDetalle   = $pendiente->ocDetalle;
            $sicdDetalle = $ocDetalle?->sicdDetalle;
            if ($sicdDetalle && !$sicdDetalle->producto_id) {
                $sicdDetalle->update([
                    'producto_id'             => $data['producto_id'],
                    'clasificacion_pendiente' => false,
                    'resuelto_user_id'        => Auth::id(),
                    'resuelto_at'             => now(),
                ]);
            }

            HistorialCambio::create([
                'producto_id'      => $data['producto_id'],
                'nombre_producto'  => $producto->nombre,
                'contenedor_id'    => $data['container_id'] ?? $producto->contenedor,
                'tipo'             => 'entrada',
                'cantidad'         => $cantidad,
                'stock_anterior'   => $stockAnt,
                'stock_posterior'  => $stockAnt + $cantidad,
                'origen'           => 'orden',
                'origen_id'        => $pendiente->orden_compra_id,
                'orden_compra_id'  => $pendiente->orden_compra_id,
                'origen_tipo'      => 'orden_compra',
                'motivo'           => 'Resolución ítem OC pendiente: ' . $pendiente->motivoLabel(),
                'usuario_id'       => Auth::id(),
                'aprobado_por'     => Auth::user()->name,
            ]);

            $pendiente->update([
                'producto_id_final'   => $data['producto_id'],
                'container_id_final'  => $data['container_id'] ?? null,
                'cantidad_resolucion' => $cantidad,
                'resuelto_user_id'    => Auth::id(),
                'resuelto_at'         => now(),
            ]);
        });

        return redirect()->route('admin.oc-pendientes.index')
            ->with('success', 'Ítem resuelto y stock actualizado correctamente.');
    }

    /** DELETE /admin/oc-pendientes/{id} — eliminar ítem pendiente (sin solución) */
    public function destroy(Request $request, int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $pendiente = OcItemPendiente::whereNull('resuelto_at')->whereNull('deleted_at')->findOrFail($id);

        $data = $request->validate([
            'motivo_eliminacion' => ['required', 'string', 'max:500'],
        ]);

        $pendiente->update(['eliminado_motivo' => $data['motivo_eliminacion']]);
        $pendiente->delete();

        return redirect()->route('admin.oc-pendientes.index')
            ->with('success', 'Ítem pendiente eliminado.');
    }
}
