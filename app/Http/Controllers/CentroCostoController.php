<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CentroCostoController extends Controller
{
    private function authDev()
    {
        abort_unless(auth()->user()->esDev(), 403);
    }

    public function verificar(Request $request)
    {
        $this->authDev();
        $nombre = strtoupper(trim($request->query('nombre', '')));
        if ($nombre === '') {
            return response()->json(['existe' => false, 'mensaje' => '']);
        }
        $existe = \App\Models\CentroCosto::where('nombre', $nombre)->exists();
        return response()->json([
            'existe'  => $existe,
            'mensaje' => $existe
                ? "El centro de costo \"{$nombre}\" ya existe."
                : "El centro de costo \"{$nombre}\" no existe aún.",
        ]);
    }

    public function store(Request $request)
    {
        $this->authDev();
        $nombre = strtoupper(trim($request->input('nombre', '')));

        if ($nombre === '') {
            return response()->json(['ok' => false, 'mensaje' => 'El nombre no puede estar vacío.']);
        }
        if (\App\Models\CentroCosto::where('nombre', $nombre)->exists()) {
            return response()->json(['ok' => false, 'mensaje' => "El centro de costo \"{$nombre}\" ya existe."]);
        }

        \App\Models\CentroCosto::create(['nombre' => $nombre]);
        return response()->json(['ok' => true, 'nombre' => $nombre, 'mensaje' => "Centro de costo \"{$nombre}\" creado."]);
    }
}
