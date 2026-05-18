<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\SolicitudDevolucion;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $solicitudes = Solicitud::with(['producto' => fn($q) => $q->withoutGlobalScopes()])
            ->where('usuario_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $misDevolucionesPorSolicitud = SolicitudDevolucion::where('usuario_id', $userId)
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('solicitud_id');

        return view('solicitudes.mis-solicitudes', compact('solicitudes', 'misDevolucionesPorSolicitud'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'producto_id' => ['required', 'exists:productos,id'],
            'cantidad'    => ['required', 'integer', 'min:1'],
            'tipo'        => ['required', 'in:salida'],
            'motivo'      => ['required', 'string', 'max:500'],
        ], [
            'producto_id.required' => 'El producto es obligatorio.',
            'producto_id.exists'   => 'El producto seleccionado no existe.',
            'cantidad.required'    => 'La cantidad es obligatoria.',
            'cantidad.integer'     => 'La cantidad debe ser un número entero.',
            'cantidad.min'         => 'La cantidad debe ser al menos 1.',
            'tipo.required'        => 'El tipo es obligatorio.',
            'tipo.in'              => 'El tipo debe ser entrada o salida.',
            'motivo.required'      => 'El motivo es obligatorio.',
            'motivo.max'           => 'El motivo no puede superar los 500 caracteres.',
        ]);

        if ($data['tipo'] === 'salida') {
            $producto = Producto::findOrFail($data['producto_id']);
            if ($producto->stock_actual < $data['cantidad']) {
                return back()->withErrors([
                    'cantidad' => 'La cantidad solicitada supera el stock disponible (' . $producto->stock_actual . ').',
                ])->withInput();
            }
        }

        Solicitud::create([
            'producto_id' => $data['producto_id'],
            'cantidad'    => $data['cantidad'],
            'tipo'        => $data['tipo'],
            'motivo'      => $data['motivo'],
            'estado'      => 'pendiente',
            'usuario_id'  => Auth::id(),
        ]);

        return back()->with('success', 'Solicitud enviada correctamente. Pendiente de aprobación.');
    }

    public function solicitarDevolucion(Request $request, int $id)
    {
        $solicitud = Solicitud::with(['producto' => fn($q) => $q->withoutGlobalScopes()])
            ->where('usuario_id', Auth::id())
            ->findOrFail($id);

        if (!in_array($solicitud->estado, ['aprobado', 'en_devolucion'])) {
            return back()->with('error', 'Solo puedes solicitar devolución de solicitudes aprobadas.');
        }
        if ($solicitud->tipo !== 'salida') {
            return back()->with('error', 'Solo se pueden devolver materiales de solicitudes de salida.');
        }

        $producto = $solicitud->producto;
        if (!$producto || $producto->es_servicio) {
            return back()->with('error', 'Los servicios no tienen devolución de stock físico.');
        }

        // Ya devuelto: solo devoluciones aprobadas (impactan stock)
        $yaDevuelto = SolicitudDevolucion::where('solicitud_id', $solicitud->id)
            ->where('estado', 'aprobada')
            ->sum('cantidad');

        // En revisión: pendientes que aún no se aprueban
        $enRevision = SolicitudDevolucion::where('solicitud_id', $solicitud->id)
            ->where('estado', 'pendiente')
            ->sum('cantidad');

        $disponible = max(0, $solicitud->cantidad - (int)$yaDevuelto - (int)$enRevision);

        if ($disponible <= 0) {
            if ((int)$enRevision > 0) {
                return back()->with('error', 'Ya tienes una solicitud de devolución pendiente de revisión que cubre el saldo disponible. Espera la resolución del administrador.');
            }
            return back()->with('error', 'Ya se devolvió la totalidad de esta solicitud.');
        }

        $data = $request->validate([
            'cantidad_devolucion' => ['required', 'integer', 'min:1', 'max:' . $disponible],
            'motivo_devolucion'   => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'cantidad_devolucion.required' => 'La cantidad es obligatoria.',
            'cantidad_devolucion.integer'  => 'La cantidad debe ser un número entero.',
            'cantidad_devolucion.min'      => 'La cantidad mínima es 1.',
            'cantidad_devolucion.max'      => "No puede solicitar más de {$disponible} unidad(es) disponibles.",
            'motivo_devolucion.required'   => 'El motivo es obligatorio.',
            'motivo_devolucion.min'        => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        SolicitudDevolucion::create([
            'solicitud_id' => $solicitud->id,
            'producto_id'  => $producto->id,
            'usuario_id'   => Auth::id(),
            'cantidad'     => (int)$data['cantidad_devolucion'],
            'motivo'       => $data['motivo_devolucion'],
            'estado'       => 'pendiente',
        ]);

        return back()->with('success', 'Solicitud de devolución enviada correctamente. Quedará pendiente de aprobación por un administrador.');
    }
}
