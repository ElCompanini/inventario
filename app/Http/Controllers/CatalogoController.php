<?php


namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Container;
use App\Models\Familia;
use App\Models\HistorialCambio;
use App\Models\Marca;
use App\Models\Producto;
use App\Models\ArriendoMovimiento;
use App\Models\ServicioEstado;
use App\Models\UnidadMedida;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CatalogoController extends Controller
{
    private function ccId(): ?int
    {
        // Para visibilidad usamos ccFiltro(), pero para CREAR un producto
        // usamos el CC real del usuario â€” asÃ­ los productos creados por devs
        // quedan asignados a su CC y son visibles para otros usuarios del mismo CC.
        return auth()->user()->centro_costo_id ?? null;
    }

    private function tipoItemValido(?string $tipo): string
    {
        return in_array($tipo, ['producto', 'servicio', 'mantencion', 'arriendo'], true) ? $tipo : 'producto';
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
                    ->when($ccId, fn($q3) => $q3->where(fn($i) => $i->where('centro_costo_id', $ccId)->orWhereNull('centro_costo_id'))),
            ]),
            'productosDirectos' => fn($q) => $q
                ->with('marca')
                ->when($ccId, fn($q2) => $q2->where(fn($i) => $i->where('centro_costo_id', $ccId)->orWhereNull('centro_costo_id'))),
        ])->where('activo', true)
            ->when($ccId, fn($q) => $q->where(fn($i) => $i->where('centro_costo_id', $ccId)->orWhereNull('centro_costo_id')))
            ->orderBy('nombre')
            ->get();

        $containers = Container::orderBy('nombre')
            ->when($ccId, fn($q) => $q->where('centro_costo_id', $ccId))
            ->get(['id', 'nombre']);

        $unidades = UnidadMedida::noEsPresentacion()->orderBy('nombre')->get(['id', 'nombre', 'abreviacion']);

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

        $familiasBienes    = $familias->filter(fn($f) => ($f->tipo_catalogo ?? 'bien') === 'bien')->values();
        $familiasServicios = $familias->filter(fn($f) => ($f->tipo_catalogo ?? 'bien') === 'servicio')->values();

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
                Rule::unique('familias', 'nombre')->where('centro_costo_id', $ccId)->where('tipo_item', $this->tipoItemValido($request->input('tipo_item'))),
            ],
            'tipo_item'          => ['nullable', Rule::in(['producto', 'servicio', 'mantencion', 'arriendo'])],
            'requiere_categoria' => ['nullable', 'boolean'],
            'requiere_marca'     => ['nullable', 'boolean'],
        ], [
            'nombre.unique' => 'Ya existe una familia con ese nombre en tu centro de costo.',
        ]);
        $tipoItem = $this->tipoItemValido($data['tipo_item'] ?? 'producto');

        $familia = Familia::create([
            'nombre'             => strtoupper(trim($data['nombre'])),
            'centro_costo_id'    => $ccId,
            'tipo_item'          => $tipoItem,
            'tipo_catalogo'      => $tipoItem === 'producto' ? 'bien' : 'servicio',
            'requiere_categoria' => $data['requiere_categoria'] ?? true,
            'requiere_marca'     => $data['requiere_marca'] ?? false,
        ]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $familia->id, 'nombre' => $familia->nombre]);
        }

        return back()->with('success', 'Familia creada correctamente.');
    }

    public function updateFamilia(Request $request, Familia $familia)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'requiere_categoria' => ['required', 'boolean'],
            'requiere_marca'     => ['required', 'boolean'],
        ]);

        $familia->update($data);

        return response()->json(['ok' => true]);
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
            'tipo_item'  => ['nullable', Rule::in(['producto', 'servicio', 'mantencion', 'arriendo'])],
        ], [
            'nombre.unique' => 'Ya existe una categorÃ­a con ese nombre en esta familia.',
        ]);

        $familia = Familia::findOrFail($data['familia_id']);
        $tipoItem = $this->tipoItemValido($data['tipo_item'] ?? $familia->tipo_item ?? 'producto');
        if (($familia->tipo_item ?? 'producto') !== $tipoItem) {
            $err = 'La categorÃƒÂ­a debe pertenecer al mismo tipo de ÃƒÂ­tem que la familia.';
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => $err], 422)
                : back()->withErrors(['tipo_item' => $err]);
        }

        if ($this->esFamiliaProtegida((int) $data['familia_id'])) {
            $err = 'La familia "Partes y Piezas" estÃ¡ protegida. Sus categorÃ­as no pueden ser modificadas.';
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => $err], 403)
                : back()->withErrors(['familia_id' => $err]);
        }

        $data['nombre'] = strtoupper(trim($data['nombre']));
        $data['tipo_item'] = $tipoItem;
        $categoria = Categoria::create($data);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $categoria->id, 'nombre' => $categoria->nombre]);
        }

        return back()->with('success', 'CategorÃ­a creada correctamente.');
    }

    public function updateCategoria(Request $request, Categoria $categoria)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        if ($this->esFamiliaProtegida($categoria->familia_id)) {
            $err = 'Las categorÃ­as de "Partes y Piezas" estÃ¡n protegidas y no pueden ser modificadas.';
            return $request->ajax()
                ? response()->json(['ok' => false, 'error' => $err], 403)
                : back()->withErrors(['nombre' => $err]);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150',
                         Rule::unique('categorias')->where('familia_id', $categoria->familia_id)->ignore($categoria->id)],
        ], [
            'nombre.unique' => 'Ya existe una categorÃ­a con ese nombre en esta familia.',
        ]);

        $data['nombre'] = strtoupper(trim($data['nombre']));
        $categoria->update($data);
        Producto::where('categoria_id', $categoria->id)->update(['nombre' => $data['nombre']]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'nombre' => $categoria->nombre]);
        }

        return back()->with('success', 'CategorÃ­a actualizada.');
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

        // Resolve familia first â€” it drives conditional rules
        $familiaId  = (int) $request->input('familia_id');
        $familiaObj = Familia::find($familiaId);
        $esFamiliaServicios = $familiaObj?->tipo === 'servicios';
        $requiereCategoria  = $familiaObj ? $familiaObj->requiereCategoria() : true;
        $requiereMarca      = $familiaObj ? $familiaObj->requiereMarca()     : false;

        $categoriaObj = $requiereCategoria
            ? Categoria::with('familia')->find($request->input('categoria_id'))
            : null;

        $tipoSolicitado = $request->input('tipo_item')
            ?: ($request->boolean('es_servicio', false) ? 'servicio' : ($esFamiliaServicios ? 'servicio' : 'producto'));
        $tipoSolicitado = $this->tipoItemValido($tipoSolicitado);
        if ($categoriaObj && (($categoriaObj->tipo_item ?? 'producto') !== $tipoSolicitado || (($categoriaObj->familia?->tipo_item ?? 'producto') !== $tipoSolicitado))) {
            $err = 'El tipo de Ã­tem no coincide con la familia y categorÃ­a seleccionadas.';
            return $request->ajax()
                ? response()->json(['ok' => false, 'errors' => ['tipo_item' => [$err]]], 422)
                : back()->withErrors(['tipo_item' => $err]);
        }
        $esNoFisico = in_array($tipoSolicitado, ['servicio', 'mantencion', 'arriendo'], true);

        $data = $request->validate([
            'nombre'               => ['required', 'string', 'max:200'],
            'familia_id'           => ['required', 'integer', 'exists:familias,id'],
            'categoria_id'         => $requiereCategoria ? ['required', 'integer', 'exists:categorias,id'] : ['nullable', 'integer', 'exists:categorias,id'],
            'marca_id'             => ['nullable', 'integer', 'exists:marcas,id'],
            'stock_minimo'         => $esNoFisico ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'stock_critico'        => $esNoFisico ? ['nullable', 'integer', 'min:0'] : ['required', 'integer', 'min:0'],
            'contenedor'           => ($esNoFisico || $request->filled('codigo_barras')) ? ['nullable', 'integer', 'exists:containers,id'] : ['required', 'integer', 'exists:containers,id'],
            'unidad_medida_id'     => ($esNoFisico || $request->filled('codigo_barras')) ? ['nullable', 'integer', 'exists:unidades_medida,id'] : ['required', 'integer', 'exists:unidades_medida,id'],
            'codigo_barras'        => ['nullable', 'string', 'max:100', 'unique:productos,codigo_barras'],
            'es_servicio'          => ['nullable', 'boolean'],
            'tipo_item'            => ['nullable', 'in:producto,servicio,mantencion,arriendo'],
            'maneja_presentacion'  => ['nullable', 'boolean'],
            'tipo_presentacion'    => ['nullable', 'string', 'max:50'],
            'cantidad_presentacion'=> ['nullable', 'integer', 'min:2', 'max:9999'],
            'unidad_base'          => ['nullable', 'string', 'max:50'],
            'proveedor_nombre'     => ['nullable', 'string', 'max:255'],
            'estado_operacional'   => ['nullable', 'string', Rule::in(['pendiente', 'aprobado', 'en_proceso', 'ejecutado', 'validado', 'cerrado', 'cancelado'])],
            'fecha_ejecucion'      => ['nullable', 'date'],
            'documento_referencia' => ['nullable', 'string', 'max:100'],
            'observacion'          => ['nullable', 'string', 'max:2000'],
            'fecha_inicio'         => ['nullable', 'date'],
            'fecha_termino'        => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'condicion_termino'    => ['nullable', Rule::in(['con_fecha', 'sin_fecha'])],
            'unidad_tiempo'        => ['nullable', 'string', 'max:20'],
            'monto_periodo'        => ['nullable', 'numeric', 'min:0'],
            'monto_total'          => ['nullable', 'numeric', 'min:0'],
        ], [
            'nombre.required'              => 'El nombre del producto es obligatorio.',
            'codigo_barras.unique'         => 'Ese cÃ³digo de barras ya estÃ¡ asignado a otro producto.',
            'unidad_medida_id.required'    => 'Debes seleccionar una unidad de medida.',
            'cantidad_presentacion.min'    => 'La cantidad por presentaciÃ³n debe ser al menos 2.',
        ]);

        $sinMarcaId = Marca::idSinMarca();

        // SERVICIOS family or families that don't require brand: force SIN MARCA
        if ($esFamiliaServicios || !$requiereMarca) {
            $marcaId    = $sinMarcaId;
            $tipoItem   = $request->input('tipo_item') ?: ($esFamiliaServicios ? 'servicio' : 'producto');
            if (!in_array($tipoItem, ['producto', 'servicio', 'mantencion', 'arriendo'], true)) {
                $tipoItem = $esFamiliaServicios ? 'servicio' : 'producto';
            }
            $esServicio = $tipoItem === 'servicio';
        } else {
            // Validate brand belongs to category (skip check for SIN MARCA)
            $marcaId = ($data['marca_id'] ?? null) ?: $sinMarcaId;
            if ($marcaId && $marcaId !== $sinMarcaId && $requiereCategoria && isset($data['categoria_id'])) {
                $marca = Marca::find($marcaId);
                if (!$marca || (int) $marca->categoria_id !== (int) $data['categoria_id']) {
                    $err = 'La marca seleccionada no pertenece a esta categorÃ­a.';
                    return $request->ajax()
                        ? response()->json(['ok' => false, 'errors' => ['marca_id' => [$err]]], 422)
                        : back()->withErrors(['marca_id' => $err]);
                }
            }
            $tipoItem = $request->input('tipo_item') ?: ($request->boolean('es_servicio', false) ? 'servicio' : 'producto');
            if (!in_array($tipoItem, ['producto', 'servicio', 'mantencion', 'arriendo'], true)) {
                $tipoItem = 'producto';
            }
            $esServicio = $tipoItem === 'servicio';
        }

        $ccId = $this->ccId();

        $manejaPresentacion = $tipoItem === 'producto' && $request->boolean('maneja_presentacion', false);

        if ($tipoItem === 'mantencion') {
            $request->validate([
                'proveedor_nombre' => ['required', 'string', 'max:255'],
            ], [
                'proveedor_nombre.required' => 'Debes indicar el proveedor de la mantenciÃƒÂ³n.',
            ]);
        }

        if ($tipoItem === 'arriendo') {
            $request->validate([
                'proveedor_nombre'     => ['required', 'string', 'max:255'],
                'fecha_inicio'         => ['required', 'date'],
                'condicion_termino'    => ['required', Rule::in(['con_fecha', 'sin_fecha'])],
                'fecha_termino'        => [Rule::requiredIf($request->input('condicion_termino') === 'con_fecha'), 'nullable', 'date', 'after_or_equal:fecha_inicio'],
                'monto_periodo'        => ['required', 'numeric', 'min:0'],
                'monto_total'          => ['required', 'numeric', 'min:0'],
                'documento_referencia' => ['required', 'string', 'max:100'],
            ], [
                'proveedor_nombre.required'     => 'Debes indicar el proveedor del arriendo.',
                'fecha_inicio.required'         => 'Debes indicar la fecha de inicio.',
                'condicion_termino.required'    => 'Debes indicar la condiciÃƒÂ³n de tÃƒÂ©rmino.',
                'fecha_termino.required'        => 'Debes indicar la fecha de tÃƒÂ©rmino.',
                'monto_periodo.required'        => 'Debes indicar el monto del perÃƒÂ­odo.',
                'monto_total.required'          => 'Debes indicar el monto total estimado.',
                'documento_referencia.required' => 'Debes indicar el documento de referencia.',
            ]);
        }

        $producto = DB::transaction(function () use ($data, $tipoItem, $manejaPresentacion, $marcaId, $ccId, $esServicio, $familiaId, $requiereCategoria, $request) {
            $producto = Producto::create([
            'nombre'                => strtoupper(trim($data['nombre'])),
            'codigo_barras'         => $data['codigo_barras'] ?? null,
            'stock_actual'          => $tipoItem === 'producto' ? (int) ($request->stock_inicial ?? 0) : 0,
            'stock_minimo'          => $tipoItem === 'producto' ? ($data['stock_minimo'] ?? 0) : 0,
            'stock_critico'         => $tipoItem === 'producto' ? ($data['stock_critico'] ?? 0) : 0,
            'contenedor'            => $tipoItem === 'producto' ? ($data['contenedor'] ?? null) : null,
            'unidad_medida_id'      => $tipoItem === 'producto' ? ($data['unidad_medida_id'] ?? null) : null,
            'familia_id'            => $familiaId,
            'categoria_id'          => $requiereCategoria ? ($data['categoria_id'] ?? null) : null,
            'marca_id'              => $tipoItem === 'producto' ? $marcaId : null,
            'centro_costo_id'       => $ccId,
            'es_servicio'           => $esServicio,
            'tipo_item'             => $tipoItem,
            'maneja_presentacion'   => $manejaPresentacion,
            'tipo_presentacion'     => $manejaPresentacion ? ($data['tipo_presentacion'] ?? null) : null,
            'cantidad_presentacion' => $manejaPresentacion ? ($data['cantidad_presentacion'] ?? null) : null,
            'unidad_base'           => $manejaPresentacion ? ($data['unidad_base'] ?? null) : null,
            ]);

            if (in_array($tipoItem, ['servicio', 'mantencion'], true)) {
                $observacion = trim((string) $request->input('observacion', ''));
                if ($tipoItem === 'mantencion' && $request->filled('fecha_ejecucion')) {
                    $observacion = trim($observacion . "\nFecha ejecuciÃƒÂ³n: " . $request->input('fecha_ejecucion'));
                }

                ServicioEstado::create([
                    'producto_id'          => $producto->id,
                    'estado'               => $request->input('estado_operacional', 'pendiente'),
                    'estado_anterior'      => null,
                    'usuario_id'           => auth()->id(),
                    'observacion'          => $observacion ?: null,
                    'documento_referencia' => $request->input('documento_referencia'),
                    'proveedor_nombre'     => $request->input('proveedor_nombre'),
                ]);
            }

            if ($tipoItem === 'arriendo') {
                $fechaInicio = $request->date('fecha_inicio');
                $fechaTermino = $request->input('condicion_termino') === 'con_fecha' ? $request->date('fecha_termino') : null;
                $duracion = ($fechaInicio && $fechaTermino) ? $fechaInicio->diffInDays($fechaTermino) + 1 : null;

                ArriendoMovimiento::create([
                    'producto_id'          => $producto->id,
                    'proveedor_nombre'     => $request->input('proveedor_nombre'),
                    'estado_anterior'      => null,
                    'estado_nuevo'         => 'pendiente',
                    'fecha_inicio'         => $fechaInicio,
                    'fecha_termino'        => $fechaTermino,
                    'condicion_termino'    => $request->input('condicion_termino'),
                    'duracion'             => $duracion,
                    'unidad_tiempo'        => $request->input('unidad_tiempo', 'dia'),
                    'monto_periodo'        => $request->input('monto_periodo'),
                    'monto_total'          => $request->input('monto_total'),
                    'responsable_id'       => auth()->id(),
                    'ejecutado_por'        => auth()->id(),
                    'observacion'          => $request->input('observacion'),
                    'documento_referencia' => $request->input('documento_referencia'),
                ]);
            }

            return $producto;
        });

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
            'nombre'                => $producto->nombre,
            'codigo_barras'         => $data['codigo_barras'],
            'stock_actual'          => 0,
            'stock_minimo'          => $producto->stock_minimo,
            'stock_critico'         => $producto->stock_critico,
            'contenedor'            => $producto->contenedor,
            'categoria_id'          => $producto->categoria_id,
            'centro_costo_id'       => $producto->centro_costo_id,
            'familia_id'            => $producto->familia_id,
            'marca_id'              => $producto->marca_id,
            'unidad_medida_id'      => $producto->unidad_medida_id,
            'es_servicio'           => $producto->es_servicio,
            'tipo_item'             => $producto->tipo_item,
            'maneja_presentacion'   => $producto->maneja_presentacion,
            'tipo_presentacion'     => $producto->tipo_presentacion,
            'cantidad_presentacion' => $producto->cantidad_presentacion,
            'unidad_base'           => $producto->unidad_base,
        ]);

        return response()->json(['ok' => true, 'id' => $nuevo->id, 'nombre' => $nuevo->nombre]);
    }

    public function updateProducto(Request $request, Producto $producto)
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $data = $request->validate([
            'stock_minimo'         => ['required', 'integer', 'min:0'],
            'stock_critico'        => ['required', 'integer', 'min:0'],
            'contenedor'           => ['nullable', 'integer', 'exists:containers,id'],
            'marca_id'             => ['nullable', 'integer', 'exists:marcas,id'],
            'unidad_medida_id'     => ['nullable', 'integer', 'exists:unidades_medida,id'],
            'tipo_item'            => ['nullable', 'in:producto,servicio,mantencion,arriendo'],
            'maneja_presentacion'  => ['nullable', 'boolean'],
            'tipo_presentacion'    => ['nullable', 'string', 'max:50'],
            'cantidad_presentacion'=> ['nullable', 'integer', 'min:2', 'max:9999'],
            'unidad_base'          => ['nullable', 'string', 'max:50'],
        ]);

        $tipoItem = $request->input('tipo_item') ?: ($producto->tipo_item ?? ($producto->es_servicio ? 'servicio' : 'producto'));
        if (!in_array($tipoItem, ['producto', 'servicio', 'mantencion', 'arriendo'], true)) {
            $tipoItem = 'producto';
        }

        $manejaPresentacion = $tipoItem === 'producto' && $request->boolean('maneja_presentacion', false);

        $updateData = [
            'tipo_item'             => $tipoItem,
            'es_servicio'           => $tipoItem === 'servicio',
            'stock_minimo'          => $tipoItem === 'producto' ? $data['stock_minimo'] : 0,
            'stock_critico'         => $tipoItem === 'producto' ? $data['stock_critico'] : 0,
            'contenedor'            => $tipoItem === 'producto' ? ($data['contenedor'] ?? null) : null,
            'marca_id'              => $data['marca_id'] ?? $producto->marca_id,
            'maneja_presentacion'   => $manejaPresentacion,
            'tipo_presentacion'     => $manejaPresentacion ? ($data['tipo_presentacion'] ?? null) : null,
            'cantidad_presentacion' => $manejaPresentacion ? ($data['cantidad_presentacion'] ?? null) : null,
            'unidad_base'           => $manejaPresentacion ? ($data['unidad_base'] ?? null) : null,
        ];
        if ($tipoItem === 'producto' && !empty($data['unidad_medida_id'])) {
            $updateData['unidad_medida_id'] = $data['unidad_medida_id'];
        } elseif ($tipoItem !== 'producto') {
            $updateData['unidad_medida_id'] = null;
        }
        $producto->update($updateData);

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
            'tipo_item' => ['nullable', Rule::in(['producto', 'servicio', 'mantencion', 'arriendo'])],
        ], [
            'nombre.unique' => 'Ya existe una marca con ese nombre en esta categorÃ­a.',
        ]);

        $tipoItem = $this->tipoItemValido($data['tipo_item'] ?? $categoria->tipo_item ?? 'producto');
        if (($categoria->tipo_item ?? 'producto') !== $tipoItem || $tipoItem !== 'producto') {
            return response()->json([
                'ok' => false,
                'errors' => ['tipo_item' => ['Las marcas solo aplican a categorÃƒÂ­as de productos.']],
            ], 422);
        }

        $marca = Marca::create([
            'nombre'      => strtoupper(trim($data['nombre'])),
            'categoria_id' => $categoria->id,
            'tipo_item'   => $tipoItem,
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
                'message' => "El registro \"{$marca->nombre}\" estÃ¡ protegido y no puede eliminarse.",
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

    public function importarProductosMasivo(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $request->validate([
            'excel_catalogo'         => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
            'categoria_id'           => ['required', 'integer', 'exists:categorias,id'],
            'contenedor_id'          => ['nullable', 'integer', 'exists:containers,id'],
            'marca_id'               => ['nullable', 'integer', 'exists:marcas,id'],
            'maneja_presentacion'    => ['nullable', 'boolean'],
            'tipo_presentacion'      => ['nullable', 'string', 'max:50'],
            'cantidad_presentacion'  => ['nullable', 'integer', 'min:1', 'max:9999'],
            'unidad_base'            => ['nullable', 'string', 'max:50'],
        ]);

        $rawRows = $this->leerExcelCatalogo($request->file('excel_catalogo'));

        if ($rawRows->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'El archivo Excel estÃ¡ vacÃ­o o no tiene filas de datos.']);
        }

        // Validate header row
        $encabezado = $rawRows->first()->map(fn($v) => strtolower(trim((string) $v)))->values();
        $esperados  = ['descripcion', 'unidad', 'cantidad', 'minimo', 'critico'];
        foreach ($esperados as $i => $col) {
            if (($encabezado[$i] ?? '') !== $col) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'El archivo debe contener las columnas: descripcion, unidad, cantidad, minimo, critico (fila 1 como encabezado).',
                ]);
            }
        }

        $dataRows = $rawRows->slice(1)->values();

        $categoria    = Categoria::findOrFail($request->categoria_id);
        $ccId         = $this->ccId();
        $contenedorId = $request->filled('contenedor_id') ? (int) $request->contenedor_id : null;
        $marcaId      = $request->filled('marca_id')      ? (int) $request->marca_id      : Marca::idSinMarca();

        $manejaPres = $request->boolean('maneja_presentacion', false);
        $tipoPres   = $manejaPres ? trim($request->input('tipo_presentacion', '')) : null;
        $cantPres   = $manejaPres ? (int) $request->input('cantidad_presentacion', 0) : null;
        $unidBase   = $manejaPres ? trim($request->input('unidad_base', '')) : null;

        if ($manejaPres && (!$tipoPres || $cantPres < 1 || !$unidBase)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Si los productos manejan paquetes, completa tipo, cantidad (â‰¥ 1) y unidad base.',
            ]);
        }

        // Build unit lookup index
        $unidades = UnidadMedida::activas()->get(['id', 'nombre', 'abreviacion']);
        $unidIdx  = [];
        foreach ($unidades as $u) {
            $unidIdx[$this->normalizarTextoCatalogo($u->nombre)]      = $u->id;
            $unidIdx[$this->normalizarTextoCatalogo($u->abreviacion)] = $u->id;
        }

        $creados = 0;
        $errores = [];

        foreach ($dataRows as $i => $row) {
            $filaExcel = $i + 2;

            $desc     = strtoupper(trim((string) ($row[0] ?? '')));
            $unidTxt  = trim((string) ($row[1] ?? ''));
            $cantidad = is_numeric($row[2] ?? '') ? (int) $row[2] : null;
            $minimo   = is_numeric($row[3] ?? '') ? (int) $row[3] : null;
            $critico  = is_numeric($row[4] ?? '') ? (int) $row[4] : null;

            if ($desc === '') continue;

            if ($unidTxt === '') {
                $errores[] = "Fila {$filaExcel}: la columna 'unidad' estÃ¡ vacÃ­a.";
                continue;
            }
            if ($cantidad === null || $cantidad < 0) {
                $errores[] = "Fila {$filaExcel}: 'cantidad' debe ser un nÃºmero >= 0.";
                continue;
            }
            if ($minimo === null || $minimo < 0) {
                $errores[] = "Fila {$filaExcel}: 'minimo' debe ser un nÃºmero >= 0.";
                continue;
            }
            if ($critico === null || $critico < 0) {
                $errores[] = "Fila {$filaExcel}: 'critico' debe ser un nÃºmero >= 0.";
                continue;
            }

            $unidMedidaId = $unidIdx[$this->normalizarTextoCatalogo($unidTxt)] ?? null;
            $stockInicial = $manejaPres ? ($cantidad * $cantPres) : $cantidad;

            try {
                DB::transaction(function () use (
                    $desc, $unidTxt, $unidMedidaId, $stockInicial, $cantidad, $minimo, $critico,
                    $contenedorId, $categoria, $marcaId, $ccId,
                    $manejaPres, $tipoPres, $cantPres, $unidBase,
                    &$creados
                ) {
                    $producto = Producto::create([
                        'nombre'                => $desc,
                        'stock_actual'          => $stockInicial,
                        'stock_minimo'          => $minimo,
                        'stock_critico'         => $critico,
                        'contenedor'            => $contenedorId,
                        'unidad_medida_id'      => $unidMedidaId,
                        'unidad'                => $unidMedidaId ? null : $unidTxt,
                        'categoria_id'          => $categoria->id,
                        'marca_id'              => $marcaId,
                        'centro_costo_id'       => $ccId,
                        'es_servicio'           => false,
                        'tipo_item'             => 'producto',
                        'maneja_presentacion'   => $manejaPres,
                        'tipo_presentacion'     => $manejaPres ? $tipoPres : null,
                        'cantidad_presentacion' => $manejaPres ? $cantPres : null,
                        'unidad_base'           => $manejaPres ? $unidBase : null,
                    ]);

                    if ($stockInicial > 0) {
                        HistorialCambio::create([
                            'producto_id'    => $producto->id,
                            'nombre_producto' => $producto->nombre,
                            'usuario_id'     => Auth::id(),
                            'tipo'           => 'entrada',
                            'origen'         => 'catalogo',
                            'cantidad'       => $stockInicial,
                            'contenedor_id'  => $contenedorId,
                            'stock_anterior' => 0,
                            'stock_posterior'=> $stockInicial,
                        ]);
                    }

                    $creados++;
                });
            } catch (\Throwable $e) {
                $errores[] = "Fila {$filaExcel}: " . $e->getMessage();
            }
        }

        return response()->json([
            'ok'      => true,
            'creados' => $creados,
            'errores' => $errores,
        ]);
    }

    public function previewCargaMasiva(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $request->validate([
            'excel_catalogo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $rawRows = $this->leerExcelCatalogo($request->file('excel_catalogo'));

        if ($rawRows->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'El archivo Excel estÃ¡ vacÃ­o.']);
        }

        $encabezado = $rawRows->first()->map(fn($v) => strtolower(trim((string) $v)))->values();
        $esperados  = ['descripcion', 'unidad', 'cantidad', 'minimo', 'critico'];
        foreach ($esperados as $i => $col) {
            if (($encabezado[$i] ?? '') !== $col) {
                return response()->json([
                    'ok'    => false,
                    'error' => 'El archivo debe tener las columnas: descripcion, unidad, cantidad, minimo, critico (fila 1 encabezado).',
                ]);
            }
        }

        $rows = [];
        foreach ($rawRows->slice(1)->values() as $idx => $row) {
            $desc = strtoupper(trim((string) ($row[0] ?? '')));
            if ($desc === '') continue;
            $rows[] = [
                'fila'        => $idx + 2,
                'descripcion' => $desc,
                'unidad'      => trim((string) ($row[1] ?? '')),
                'cantidad'    => is_numeric($row[2] ?? null) ? max(0, (int) $row[2]) : 0,
                'minimo'      => is_numeric($row[3] ?? null) ? max(0, (int) $row[3]) : 0,
                'critico'     => is_numeric($row[4] ?? null) ? max(0, (int) $row[4]) : 0,
            ];
        }

        if (empty($rows)) {
            return response()->json(['ok' => false, 'error' => 'No se encontraron productos en el archivo (el cuerpo estÃ¡ vacÃ­o).']);
        }

        return response()->json(['ok' => true, 'rows' => $rows]);
    }

    public function confirmarCargaMasiva(Request $request): \Illuminate\Http\JsonResponse
    {
        abort_unless(auth()->user()->tienePermiso('catalogo'), 403);

        $contenedorId = $request->filled('contenedor_id') ? (int) $request->contenedor_id : null;
        $items        = $request->input('items', []);
        $ccId         = $this->ccId();

        $unidades = UnidadMedida::activas()->get(['id', 'nombre', 'abreviacion']);
        $unidIdx  = [];
        foreach ($unidades as $u) {
            $unidIdx[$this->normalizarTextoCatalogo($u->nombre)]      = $u->id;
            $unidIdx[$this->normalizarTextoCatalogo($u->abreviacion)] = $u->id;
        }

        $creados = 0;
        $errores = [];

        foreach ($items as $i => $item) {
            $fila        = (int) ($item['fila'] ?? ($i + 2));
            $nombre      = strtoupper(trim((string) ($item['nombre'] ?? '')));
            $unidTxt     = trim((string) ($item['unidad'] ?? ''));
            $cantidad    = max(0, (int) ($item['cantidad'] ?? 0));
            $minimo      = max(0, (int) ($item['minimo'] ?? 0));
            $critico     = max(0, (int) ($item['critico'] ?? 0));
            $catId       = !empty($item['categoria_id']) ? (int) $item['categoria_id'] : null;
            $marcaId     = !empty($item['marca_id'])     ? (int) $item['marca_id']     : Marca::idSinMarca();
            $itemContId  = !empty($item['contenedor_id']) ? (int) $item['contenedor_id'] : $contenedorId;
            $tipoItem    = in_array($item['tipo_item'] ?? '', ['producto','servicio','mantencion','arriendo'])
                           ? $item['tipo_item'] : 'producto';
            $manejaPres  = !empty($item['maneja_presentacion']);
            $tipoPres    = trim((string) ($item['tipo_presentacion'] ?? ''));
            $cantPres    = $manejaPres ? max(1, (int) ($item['cantidad_presentacion'] ?? 1)) : null;
            $unidBase    = $manejaPres ? trim((string) ($item['unidad_base'] ?? '')) : null;

            if ($nombre === '') { $errores[] = "Fila {$fila}: nombre vacÃ­o."; continue; }
            if ($unidTxt === '') { $errores[] = "Fila {$fila}: unidad vacÃ­a."; continue; }

            $unidMedidaId = $unidIdx[$this->normalizarTextoCatalogo($unidTxt)] ?? null;

            try {
                DB::transaction(function () use (
                    $nombre, $unidTxt, $unidMedidaId, $cantidad, $minimo, $critico,
                    $itemContId, $catId, $marcaId, $ccId, $tipoItem,
                    $manejaPres, $tipoPres, $cantPres, $unidBase, &$creados
                ) {
                    $producto = Producto::create([
                        'nombre'                  => $nombre,
                        'stock_actual'            => $cantidad,
                        'stock_minimo'            => $minimo,
                        'stock_critico'           => $critico,
                        'contenedor'              => $itemContId,
                        'unidad_medida_id'        => $unidMedidaId,
                        'unidad'                  => $unidMedidaId ? null : $unidTxt,
                        'categoria_id'            => $catId,
                        'marca_id'                => $marcaId,
                        'centro_costo_id'         => $ccId,
                        'es_servicio'             => $tipoItem !== 'producto',
                        'tipo_item'               => $tipoItem,
                        'maneja_presentacion'     => $manejaPres,
                        'tipo_presentacion'       => $manejaPres ? $tipoPres : null,
                        'cantidad_presentacion'   => $cantPres,
                        'unidad_base'             => $unidBase,
                    ]);

                    if ($cantidad > 0) {
                        HistorialCambio::create([
                            'producto_id'     => $producto->id,
                            'nombre_producto' => $producto->nombre,
                            'usuario_id'      => Auth::id(),
                            'tipo'            => 'entrada',
                            'origen'          => 'catalogo',
                            'cantidad'        => $cantidad,
                            'contenedor_id'   => $itemContId,
                            'stock_anterior'  => 0,
                            'stock_posterior' => $cantidad,
                        ]);
                    }
                    $creados++;
                });
            } catch (\Throwable $e) {
                $errores[] = "Fila {$fila} ({$nombre}): " . $e->getMessage();
            }
        }

        return response()->json(['ok' => true, 'creados' => $creados, 'errores' => $errores]);
    }

    private function leerExcelCatalogo(\Illuminate\Http\UploadedFile $file): \Illuminate\Support\Collection
    {
        $path   = $file->getRealPath();
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($path);
        $sheet       = $spreadsheet->getActiveSheet();
        $rawRows     = $sheet->toArray(null, true, true, false);

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return collect($rawRows)->map(fn($row) => collect(array_values($row)));
    }

    private function normalizarTextoCatalogo(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = str_replace(['Ã¡','Ã©','Ã­','Ã³','Ãº','Ã¼','Ã±'], ['a','e','i','o','u','u','n'], $s);
        return preg_replace('/[^a-z0-9]/', '', $s);
    }
}

