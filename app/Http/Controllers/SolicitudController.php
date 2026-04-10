<?php

namespace App\Http\Controllers;

use App\Models\Solicitud;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SolicitudController extends Controller
{
    public function index()
    {
        $solicitudes = Solicitud::with('producto')
            ->where('usuario_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('solicitudes.mis-solicitudes', compact('solicitudes'));
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

        // Verificar que si es salida haya stock suficiente para la solicitud
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
}
