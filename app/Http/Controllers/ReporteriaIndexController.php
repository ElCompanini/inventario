<?php

namespace App\Http\Controllers;

use App\Models\ReporteriaIndexada;
use App\Services\ReporteriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReporteriaIndexController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $query = ReporteriaIndexada::with('usuario')
            ->orderByDesc('created_at');

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        if ($request->filled('modulo')) {
            $query->where('modulo', $request->modulo);
        }
        if ($request->filled('formato')) {
            $query->where('formato', $request->formato);
        }
        if ($request->filled('usuario_id')) {
            $query->where('usuario_id', $request->usuario_id);
        }
        if ($request->filled('desde')) {
            $query->whereDate('created_at', '>=', $request->desde);
        }
        if ($request->filled('hasta')) {
            $query->whereDate('created_at', '<=', $request->hasta);
        }
        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($sub) use ($q) {
                $sub->where('nombre', 'like', "%{$q}%")
                    ->orWhere('nombre_archivo', 'like', "%{$q}%")
                    ->orWhere('usuario_nombre', 'like', "%{$q}%");
            });
        }

        $reporterias = $query->paginate(25)->withQueryString();
        $tipos       = ReporteriaService::tipos();
        $modulos     = ReporteriaService::modulos();

        return view('admin.reportes.historial', compact('reporterias', 'tipos', 'modulos'));
    }

    public function descargar(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $r = ReporteriaIndexada::findOrFail($id);

        abort_unless($r->tieneArchivo(), 404, 'El archivo ya no está disponible en el servidor.');

        return Storage::disk('local')->download($r->ruta_archivo, $r->nombre_archivo);
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $r = ReporteriaIndexada::findOrFail($id);
        $r->delete(); // soft delete

        return back()->with('success', "Reportería «{$r->nombre}» eliminada del índice.");
    }
}
