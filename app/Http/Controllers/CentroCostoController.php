<?php

namespace App\Http\Controllers;

use App\Models\CentroCosto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CentroCostoController extends Controller
{
    private function authDev()
    {
        abort_unless(auth()->user()->esDev(), 403);
    }

    /**
     * Busca el nombre completo del acrónimo en la BD remota de solicitudes.
     * Primero prueba coincidencia exacta, luego por prefijo (antes del primer paréntesis).
     */
    private function buscarEnRemota(string $acronimo): ?string
    {
        try {
            $row = DB::connection('sicd_externa')
                ->table('centros')
                ->where('acronimo', $acronimo)
                ->first();

            if ($row) {
                return trim($row->nombre);
            }

            // Intentar con el prefijo si el acrónimo incluye sufijo tipo "(RAMO)"
            $prefijo = preg_replace('/[^A-Za-z].*$/u', '', $acronimo);
            if ($prefijo && $prefijo !== $acronimo) {
                $row = DB::connection('sicd_externa')
                    ->table('centros')
                    ->where('acronimo', $prefijo)
                    ->first();

                if ($row) {
                    return trim($row->nombre);
                }
            }
        } catch (\Throwable) {
            // Si la BD remota no responde, continuar sin nombre completo
        }

        return null;
    }

    public function verificar(Request $request)
    {
        $this->authDev();
        $acronimo = strtoupper(trim($request->query('nombre', '')));
        if ($acronimo === '') {
            return response()->json(['existe' => false, 'en_remota' => false, 'mensaje' => '']);
        }

        $existe = CentroCosto::where('acronimo', $acronimo)->exists();
        if ($existe) {
            return response()->json([
                'existe'     => true,
                'en_remota'  => false,
                'mensaje'    => "El centro de costo \"{$acronimo}\" ya existe.",
            ]);
        }

        $nombreCompleto = $this->buscarEnRemota($acronimo);

        return response()->json([
            'existe'          => false,
            'en_remota'       => $nombreCompleto !== null,
            'nombre_completo' => $nombreCompleto,
            'mensaje'         => $nombreCompleto !== null
                ? "Encontrado en base de datos: {$nombreCompleto}"
                : "El centro de costo \"{$acronimo}\" no existe aún.",
        ]);
    }

    public function store(Request $request)
    {
        $this->authDev();
        $acronimo = strtoupper(trim($request->input('nombre', '')));

        if ($acronimo === '') {
            return response()->json(['ok' => false, 'mensaje' => 'El nombre no puede estar vacío.']);
        }
        if (CentroCosto::where('acronimo', $acronimo)->exists()) {
            return response()->json(['ok' => false, 'mensaje' => "El centro de costo \"{$acronimo}\" ya existe."]);
        }

        // Usar nombre_completo enviado desde el cliente, o buscar en remota como fallback
        $nombreCompleto = trim($request->input('nombre_completo', '')) ?: $this->buscarEnRemota($acronimo);

        $cc = CentroCosto::create([
            'acronimo'        => $acronimo,
            'nombre_completo' => $nombreCompleto ?: null,
        ]);

        return response()->json([
            'ok'              => true,
            'id'              => $cc->id,
            'nombre'          => $acronimo,
            'nombre_completo' => $nombreCompleto,
            'mensaje'         => "Centro de costo \"{$acronimo}\" creado.",
        ]);
    }
}
