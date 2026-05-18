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
            'componentesActivos.categoria',
            'componentes' => fn($q) => $q->with('producto', 'usuarioInstalacion', 'usuarioRetiro')
                                         ->orderByDesc('created_at'),
            'usuario',
        ])->findOrFail($id);

        // Familia "Partes y Piezas" — buscar por ID usando nombre normalizado
        $familiaPiezas = \App\Models\Familia::whereRaw(
            "LOWER(REPLACE(REPLACE(nombre,' ',''),'/','')) LIKE ?", ['%partes%piezas%']
        )->first();

        // Categorías de esa familia para los tabs (ordenadas por nombre)
        $familiaCategorias = $familiaPiezas
            ? \App\Models\Categoria::where('familia_id', $familiaPiezas->id)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'familia_id'])
            : collect();

        $categoriasJson = $familiaCategorias->map(fn($c) => [
            'id'     => $c->id,
            'nombre' => $c->nombre,
        ])->values();

        $motivos = [
            'ARMADO EQUIPO NUEVO', 'UPGRADE', 'REEMPLAZO COMPONENTE',
            'MANTENIMIENTO', 'DIAGNÓSTICO', 'PRUEBAS TÉCNICAS', 'OTRO',
        ];

        return view('admin.computadores.show', compact(
            'computador', 'familiaCategorias', 'categoriasJson', 'motivos'
        ));
    }

    /** AJAX: productos con stock de una categoría específica */
    public function productosPorCategoria(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $catId        = (int) $request->input('cat_id');
        $computadorId = (int) $request->input('computador_id', 0);

        $productos = Producto::where('categoria_id', $catId)
            ->where('activo', true)
            ->where('es_servicio', false)
            ->with(['unidadMedida:id,abreviacion'])
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'stock_actual', 'unidad', 'categoria_id', 'unidad_medida_id']);

        $ids = $productos->pluck('id');

        $preciosPorProducto = \App\Models\Precio::whereIn('producto_id', $ids)
            ->orderByDesc('created_at')
            ->get(['producto_id', 'precio_neto'])
            ->groupBy('producto_id')
            ->map(fn($g) => $g->first()->precio_neto ?? 0);

        // Cantidad instalada en este equipo por producto (solo activos)
        $instaladoPorProducto = collect();
        if ($computadorId) {
            $instaladoPorProducto = ComputadorComponente::where('computador_id', $computadorId)
                ->where('activo', true)
                ->whereIn('producto_id', $ids)
                ->get(['producto_id', 'cantidad'])
                ->groupBy('producto_id')
                ->map(fn($g) => $g->sum('cantidad'));
        }

        return response()->json(
            $productos->map(fn($p) => [
                'id'        => $p->id,
                'nombre'    => $p->nombre,
                'stock'     => $p->stock_actual,
                'cat_id'    => $p->categoria_id,
                'unidad'    => optional($p->unidadMedida)->abreviacion ?? ($p->unidad ?? ''),
                'precio'    => (float) ($preciosPorProducto[$p->id] ?? 0),
                'instalado' => (int) ($instaladoPorProducto[$p->id] ?? 0),
            ])->values()
        );
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
            'producto_id' => ['required', 'integer', 'exists:productos,id'],
            'categoria_id'=> ['required', 'integer', 'exists:categorias,id'],
            'cantidad'    => ['required', 'integer', 'min:1'],
            'motivo'      => ['required', 'string', 'max:200'],
            'serial'      => ['nullable', 'string', 'max:100'],
            'notas'       => ['nullable', 'string', 'max:500'],
        ], [
            'motivo.required' => 'El motivo del movimiento es obligatorio.',
        ]);

        $producto  = Producto::with('categoria.familia')->findOrFail($data['producto_id']);
        $categoria = \App\Models\Categoria::with('familia')->findOrFail($data['categoria_id']);

        // Bloquear servicios — no son componentes físicos
        if ($producto->es_servicio) {
            return back()->withErrors([
                'producto_id' => "«{$producto->nombre}» es un servicio y no puede usarse como componente físico de un equipo.",
            ])->withInput();
        }

        // Validar que el producto pertenece a la categoría enviada
        if ($producto->categoria_id !== $categoria->id) {
            return back()->withErrors([
                'producto_id' => "El producto no pertenece a la categoría «{$categoria->nombre}».",
            ])->withInput();
        }

        // Validar que la categoría pertenece a la familia "Partes y Piezas" (por ID)
        $familiaId = $categoria->familia_id;
        $familiaPiezas = \App\Models\Familia::whereRaw(
            "LOWER(REPLACE(REPLACE(nombre,' ',''),'/','')) LIKE ?", ['%partes%piezas%']
        )->value('id');

        if ($familiaId !== $familiaPiezas) {
            return back()->withErrors([
                'categoria_id' => "Solo se pueden agregar componentes de la familia «Partes y Piezas».",
            ])->withInput();
        }

        if ($producto->stock_actual < (int) $data['cantidad']) {
            return back()->withErrors([
                'producto_id' => "Stock insuficiente. Disponible: {$producto->stock_actual}.",
            ])->withInput();
        }

        $categoriaLabel = $categoria->nombre;
        $motivoStr      = strtoupper(trim($data['motivo']));

        DB::transaction(function () use ($computador, $producto, $categoria, $data, $categoriaLabel, $motivoStr) {
            ComputadorComponente::create([
                'computador_id'          => $computador->id,
                'producto_id'            => $producto->id,
                'categoria_id'           => $categoria->id,
                'tipo_componente'        => 'otro', // mantenido por compatibilidad; categoría real en categoria_id
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
                'motivo'          => "SALIDA ARMAR EQUIPO · {$computador->codigo} · {$categoriaLabel} · Motivo: {$motivoStr}",
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

    /** Reabrir equipo (listo/en_uso → en_armado) para modificar componentes */
    public function reabrir(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::findOrFail($id);

        if (!in_array($computador->estado, ['listo', 'en_uso'])) {
            return back()->withErrors(['error' => 'Solo se pueden reabrir equipos en estado Listo o En uso.']);
        }

        $computador->update(['estado' => 'en_armado']);

        return back()->with('success', "Equipo {$computador->codigo} reabierto para modificación de componentes.");
    }

    /** Marcar equipo como listo (terminado) */
    public function marcarListo(int $id)
    {
        abort_unless(auth()->user()->tienePermiso('computadores'), 403);

        $computador = ComputadorArmado::findOrFail($id);

        if ($computador->componentesActivos()->doesntExist()) {
            return back()->withErrors(['error' => 'El equipo no tiene componentes instalados.']);
        }

        $computador->update(['estado' => 'listo']);

        return back()->with('success', "Equipo {$computador->codigo} marcado como Listo.");
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
