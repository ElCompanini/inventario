<?php

namespace App\Http\Controllers;

use App\Models\ComputadorArmado;
use App\Models\ComputadorComponente;
use App\Models\HistorialCambio;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ComputadorController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computadores = ComputadorArmado::with(['componentesActivos.producto'])
            ->orderByDesc('created_at')
            ->get();

        return view('admin.computadores.index', compact('computadores'));
    }

    public function create()
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $codigo = ComputadorArmado::siguienteCodigo();

        return view('admin.computadores.crear', compact('codigo'));
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $data = $request->validate([
            'codigo'           => ['required', 'string', 'max:50', 'unique:computadores_armados,codigo'],
            'nombre'           => ['required', 'string', 'max:200'],
            'descripcion'      => ['nullable', 'string', 'max:1000'],
            'ubicacion'        => ['nullable', 'string', 'max:200'],
            'usuario_asignado' => ['nullable', 'string', 'max:150'],
            'notas'            => ['nullable', 'string', 'max:1000'],
        ], [
            'codigo.unique'   => 'Ya existe un equipo con ese código.',
            'codigo.required' => 'El código del equipo es obligatorio.',
            'nombre.required' => 'El nombre del equipo es obligatorio.',
        ]);

        $computador = ComputadorArmado::create([
            ...$data,
            'estado'     => 'en_armado',
            'usuario_id' => Auth::id(),
        ]);

        return redirect()->route('admin.computadores.show', $computador->id)
            ->with('success', "Equipo {$computador->codigo} creado correctamente.");
    }

    public function show(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::with([
            'componentesActivos.producto.unidadMedida',
            'componentesActivos.producto.categoria',
            'componentes' => fn($q) => $q->with('producto', 'usuarioInstalacion', 'usuarioRetiro')
                                         ->orderByDesc('created_at'),
            'usuario',
        ])->findOrFail($id);

        // Productos de "Partes y Piezas" con stock — incluye precio para valorización
        $productos = Producto::where('stock_actual', '>', 0)
            ->whereHas('categoria.familia', fn($q) =>
                $q->whereRaw("LOWER(REPLACE(nombre,' ','')) LIKE ?", ['%partes%piezas%'])
            )
            ->with([
                'categoria:id,nombre,familia_id',
                'unidadMedida:id,abreviacion',
            ])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'stock_actual', 'unidad', 'categoria_id', 'unidad_medida_id']);

        // Cargar último precio de cada producto (para valorización)
        $preciosPorProducto = \App\Models\Precio::whereIn('producto_id', $productos->pluck('id'))
            ->orderByDesc('created_at')
            ->get(['producto_id', 'precio_neto'])
            ->groupBy('producto_id')
            ->map(fn($g) => $g->first()->precio_neto ?? 0);

        // Categorías únicas para el browser
        $categoriasJson = $productos->pluck('categoria')->filter()->unique('id')
            ->sortBy('nombre')
            ->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre])
            ->values();

        // Productos pre-serializados para JS
        $productosJson = $productos->map(function ($p) use ($preciosPorProducto) {
            return [
                'id'       => $p->id,
                'nombre'   => $p->nombre,
                'stock'    => $p->stock_actual,
                'cat_id'   => $p->categoria_id,
                'cat'      => optional($p->categoria)->nombre ?? '—',
                'unidad'   => optional($p->unidadMedida)->abreviacion ?? ($p->unidad ?? '—'),
                'precio'   => (float) ($preciosPorProducto[$p->id] ?? 0),
            ];
        })->values();

        $tipos  = ComputadorArmado::TIPOS_COMPONENTE;
        $motivos = [
            'ARMADO EQUIPO NUEVO', 'UPGRADE', 'REEMPLAZO COMPONENTE',
            'MANTENIMIENTO', 'DIAGNÓSTICO', 'PRUEBAS TÉCNICAS', 'OTRO',
        ];

        return view('admin.computadores.show', compact(
            'computador', 'tipos', 'productosJson', 'categoriasJson', 'motivos'
        ));
    }

    public function edit(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);
        $computador = ComputadorArmado::findOrFail($id);
        return view('admin.computadores.editar', compact('computador'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::findOrFail($id);

        $data = $request->validate([
            'nombre'           => ['required', 'string', 'max:200'],
            'descripcion'      => ['nullable', 'string', 'max:1000'],
            'ubicacion'        => ['nullable', 'string', 'max:200'],
            'usuario_asignado' => ['nullable', 'string', 'max:150'],
            'estado'           => ['required', 'string', 'in:' . implode(',', array_keys(ComputadorArmado::ESTADOS))],
            'notas'            => ['nullable', 'string', 'max:1000'],
        ]);

        $computador->update($data);

        return back()->with('success', 'Equipo actualizado correctamente.');
    }

    /** Agregar componente al computador y descontar stock */
    public function agregarComponente(Request $request, int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::findOrFail($id);

        if ($computador->estado === 'desarmado') {
            return back()->withErrors(['error' => 'No se pueden agregar componentes a un equipo desarmado.']);
        }

        $data = $request->validate([
            'producto_id'    => ['required', 'integer', 'exists:productos,id'],
            'tipo_componente'=> ['required', 'string', 'in:' . implode(',', array_keys(ComputadorArmado::TIPOS_COMPONENTE))],
            'cantidad'       => ['required', 'integer', 'min:1'],
            'motivo'         => ['required', 'string', 'max:200'],
            'serial'         => ['nullable', 'string', 'max:100'],
            'notas'          => ['nullable', 'string', 'max:500'],
        ], [
            'tipo_componente.in' => 'Tipo de componente inválido.',
            'motivo.required'    => 'El motivo del movimiento es obligatorio.',
        ]);

        $producto = Producto::with('categoria.familia')->findOrFail($data['producto_id']);

        // Validar que pertenece a "Partes y Piezas"
        $nombreFamilia  = $producto->categoria?->familia?->nombre ?? 'familia desconocida';
        $familiaCompara = strtolower(str_replace(' ', '', $nombreFamilia));
        if (!str_contains($familiaCompara, 'partes') || !str_contains($familiaCompara, 'piezas')) {
            return back()->withErrors([
                'producto_id' => "Solo se pueden agregar productos de la familia «Partes y Piezas». El producto pertenece a «{$nombreFamilia}».",
            ])->withInput();
        }

        if ($producto->stock_actual < (int) $data['cantidad']) {
            return back()->withErrors([
                'producto_id' => "Stock insuficiente. Disponible: {$producto->stock_actual}.",
            ])->withInput();
        }

        $tipoLabel = ComputadorArmado::TIPOS_COMPONENTE[$data['tipo_componente']] ?? $data['tipo_componente'];
        $motivoStr = strtoupper(trim($data['motivo']));

        DB::transaction(function () use ($computador, $producto, $data, $tipoLabel, $motivoStr) {
            ComputadorComponente::create([
                'computador_id'          => $computador->id,
                'producto_id'            => $producto->id,
                'tipo_componente'        => $data['tipo_componente'],
                'cantidad'               => $data['cantidad'],
                'serial'                 => $data['serial'] ?? null,
                'notas'                  => $data['notas'] ?? null,
                'activo'                 => true,
                'fecha_instalacion'      => now(),
                'usuario_instalacion_id' => Auth::id(),
            ]);

            $producto->stock_actual -= $data['cantidad'];
            $producto->actualizarFechasStock();
            $producto->save();

            // BINCARD: SALIDA ARMAR EQUIPO
            HistorialCambio::create([
                'producto_id'     => $producto->id,
                'nombre_producto' => $producto->nombre,
                'contenedor_id'   => $producto->contenedor,
                'cantidad'        => $data['cantidad'],
                'tipo'            => 'salida',
                'motivo'          => "SALIDA ARMAR EQUIPO · {$computador->codigo} · {$tipoLabel} · Motivo: {$motivoStr}",
                'aprobado_por'    => Auth::user()->name,
                'usuario_id'      => Auth::id(),
                'origen'          => 'computador_armado',
                'origen_id'       => $computador->id,
            ]);
        });

        return back()->with('success', "Componente «{$producto->nombre}» agregado a {$computador->codigo}.");
    }

    /** Retirar componente del computador y devolver al stock */
    public function retirarComponente(Request $request, int $computadorId, int $componenteId)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::findOrFail($computadorId);
        $componente = ComputadorComponente::where('computador_id', $computadorId)
            ->where('id', $componenteId)
            ->where('activo', true)
            ->firstOrFail();

        $data = $request->validate([
            'motivo_retiro' => ['required', 'string', 'max:500'],
        ], [
            'motivo_retiro.required' => 'El motivo del retiro es obligatorio.',
        ]);

        $producto = $componente->producto;
        $motivoRetiro = $data['motivo_retiro'] ?? 'sin motivo';

        DB::transaction(function () use ($computador, $componente, $producto, $data, $motivoRetiro) {
            // Marcar como retirado (historial permanente)
            $componente->update([
                'activo'          => false,
                'fecha_retiro'    => now(),
                'usuario_retiro_id' => Auth::id(),
                'motivo_retiro'   => $data['motivo_retiro'] ?? null,
            ]);

            // Devolver al stock
            if ($producto) {
                $producto->stock_actual += $componente->cantidad;
                $producto->actualizarFechasStock();
                $producto->save();

                HistorialCambio::create([
                    'producto_id'     => $producto->id,
                    'nombre_producto' => $producto->nombre,
                    'contenedor_id'   => $producto->contenedor,
                    'cantidad'        => $componente->cantidad,
                    'tipo'            => 'entrada',
                    'motivo'          => "INGRESO DESMONTAJE · {$computador->codigo} · Retiro: " . strtoupper($motivoRetiro),
                    'aprobado_por'    => Auth::user()->name,
                    'usuario_id'      => Auth::id(),
                    'origen'          => 'computador_armado',
                    'origen_id'       => $computador->id,
                ]);
            }
        });

        $nombre = $producto?->nombre ?? 'componente';
        return back()->with('success', "«{$nombre}» retirado de {$computador->codigo} y devuelto al stock.");
    }

    /** Desarmar el equipo completo (retira todos los componentes activos) */
    public function desarmar(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::with('componentesActivos.producto')->findOrFail($id);

        if ($computador->componentesActivos->isEmpty()) {
            $computador->update(['estado' => 'desarmado']);
            return back()->with('success', "Equipo {$computador->codigo} marcado como desarmado.");
        }

        DB::transaction(function () use ($computador) {
            foreach ($computador->componentesActivos as $componente) {
                $componente->update([
                    'activo'           => false,
                    'fecha_retiro'     => now(),
                    'usuario_retiro_id'=> Auth::id(),
                    'motivo_retiro'    => 'Desarmado completo del equipo',
                ]);

                $producto = $componente->producto;
                if ($producto) {
                    $producto->stock_actual += $componente->cantidad;
                    $producto->actualizarFechasStock();
                    $producto->save();

                    HistorialCambio::create([
                        'producto_id'     => $producto->id,
                        'nombre_producto' => $producto->nombre,
                        'contenedor_id'   => $producto->contenedor,
                        'cantidad'        => $componente->cantidad,
                        'tipo'            => 'entrada',
                        'motivo'          => "Desarmado completo del equipo {$computador->codigo}",
                        'aprobado_por'    => Auth::user()->name,
                        'usuario_id'      => Auth::id(),
                        'origen'          => 'computador_armado',
                        'origen_id'       => $computador->id,
                    ]);
                }
            }
            $computador->update(['estado' => 'desarmado']);
        });

        return back()->with('success', "Equipo {$computador->codigo} desarmado. Todos los componentes devueltos al stock.");
    }

    public function destroy(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::with('componentesActivos')->findOrFail($id);

        if ($computador->componentesActivos->isNotEmpty()) {
            return back()->withErrors(['error' => 'No se puede eliminar: el equipo tiene componentes instalados. Desármalo primero.']);
        }

        $computador->delete();

        return redirect()->route('admin.computadores.index')
            ->with('success', "Equipo {$computador->codigo} eliminado correctamente.");
    }
}
