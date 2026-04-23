<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\Container;
use App\Models\Familia;
use App\Models\Producto;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $familias   = Familia::with(['categorias.productos'])->where('activo', true)->get();
        $containers = Container::orderBy('nombre')->get(['id', 'nombre']);
        $familiaActiva = (int) $request->get('familia', $familias->first()?->id ?? 0);

        return view('admin.productos.catalogo', compact('familias', 'containers', 'familiaActiva'));
    }

    public function storeFamilia(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:familias,nombre'],
        ], [
            'nombre.unique' => 'Ya existe una familia con ese nombre.',
        ]);

        $familia = Familia::create($data);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $familia->id, 'nombre' => $familia->nombre]);
        }

        return back()->with('success', 'Familia creada correctamente.');
    }

    public function storeCategoria(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'nombre'     => ['required', 'string', 'max:150',
                             Rule::unique('categorias')->where('familia_id', $request->familia_id)],
            'familia_id' => ['required', 'integer', 'exists:familias,id'],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre en esta familia.',
        ]);

        $categoria = Categoria::create($data);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'id' => $categoria->id, 'nombre' => $categoria->nombre]);
        }

        return back()->with('success', 'Categoría creada correctamente.');
    }

    public function updateCategoria(Request $request, Categoria $categoria)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:150',
                         Rule::unique('categorias')->where('familia_id', $categoria->familia_id)->ignore($categoria->id)],
        ], [
            'nombre.unique' => 'Ya existe una categoría con ese nombre en esta familia.',
        ]);

        $categoria->update($data);
        // Keep productos.nombre in sync
        Producto::where('categoria_id', $categoria->id)->update(['nombre' => $data['nombre']]);

        if ($request->ajax()) {
            return response()->json(['ok' => true, 'nombre' => $categoria->nombre]);
        }

        return back()->with('success', 'Categoría actualizada.');
    }

    public function buscarBarcode(Request $request)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $codigo = trim($request->get('codigo', ''));
        if (!$codigo) {
            return response()->json(['encontrado' => false, 'similares' => []]);
        }

        // Búsqueda exacta
        $producto = Producto::with(['categoria.familia'])
            ->where('codigo_barras', $codigo)
            ->first();

        if ($producto) {
            return response()->json([
                'encontrado' => true,
                'producto'   => [
                    'id'            => $producto->id,
                    'descripcion'   => $producto->descripcion,
                    'codigo_barras' => $producto->codigo_barras,
                    'categoria'     => $producto->categoria->nombre,
                    'familia'       => $producto->categoria->familia->nombre,
                    'stock_actual'  => $producto->stock_actual,
                ],
            ]);
        }

        // No encontrado → calcular similitud contra productos con código de barras
        $todos     = Producto::with(['categoria.familia'])->whereNotNull('codigo_barras')->get();
        $similares = [];

        foreach ($todos as $p) {
            similar_text($codigo, $p->codigo_barras, $pct);
            if ($pct >= 40) {
                $similares[] = [
                    'id'            => $p->id,
                    'descripcion'   => $p->descripcion,
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
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'descripcion'   => ['required', 'string', 'max:500'],
            'categoria_id'  => ['required', 'integer', 'exists:categorias,id'],
            'stock_minimo'  => ['required', 'integer', 'min:0'],
            'stock_critico' => ['required', 'integer', 'min:0'],
            'contenedor'    => ['nullable', 'integer', 'exists:containers,id'],
            'codigo_barras' => ['nullable', 'string', 'max:100', 'unique:productos,codigo_barras'],
        ], [
            'codigo_barras.unique' => 'Ese código de barras ya está asignado a otro producto.',
        ]);

        $categoria = Categoria::findOrFail($data['categoria_id']);

        $producto = Producto::create([
            'nombre'        => $categoria->nombre,
            'descripcion'   => $data['descripcion'],
            'codigo_barras' => $data['codigo_barras'] ?? null,
            'stock_actual'  => 0,
            'stock_minimo'  => $data['stock_minimo'],
            'stock_critico' => $data['stock_critico'],
            'contenedor'    => $data['contenedor'] ?? null,
            'categoria_id'  => $data['categoria_id'],
        ]);

        if ($request->ajax()) {
            return response()->json([
                'ok'  => true,
                'id'  => $producto->id,
                'descripcion'  => $producto->descripcion,
                'stock_minimo' => $producto->stock_minimo,
                'stock_critico'=> $producto->stock_critico,
            ]);
        }

        return back()->with('success', 'Producto agregado al catálogo.');
    }

    public function asociarBarcode(Request $request, Producto $producto)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'codigo_barras' => ['required', 'string', 'max:100', 'unique:productos,codigo_barras'],
        ], [
            'codigo_barras.unique' => 'Ese código ya está asignado a otro producto.',
        ]);

        // Crear nuevo producto con los mismos datos pero distinto código de barras
        $nuevo = Producto::create([
            'nombre'        => $producto->nombre,
            'descripcion'   => $producto->descripcion,
            'codigo_barras' => $data['codigo_barras'],
            'stock_actual'  => 0,
            'stock_minimo'  => $producto->stock_minimo,
            'stock_critico' => $producto->stock_critico,
            'contenedor'    => $producto->contenedor,
            'categoria_id'  => $producto->categoria_id,
        ]);

        return response()->json(['ok' => true, 'id' => $nuevo->id, 'descripcion' => $nuevo->descripcion]);
    }

    public function updateProducto(Request $request, Producto $producto)
    {
        abort_unless(auth()->user()->esAdmin(), 403);

        $data = $request->validate([
            'descripcion'  => ['required', 'string', 'max:500'],
            'stock_minimo' => ['required', 'integer', 'min:0'],
            'stock_critico'=> ['required', 'integer', 'min:0'],
            'contenedor'   => ['nullable', 'integer', 'exists:containers,id'],
        ]);

        $producto->update([
            'descripcion'  => $data['descripcion'],
            'stock_minimo' => $data['stock_minimo'],
            'stock_critico'=> $data['stock_critico'],
            'contenedor'   => $data['contenedor'] ?? null,
        ]);

        if ($request->ajax()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('success', 'Producto actualizado.');
    }
}
