<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Container;
use App\Models\Familia;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    private function ccId(): ?int
    {
        return auth()->user()->ccFiltro();
    }

    public function index(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $ccId = $this->ccId();

        $familias = Familia::with([
            'categorias' => fn($q) => $q->with([
                'marcas',
                'productos' => fn($q2) => $q2
                    ->with('marca')
                    ->when($ccId, fn($q3) => $q3->where('centro_costo_id', $ccId)),
            ]),
        ])->where('activo', true)
            ->when($ccId, fn($q) => $q->where(fn($i) => $i->where('centro_costo_id', $ccId)->orWhereNull('centro_costo_id')))
            ->orderBy('nombre')
            ->get();

        $containers = Container::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

        $unidades = UnidadMedida::orderBy('nombre')->get(['id', 'nombre', 'abreviacion']);

        $familiaActiva = (int) $request->get('familia', $familias->first()?->id ?? 0);
        $familiaActual = $familias->firstWhere('id', $familiaActiva);

        // When SIN FAMILIA is active, build virtual category list from all normal families (DB query)
        $categoriasActivas = null;
        if ($familiaActual && $familiaActual->esSinFamilia()) {
            $categoriasActivas = Categoria::paraSinFamilia()
                ->with([
                    'marcas',
                    'productos' => fn($q) => $q
                        ->with('marca')
                        ->when($ccId, fn($q2) => $q2->where('centro_costo_id', $ccId)),
                ])
                ->orderBy('nombre')
                ->get();
        }

        $familiasBienes    = $familias->filter(fn($f) => $f->esBien())->values();
        $familiasServicios = $familias->filter(fn($f) => $f->esServicioCatalogo())->values();

        return view('admin.productos.catalogo', compact(
            'familias', 'familiasBienes', 'familiasServicios',
            'containers', 'unidades', 'familiaActiva', 'categoriasActivas'
        ));
    }

    public function storeFamilia(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $ccId = $this->ccId();

        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('familias', 'nombre')->where('centro_costo_id', $ccId),
            ],
        ], [
            'nombre.unique' => 'Ya existe una familia con ese nombre en tu centro de costo.',
        ]);

        $familia = Familia::create([
            'nombre'          => strtoupper(trim($data['nombre'])),
            'centro_costo_id' => $ccId,
        ]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $familia->id, 'nombre' => $familia->nombre]);
        }

        return back()->with('success', 'Familia creada correctamente.');
    }

    private function esFamiliaProtegida(int $familiaId): bool
    {
        $familia = Familia::find($familiaId);
        if (!$familia) return false;
        $nombre = strtolower(str_replace([' ', '_', '-'], '', $familia->nombre));
        return str_contains($nombre, 'partes') && str_contains($nombre, 'piezas');
    }

    public function storeCategoria(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:150',
                             Rule::unique('categorias')->where('familia_id', $request->familia_id)],
            'familia_id' => ['required', 'integer', 'exists:familias,id'],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre en esta familia.',
        ]);

        if ($this->esFamiliaProtegida((int) $data['familia_id'])) {
            $err = 'La familia "Partes y Piezas" está protegida. Sus categorías no pueden ser modificadas.';
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => $err], 403)
                : back()->withErrors(['familia_id' => $err]);
        }

        $data['nombre'] = strtoupper(trim($data['nombre']));
        $categoria = Categoria::create($data);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $categoria->id, 'nombre' => $categoria->nombre]);
        }

        return back()->with('success', 'Categoría creada correctamente.');
    }

    public function updateCategoria(Request $request, Categoria $categoria)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        if ($this->esFamiliaProtegida($categoria->familia_id)) {
            $err = 'Las categorías de "Partes y Piezas" están protegidas y no pueden ser modificadas.';
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => $err], 403)
                : back()->withErrors(['nombre' => $err]);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150',
                         Rule::unique('categorias')->where('familia_id', $categoria->familia_id)->ignore($categoria->id)],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre en esta familia.',
        ]);

        $data['nombre'] = strtoupper(trim($data['nombre']));
        $categoria->update($data);
        Producto::where('categoria_id', $categoria->id)->update(['nombre' => $data['nombre']]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'nombre' => $categoria->nombre]);
        }

        return back()->with('success', 'Categoría actualizada.');
    }

    public function buscarBarcode(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $ccId   = $this->ccId();
        $codigo = trim($request->get('codigo', ''));
        if (!$codigo) {
            return response()->json(['encontrado' => false, 'similares' => []]);
        }

        $query = Producto::with(['categoria.familia'])
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId));

        $producto = (clone $query)->where('codigo_barras', $codigo)->first();

        if ($producto) {
            return response()->json([
                'encontrado' => true,
                'producto'   => [
                    'id'            => $producto->id,
                    'nombre'        => $producto->nombre,
                    'codigo_barras' => $producto->codigo_barras,
                    'categoria'     => $producto->categoria->nombre,
                    'familia'       => $producto->categoria->familia->nombre,
                    'stock_actual'  => $producto->stock_actual,
                ],
            ]);
        }

        $todos     = (clone $query)->whereNotNull('codigo_barras')->get();
        $similares = [];

        foreach ($todos as $p) {
            similar_text($codigo, $p->codigo_barras, $pct);
            if ($pct >= 40) {
                $similares[] = [
                    'id'            => $p->id,
                    'nombre'        => $p->nombre,
                    'codigo_barras' => $p->codigo_barras,
                    'categoria'     => $p->categoria->nombre,
                    'familia'       => $p->categoria->familia->nombre,
                    'similitud'     => (int) round($pct),
                ];
            }
        }

        usort($similares, fn($a, $b) => $b['similitud'] - $a['similitud']);

        return response()->json([
            'encontrado' => false,
            'similares'  => array_slice($similares, 0, 5),
        ]);
    }

    public function storeProducto(Request $request)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        // Pre-check: is this category in the SERVICIOS family?
        $categoriaObj      = Categoria::with('familia')->find($request->input('categoria_id'));
        $esFamiliaServicios = $categoriaObj?->familia?->tipo === 'servicios';

        $data = $request->validate([
            'nombre'          => ['required', 'string', 'max:200'],
            'categoria_id'    => ['required', 'integer', 'exists:categorias,id'],
            'marca_id'        => ['nullable', 'integer', 'exists:marcas,id'],
            'stock_minimo'    => $esFamiliaServicios ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'stock_critico'   => $esFamiliaServicios ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'contenedor'      => $esFamiliaServicios ? ['nullable', 'integer', 'exists:containers,id'] : ['required', 'integer', 'exists:containers,id'],
            'unidad_medida_id'=> $esFamiliaServicios ? ['nullable', 'integer', 'exists:unidades_medida,id'] : ['required', 'integer', 'exists:unidades_medida,id'],
            'codigo_barras'   => ['nullable', 'string', 'max:100', 'unique:productos,codigo_barras'],
            'es_servicio'     => ['nullable', 'boolean'],
        ], [
            'nombre.required'           => 'El nombre del producto es obligatorio.',
            'codigo_barras.unique'      => 'Ese código de barras ya está asignado a otro producto.',
            'unidad_medida_id.required' => 'Debes seleccionar una unidad de medida.',
        ]);

        $sinMarcaId = Marca::idSinMarca();

        // SERVICIOS family: force es_servicio=true and use SIN MARCA — skip brand validation
        if ($esFamiliaServicios) {
            $marcaId    = $sinMarcaId;
            $esServicio = true;
        } else {
            // Validate brand belongs to category (skip check for SIN MARCA)
            $marcaId = $data['marca_id'] ?: $sinMarcaId;
            if ($marcaId && $marcaId !== $sinMarcaId) {
                $marca = Marca::find($marcaId);
                if (!$marca || (int) $marca->categoria_id !== (int) $data['categoria_id']) {
                    $err = 'La marca seleccionada no pertenece a esta categoría.';
                    return $request->ajax()
                        ? response()->json(['ok' => false, 'errors' => ['marca_id' => [$err]]], 422)
                        : back()->withErrors(['marca_id' => $err]);
                }
            }
            $esServicio = $request->boolean('es_servicio', false);
        }

        $ccId = $this->ccId();

        $producto = Producto::create([
            'nombre'           => strtoupper(trim($data['nombre'])),
            'codigo_barras'    => $data['codigo_barras'] ?? null,
            'stock_actual'     => 0,
            'stock_minimo'     => $data['stock_minimo'] ?? 0,
            'stock_critico'    => $data['stock_critico'] ?? 0,
            'contenedor'       => $data['contenedor'] ?? null,
            'unidad_medida_id' => $data['unidad_medida_id'] ?? null,
            'categoria_id'     => $data['categoria_id'],
            'marca_id'         => $marcaId,
            'centro_costo_id'  => $ccId,
            'es_servicio'      => $esServicio,
        ]);

        if ($request->ajax()) {
            return response()->json([
                'ok'            => true,
                'id'            => $producto->id,
                'nombre'        => $producto->nombre,
                'stock_minimo'  => $producto->stock_minimo,
                'stock_critico' => $producto->stock_critico,
            ]);
        }

        return back()->with('success', 'Producto agregado al catálogo.');
    }

    public function asociarBarcode(Request $request, Producto $producto)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'codigo_barras' => ['required', 'string', 'max:100', 'unique:productos,codigo_barras'],
        ], [
            'codigo_barras.unique' => 'Ese código ya está asignado a otro producto.',
        ]);

        $nuevo = Producto::create([
            'nombre'          => $producto->nombre,
            'codigo_barras'   => $data['codigo_barras'],
            'stock_actual'    => 0,
            'stock_minimo'    => $producto->stock_minimo,
            'stock_critico'   => $producto->stock_critico,
            'contenedor'      => $producto->contenedor,
            'categoria_id'    => $producto->categoria_id,
            'centro_costo_id' => $producto->centro_costo_id,
        ]);

        return response()->json(['ok' => true, 'id' => $nuevo->id, 'nombre' => $nuevo->nombre]);
    }

    public function updateProducto(Request $request, Producto $producto)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'stock_minimo'  => ['required', 'integer', 'min:0'],
            'stock_critico' => ['required', 'integer', 'min:0'],
            'contenedor'    => ['nullable', 'integer', 'exists:containers,id'],
            'marca_id'      => ['nullable', 'integer', 'exists:marcas,id'],
        ]);

        $producto->update([
            'stock_minimo'  => $data['stock_minimo'],
            'stock_critico' => $data['stock_critico'],
            'contenedor'    => $data['contenedor'] ?? null,
            'marca_id'      => $data['marca_id'] ?? $producto->marca_id,
        ]);

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Producto actualizado.');
    }

    public function destroyCategoria(Categoria $categoria)
    {
        abort_unless(auth()->user()->esDev(), 403);

        if ($this->esFamiliaProtegida($categoria->familia_id)) {
            return response()->json(['ok' => false, 'message' => 'Familia protegida.'], 403);
        }

        if ($categoria->productos()->count() > 0) {
            return response()->json([
                'ok'      => false,
                'message' => "No se puede eliminar: tiene {$categoria->productos()->count()} producto(s) asignados.",
            ], 422);
        }

        $categoria->delete();

        return response()->json(['ok' => true]);
    }

    public function destroyProducto(Producto $producto)
    {
        abort_unless(auth()->user()->esDev(), 403);

        $producto->update(['activo' => false]);

        return response()->json(['ok' => true]);
    }

    public function marcasPorCategoria(Categoria $categoria)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        return response()->json(
            $categoria->marcas()->activas()->get(['id', 'nombre'])
                ->map(fn($m) => ['id' => $m->id, 'nombre' => $m->nombre])->values()
        );
    }

    public function asociarMarcaCategoria(Request $request, Categoria $categoria)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'nombre' => [
                'required', 'string', 'max:100',
                Rule::unique('marcas', 'nombre')->where('categoria_id', $categoria->id)->whereNull('deleted_at'),
            ],
        ], [
            'nombre.unique' => 'Ya existe una marca con ese nombre en esta categoría.',
        ]);

        $marca = Marca::create([
            'nombre'      => strtoupper(trim($data['nombre'])),
            'categoria_id' => $categoria->id,
        ]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $marca->id, 'nombre' => $marca->nombre]);
        }

        return back();
    }

    public function desasociarMarcaCategoria(int $categoriaId, int $marcaId)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $marca = Marca::where('id', $marcaId)->where('categoria_id', $categoriaId)->firstOrFail();

        if ($marca->protegido) {
            return response()->json([
                'ok'      => false,
                'message' => "El registro \"{$marca->nombre}\" está protegido y no puede eliminarse.",
            ], 403);
        }

        if ($marca->productos()->count() > 0) {
            return response()->json([
                'ok'      => false,
                'message' => "No se puede eliminar: la marca tiene {$marca->productos()->count()} producto(s) asignados.",
            ], 422);
        }

        $marca->update(['activo' => false]);
        $marca->delete();

        if (request()->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back();
    }
}
