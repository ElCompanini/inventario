<?php

namespace App\Http\Controllers;

use App\Models\HistorialCambio;
use App\Models\OrdenCompra;
use App\Models\Producto;
use App\Models\Sicd;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    private function query(string $q): array
    {
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $id   = ctype_digit($q) ? (int) $q : null;
        $user = auth()->user();
        $ccId = $user->ccFiltro();

        $productos = Producto::with('container')
            ->where(fn($w) => $w
                ->when($id !== null, fn($qId) => $qId->where('id', $id))
                ->orWhere('nombre', 'LIKE', $like))
            ->when($ccId, fn($q2) => $q2->where('centro_costo_id', $ccId))
            ->orderBy('nombre')->limit(20)->get();

        // SEC-05: filtrar SICDs y OrdeneCompra por centro de costo igual que en sus controladores
        $sicds = Sicd::with('usuario')
            ->where(fn($w) => $w
                ->when($id !== null, fn($qId) => $qId->where('id', $id))
                ->orWhere('codigo_sicd', 'LIKE', $like)
                ->orWhere('descripcion', 'LIKE', $like))
            ->when($user->tieneFiltroCC(), function ($q2) use ($user) {
                $prefix = strtoupper($user->centroCostoPrefix());
                $q2->where('codigo_sicd', 'LIKE', $prefix . '%');
            })
            ->orderByDesc('created_at')->limit(20)->get();

        $ordenes = OrdenCompra::with('usuario')
            ->where(fn($w) => $w
                ->when($id !== null, fn($qId) => $qId->where('id', $id))
                ->orWhere('numero_oc', 'LIKE', $like))
            ->when($user->tieneFiltroCC(), function ($q2) use ($user) {
                $prefix = strtoupper($user->centroCostoPrefix());
                $q2->whereHas('sicds', fn($sq) => $sq->where('codigo_sicd', 'LIKE', $prefix . '%'));
            })
            ->orderByDesc('created_at')->limit(20)->get();

        $historial = HistorialCambio::with(['producto', 'usuario'])
            ->where(fn($w) => $w
                ->when($id !== null, fn($qId) => $qId->where('id', $id))
                ->orWhere('motivo', 'LIKE', $like)
                ->orWhere('aprobado_por', 'LIKE', $like)
                ->orWhereHas('producto', fn($p) => $p->where('nombre', 'LIKE', $like))
                ->orWhereHas('usuario',  fn($u) => $u->where('name',  'LIKE', $like)))
            ->when($ccId, fn($q2) => $q2->whereHas('producto', fn($p) => $p->where('centro_costo_id', $ccId)))
            ->orderByDesc('created_at')->limit(20)->get();

        return compact('productos', 'sicds', 'ordenes', 'historial');
    }

    public function __invoke(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return view('buscar', ['q' => $q, 'resultados' => []]);
        }

        $resultados = $this->query($q);

        return view('buscar', compact('q', 'resultados'));
    }

    /**
     * Endpoint JSON para búsqueda en vivo (navbar).
     */
    public function live(Request $request)
    {
        $q = trim($request->input('q', ''));

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $data = $this->query($q);
        $items = [];

        foreach ($data['productos'] as $p) {
            $items[] = [
                'seccion' => 'Inventario',
                'titulo'  => $p->nombre,
                'sub'     => 'ID #' . $p->id . ' · ' . ($p->container->nombre ?? '—') . ' · Stock: ' . $p->stock_actual,
                'url'     => route('dashboard'),
                'badge'   => null,
            ];
        }

        foreach ($data['sicds'] as $s) {
            $items[] = [
                'seccion' => 'SICD',
                'titulo'  => $s->codigo_sicd,
                'sub'     => 'ID #' . $s->id . ' · ' . $s->created_at->format('d/m/Y') . ($s->descripcion ? ' · ' . $s->descripcion : ''),
                'url'     => route('admin.sicd.show', $s->id),
                'badge'   => ucfirst($s->estado),
            ];
        }

        foreach ($data['ordenes'] as $oc) {
            $items[] = [
                'seccion' => 'Orden de Compra',
                'titulo'  => $oc->numero_oc,
                'sub'     => 'ID #' . $oc->id . ' · ' . $oc->usuario->name . ' · ' . $oc->created_at->format('d/m/Y'),
                'url'     => route('admin.ordenes.show', $oc->id),
                'badge'   => ucfirst($oc->estado),
            ];
        }

        foreach ($data['historial'] as $h) {
            $items[] = [
                'seccion' => 'Historial',
                'titulo'  => $h->producto->nombre,
                'sub'     => 'ID #' . $h->id . ' · ' . $h->motivo . ' · ' . $h->created_at->format('d/m/Y H:i'),
                'url'     => null,
                'badge'   => ucfirst($h->tipo),
            ];
        }

        return response()->json($items);
    }

}
