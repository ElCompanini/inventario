<?php

namespace App\Services;

use App\Exceptions\MercadoPublicoException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MercadoPublicoService
{
    private string $baseUrl;
    private string $ticket;
    private int    $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.mercadopublico.base_url',
            'https://api.mercadopublico.cl/servicios/v1/publico'), '/');
        $this->ticket  = config('services.mercadopublico.ticket', '');
        $this->timeout = (int) config('services.mercadopublico.timeout', 20);
    }

    // ─── Búsqueda combinada (OC o Licitación) ────────────────────────────────

    /**
     * Intenta encontrar el código como OC primero, luego como Licitación.
     * Devuelve ['encontrado', 'tipo_busqueda', 'codigo_oc', 'codigo_lic', 'data']
     */
    public function consultarCualquierCodigo(string $codigo): array
    {
        // 1. Intentar como OC con hasta 2 reintentos para errores de rate-limit (10500).
        // consultarOC() devuelve null para 10300 (formato no reconocido) o Cantidad=0 (no existe).
        // Lanza excepción para errores reales (10500, conexión, etc.) que aquí sí reintentamos.
        $dataOC   = null;
        $attempts = 0;
        while ($attempts <= 2) {
            try {
                $dataOC = $this->consultarOC($codigo);
                break;
            } catch (MercadoPublicoException $e) {
                if ($attempts < 2 && str_contains($e->getMessage(), 'simultáneas')) {
                    $attempts++;
                    usleep(700_000 * $attempts); // 0.7s, 1.4s
                    continue;
                }
                throw $e; // Otro error o último reintento: propagar al frontend
            }
        }

        if ($dataOC !== null) {
            return [
                'encontrado'    => true,
                'tipo_busqueda' => 'oc',
                'codigo_oc'     => $codigo,
                'codigo_lic'    => $dataOC['codigo_licitacion'] ?: null,
                'data'          => $dataOC,
            ];
        }

        // 2. Intentar como Licitación
        $dataLic = $this->consultarLicitacion($codigo);

        if ($dataLic !== null) {
            return [
                'encontrado'    => true,
                'tipo_busqueda' => 'licitacion',
                'codigo_oc'     => null,
                'codigo_lic'    => $codigo,
                'data'          => $dataLic,
            ];
        }

        return [
            'encontrado'    => false,
            'tipo_busqueda' => null,
            'codigo_oc'     => null,
            'codigo_lic'    => null,
            'data'          => null,
        ];
    }

    // ─── OC ──────────────────────────────────────────────────────────────────

    /**
     * Consulta una Orden de Compra por su código oficial.
     * Retorna array con datos o null si no existe.
     */
    public function consultarOC(string $codigo): ?array
    {
        $url = $this->baseUrl . '/ordenesdecompra.xml';
        Log::debug('[MercadoPublico] consultarOC', ['codigo' => $codigo]);

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout($this->timeout)
                ->get($url, ['codigo' => $codigo, 'ticket' => $this->ticket]);
        } catch (ConnectionException $e) {
            Log::error('[MercadoPublico] Fallo de conexión OC', ['codigo' => $codigo, 'error' => $e->getMessage()]);
            throw new MercadoPublicoException(
                'No se pudo conectar con la API de Mercado Público. ' . $e->getMessage(), 0, $e
            );
        }

        $body = trim($response->body());
        if (empty($body)) {
            throw new MercadoPublicoException('La API de Mercado Público devolvió una respuesta vacía.');
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        if ($xml === false) {
            throw new MercadoPublicoException('Respuesta XML inválida desde Mercado Público.');
        }

        if ($xml->getName() === 'Error') {
            $codError = (string) ($xml->Codigo ?? 'SIN_CODIGO');
            $mensaje  = (string) ($xml->Mensaje ?? 'Error desconocido');
            Log::warning('[MercadoPublico] Error OC', ['codigo' => $codigo, 'api_codigo' => $codError]);

            if ($codError === '10300') {
                // Formato no reconocido como OC — devolvemos null para que el caller pruebe como licitación.
                return null;
            }
            if ($codError === '10500') {
                throw new MercadoPublicoException(
                    'La API de Mercado Público detectó peticiones simultáneas. Espera unos segundos e intenta de nuevo.'
                );
            }
            throw new MercadoPublicoException("Error Mercado Público [{$codError}]: {$mensaje}");
        }

        $cantidad = (int) ($xml->Cantidad ?? 0);
        if ($cantidad === 0) return null;

        // La etiqueta real es <OrdenCompra>, no <Orden>
        $orden = $xml->Listado->OrdenCompra ?? null;
        if (!$orden) return null;

        return $this->parseOrden($orden);
    }

    // ─── Licitación ──────────────────────────────────────────────────────────

    /**
     * Consulta una Licitación por su código.
     * Retorna array con datos o null si no existe / formato inválido.
     */
    public function consultarLicitacion(string $codigo): ?array
    {
        $url = $this->baseUrl . '/licitaciones.xml';
        Log::debug('[MercadoPublico] consultarLicitacion', ['codigo' => $codigo]);

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout($this->timeout)
                ->get($url, ['codigo' => $codigo, 'ticket' => $this->ticket]);
        } catch (ConnectionException $e) {
            Log::error('[MercadoPublico] Fallo de conexión Licitación', ['codigo' => $codigo, 'error' => $e->getMessage()]);
            throw new MercadoPublicoException(
                'No se pudo conectar con la API de Mercado Público. ' . $e->getMessage(), 0, $e
            );
        }

        $body = trim($response->body());
        if (empty($body)) return null;

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        libxml_clear_errors();
        if ($xml === false) return null;

        if ($xml->getName() === 'Error') {
            $codError = (string) ($xml->Codigo ?? '');
            if (in_array($codError, ['10300', '10500'])) return null;
            $mensaje = (string) ($xml->Mensaje ?? 'Error');
            throw new MercadoPublicoException("Error MP [{$codError}]: {$mensaje}");
        }

        $cantidad = (int) ($xml->Cantidad ?? 0);
        if ($cantidad === 0) return null;

        $lic = $xml->Listado->Licitacion ?? null;
        if (!$lic) return null;

        return $this->parseLicitacion($lic);
    }

    // ─── Tipo de proceso ──────────────────────────────────────────────────────

    public function detectarTipoProceso(string $tipo): array
    {
        $t = mb_strtolower(trim($tipo));

        if (str_contains($t, 'ágil') || str_contains($t, 'agil') || str_contains($t, 'menor')) {
            return ['label' => 'Compra Ágil',    'icono' => '⚡', 'clase' => 'bg-green-100 text-green-800 border-green-200'];
        }
        if (str_contains($t, 'licitac') || in_array($t, ['le', 'lp', 'l1', 'lr', 'ld'])) {
            return ['label' => 'Licitación',      'icono' => '📋', 'clase' => 'bg-indigo-100 text-indigo-800 border-indigo-200'];
        }
        if (str_contains($t, 'trato') || str_contains($t, 'directo') || $t === 'td') {
            return ['label' => 'Trato Directo',   'icono' => '🤝', 'clase' => 'bg-amber-100 text-amber-800 border-amber-200'];
        }
        if (str_contains($t, 'convenio') || str_contains($t, 'marco') || in_array($t, ['co', 'bc'])) {
            return ['label' => 'Convenio Marco',  'icono' => '📑', 'clase' => 'bg-blue-100 text-blue-800 border-blue-200'];
        }
        // SE = Servicio, otros tipos de OC
        if ($t === 'se') {
            return ['label' => 'Servicio',        'icono' => '🔧', 'clase' => 'bg-purple-100 text-purple-800 border-purple-200'];
        }
        return ['label' => $tipo ?: 'OC',         'icono' => '📄', 'clase' => 'bg-gray-100 text-gray-700 border-gray-200'];
    }

    // ─── Ping ─────────────────────────────────────────────────────────────────

    public function ping(): bool
    {
        try {
            $r = Http::withOptions(['verify' => false])
                ->timeout(6)
                ->get($this->baseUrl . '/ordenesdecompra.xml', [
                    'codigo' => 'PING-TEST',
                    'ticket' => $this->ticket,
                ]);
            return $r->status() > 0;
        } catch (\Exception) {
            return false;
        }
    }

    // ─── Parsers privados ─────────────────────────────────────────────────────

    private function parseOrden(\SimpleXMLElement $orden): array
    {
        $total     = ($v = (string)($orden->Total     ?? '')) !== '' ? (int)$v : null;
        $totalNeto = ($v = (string)($orden->TotalNeto ?? '')) !== '' ? (int)$v : null;
        $impuestos = ($v = (string)($orden->Impuestos ?? '')) !== '' ? (int)$v : null;

        $fechaEnvio = trim((string)($orden->Fechas->FechaEnvio ?? ''));

        // Ítems del pedido: <Items><Listado><Item>
        $items = [];
        $xmlItems = $orden->Items->Listado->Item ?? null;
        if ($xmlItems) {
            foreach (is_iterable($xmlItems) ? $xmlItems : [$xmlItems] as $it) {
                $items[] = [
                    'codigo'                   => trim((string)($it->CodigoProducto          ?? '')),
                    'nombre'                   => trim((string)($it->Producto                ?? '')),
                    'categoria'                => trim((string)($it->Categoria               ?? '')),
                    'especificacion_comprador' => trim((string)($it->EspecificacionComprador  ?? '')),
                    'especificacion_proveedor' => trim((string)($it->EspecificacionProveedor  ?? '')),
                    'cantidad'                 => trim((string)($it->Cantidad                ?? '')),
                    'precio_unitario'          => ($v = trim((string)($it->PrecioNeto        ?? ''))) !== '' ? (float)$v : null,
                    'descuento'                => ($v = trim((string)($it->TotalDescuentos   ?? ''))) !== '' ? (float)$v : 0.0,
                    'cargo'                    => ($v = trim((string)($it->TotalCargos       ?? ''))) !== '' ? (float)$v : 0.0,
                    'total'                    => ($v = trim((string)($it->Total             ?? ''))) !== '' ? (float)$v : null,
                ];
            }
        }

        return [
            'codigo'             => trim((string)($orden->Codigo             ?? '')),
            'nombre'             => trim((string)($orden->Nombre             ?? '')),
            'descripcion'        => trim((string)($orden->Descripcion        ?? '')),
            'tipo'               => trim((string)($orden->Tipo               ?? '')),
            'tipo_moneda'        => trim((string)($orden->TipoMoneda         ?? '')),
            'estado'             => trim((string)($orden->Estado             ?? '')),
            'estado_proveedor'   => trim((string)($orden->EstadoProveedor    ?? '')),
            'fecha_envio'        => $fechaEnvio,
            'total_neto'         => $totalNeto,
            'total'              => $total,
            'impuestos'          => $impuestos,
            'items'              => $items,
            'codigo_licitacion'  => trim((string)($orden->CodigoLicitacion   ?? '')),
            'contacto'           => trim((string)($orden->Comprador->NombreContacto ?? '')),
            'proveedor_nombre'   => trim((string)($orden->Proveedor->Nombre        ?? '')),
            'proveedor_rut'      => trim((string)($orden->Proveedor->RutSucursal   ?? '')),
        ];
    }

    private function parseLicitacion(\SimpleXMLElement $lic): array
    {
        // Calcular total adjudicado sumando items
        $totalAdjudicado = 0;
        $proveedor       = '';
        $rutProveedor    = '';

        if (isset($lic->Items->Listado->Item)) {
            $items = $lic->Items->Listado->Item;
            // SimpleXML puede devolver un único elemento o un iterador
            foreach (is_iterable($items) ? $items : [$items] as $item) {
                if (isset($item->Adjudicacion)) {
                    $cant   = (float)($item->Adjudicacion->CantidadAdjudicada ?? 0);
                    $precio = (float)($item->Adjudicacion->MontoUnitario      ?? 0);
                    $totalAdjudicado += (int)($cant * $precio);
                    if (empty($proveedor)) {
                        $proveedor    = trim((string)($item->Adjudicacion->NombreProveedor ?? ''));
                        $rutProveedor = trim((string)($item->Adjudicacion->RutProveedor   ?? ''));
                    }
                }
            }
        }

        return [
            'codigo'             => trim((string)($lic->CodigoExterno         ?? '')),
            'nombre'             => trim((string)($lic->Nombre                ?? '')),
            'descripcion'        => trim((string)($lic->Descripcion           ?? '')),
            'tipo'               => trim((string)($lic->Tipo                  ?? '')),
            'estado'             => trim((string)($lic->Estado                ?? '')),
            'moneda'             => trim((string)($lic->Moneda                ?? '')),
            'monto_estimado'     => (int)(string)($lic->MontoEstimado         ?? 0),
            'total_adjudicado'   => $totalAdjudicado,
            'proveedor_nombre'   => $proveedor,
            'proveedor_rut'      => $rutProveedor,
            'organismo'          => trim((string)($lic->Comprador->NombreOrganismo ?? '')),
            'fecha_cierre'       => trim((string)($lic->Fechas->FechaCierre        ?? '')),
            'fecha_adjudicacion' => trim((string)($lic->Adjudicacion->Fecha        ?? '')),
            'codigo_licitacion'  => '',
        ];
    }
}
