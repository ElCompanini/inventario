<?php

namespace App\Jobs;

use App\Mail\NotificacionMovimientoInventarioMail;
use App\Models\HistorialCambio;
use App\Models\HistorialCorreo;
use App\Models\Solicitud;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EnviarNotificacionErpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300, 900];

    public function __construct(
        public string $tipoCorreo,
        public ?int $historialCambioId = null,
        public ?int $solicitudId = null,
        public array $metadata = []
    ) {
        $this->onQueue(config('inventory_notifications.queue', 'default'));
    }

    public function handle(): void
    {
        if (!config('inventory_notifications.enabled', true)) {
            return;
        }

        if ($this->yaFueProcesado()) {
            return;
        }

        $contexto = $this->resolverContexto();

        if ($contexto === null) {
            $this->registrarHistorial([], null, 'error', 'No se pudo resolver el movimiento ERP para correo.');
            return;
        }

        $centroCostoId = $contexto['centro_costo_id'] ?? null;

        if (!$centroCostoId) {
            $this->registrarHistorial([], null, 'sin_destinatarios', 'Movimiento sin centro de costo asociado.');
            return;
        }

        if ($this->debeSuprimirPorDebounce($centroCostoId, $contexto)) {
            $this->registrarHistorial(
                [],
                $centroCostoId,
                'suprimido',
                'Alerta repetida dentro de la ventana de control.',
                $contexto
            );
            return;
        }

        $destinatarios = $this->destinatariosPorCentroCosto($centroCostoId);

        $historial = $this->registrarHistorial(
            $destinatarios,
            $centroCostoId,
            $destinatarios === [] ? 'sin_destinatarios' : 'pendiente',
            null,
            $contexto
        );

        if ($destinatarios === []) {
            return;
        }

        try {
            Mail::to(array_column($destinatarios, 'email'))
                ->send(new NotificacionMovimientoInventarioMail($this->tipoCorreo, $contexto));

            $historial->update([
                'estado' => 'enviado',
                'enviado_at' => now(),
                'error' => null,
            ]);
        } catch (\Throwable $e) {
            $historial->update([
                'estado' => 'error',
                'error' => $e->getMessage(),
            ]);

            Log::error('Fallo envio correo ERP', [
                'tipo_correo' => $this->tipoCorreo,
                'historial_cambio_id' => $this->historialCambioId,
                'solicitud_id' => $this->solicitudId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    private function yaFueProcesado(): bool
    {
        return HistorialCorreo::query()
            ->where('tipo_correo', $this->tipoCorreo)
            ->when($this->historialCambioId, fn($q) => $q->where('historial_cambio_id', $this->historialCambioId))
            ->when($this->solicitudId, fn($q) => $q->where('solicitud_id', $this->solicitudId))
            ->when(
                $this->tipoCorreo === 'recepcion_oc' && !empty($this->metadata['orden_compra_id']),
                fn($q) => $q->where('metadata->orden_compra_id', $this->metadata['orden_compra_id'])
            )
            ->whereIn('estado', ['pendiente', 'enviado'])
            ->exists();
    }

    private function resolverContexto(): ?array
    {
        // Carga masiva: el contexto viene completo en metadata
        if ($this->tipoCorreo === 'carga_masiva') {
            $meta = $this->metadata;
            if (empty($meta['centro_costo_id'])) return null;
            return [
                'tipo_correo'    => 'carga_masiva',
                'tipo_label'     => 'Carga masiva de stock',
                'centro_costo_id'=> $meta['centro_costo_id'],
                'usuario'        => $meta['usuario'] ?? '—',
                'codigo_sicd'    => $meta['codigo_sicd'] ?? null,
                'productos'      => $meta['productos'] ?? [],
                'total'          => $meta['total'] ?? 0,
                'fecha'          => now(),
                'documento'      => $meta['codigo_sicd'] ? 'SICD ' . $meta['codigo_sicd'] : null,
                'producto'       => null,
                'cantidad'       => null,
                'container'      => null,
                'centro_costo'   => null,
                'motivo'         => null,
                'referencia'     => null,
                'stock_anterior' => null,
                'stock_posterior'=> null,
            ];
        }

        // Recepción de Orden de Compra: un único correo con todos los productos
        if ($this->tipoCorreo === 'recepcion_oc') {
            $meta = $this->metadata;
            if (empty($meta['centro_costo_id'])) return null;
            $cc = \App\Models\CentroCosto::find($meta['centro_costo_id']);
            return [
                'tipo_correo'     => 'recepcion_oc',
                'tipo_label'      => 'Recepción de Orden de Compra',
                'centro_costo_id' => $meta['centro_costo_id'],
                'centro_costo'    => $cc?->acronimo,
                'numero_oc'       => $meta['numero_oc'] ?? null,
                'usuario'         => $meta['usuario'] ?? '—',
                'productos'       => $meta['productos'] ?? [],
                'total'           => $meta['total'] ?? 0,
                'pendientes'      => $meta['pendientes'] ?? [],
                'fecha'           => now(),
                'documento'       => $meta['numero_oc'] ? 'OC ' . $meta['numero_oc'] : null,
                'producto'        => null,
                'cantidad'        => null,
                'container'       => null,
                'motivo'          => null,
                'referencia'      => null,
                'stock_anterior'  => null,
                'stock_posterior' => null,
            ];
        }

        if ($this->historialCambioId) {
            $mov = HistorialCambio::withTrashed()
                ->with([
                    'producto' => fn($q) => $q->withoutGlobalScopes()->with(['container', 'centroCosto']),
                    'container.centroCosto',
                    'usuario',
                    'usuarioEjecutor',
                ])
                ->find($this->historialCambioId);

            if (!$mov) {
                return null;
            }

            $producto = $mov->producto;
            $container = $mov->container ?: $producto?->container;
            $centroCosto = $container?->centroCosto ?: $producto?->centroCosto;

            return [
                'tipo_correo' => $this->tipoCorreo,
                'tipo_label' => $this->labelTipo($this->tipoCorreo),
                'producto' => $mov->nombre_producto ?: $producto?->nombre,
                'cantidad' => (int) $mov->cantidad,
                'container' => $container?->nombre,
                'centro_costo' => $centroCosto?->acronimo,
                'centro_costo_id' => $centroCosto?->id,
                'usuario' => $mov->usuarioEjecutor?->name ?: $mov->usuario?->name ?: $mov->aprobado_por,
                'motivo' => $mov->motivo,
                'fecha' => $mov->created_at,
                'documento' => $mov->doc_origen ?: $mov->codigo_movimiento,
                'referencia' => $mov->doc_referencia,
                'stock_anterior' => $mov->stock_anterior,
                'stock_posterior' => $mov->stock_posterior,
                'origen_type' => HistorialCambio::class,
                'origen_id' => $mov->id,
                'producto_id' => $mov->producto_id,
                'contenedor_id' => $mov->contenedor_id ?: $producto?->contenedor,
            ];
        }

        if ($this->solicitudId) {
            $solicitud = Solicitud::with([
                'producto' => fn($q) => $q->withoutGlobalScopes()->with(['container', 'centroCosto']),
                'usuario',
            ])->find($this->solicitudId);

            if (!$solicitud) {
                return null;
            }

            $producto = $solicitud->producto;
            $container = $producto?->container;
            $centroCosto = $container?->centroCosto ?: $producto?->centroCosto;

            return [
                'tipo_correo' => $this->tipoCorreo,
                'tipo_label' => $this->labelTipo($this->tipoCorreo),
                'producto' => $producto?->nombre,
                'cantidad' => (int) $solicitud->cantidad,
                'container' => $container?->nombre,
                'centro_costo' => $centroCosto?->acronimo,
                'centro_costo_id' => $centroCosto?->id,
                'usuario' => $solicitud->usuario?->name,
                'motivo' => $solicitud->motivo,
                'fecha' => $solicitud->created_at,
                'documento' => 'SOL-' . str_pad((string) $solicitud->id, 6, '0', STR_PAD_LEFT),
                'referencia' => null,
                'stock_anterior' => null,
                'stock_posterior' => null,
                'origen_type' => Solicitud::class,
                'origen_id' => $solicitud->id,
                'producto_id' => $producto?->id,
                'contenedor_id' => $producto?->contenedor,
            ];
        }

        return null;
    }

    private function destinatariosPorCentroCosto(int $centroCostoId): array
    {
        return User::withoutGlobalScopes()
            ->where('activo', true)
            ->where('centro_costo_id', $centroCostoId)
            ->whereNotNull('email')
            ->get(['id', 'name', 'email'])
            ->filter(fn(User $user) => filter_var($user->email, FILTER_VALIDATE_EMAIL))
            ->map(fn(User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])
            ->values()
            ->all();
    }

    private function registrarHistorial(
        array $destinatarios,
        ?int $centroCostoId,
        string $estado,
        ?string $error = null,
        array $contexto = []
    ): HistorialCorreo
    {
        $metadata = array_merge($this->metadata, array_filter([
            'producto_id' => $contexto['producto_id'] ?? null,
            'contenedor_id' => $contexto['contenedor_id'] ?? null,
            'documento' => $contexto['documento'] ?? null,
            'referencia' => $contexto['referencia'] ?? null,
        ], fn($value) => $value !== null));

        return HistorialCorreo::create([
            'tipo_correo' => $this->tipoCorreo,
            'historial_cambio_id' => $this->historialCambioId,
            'solicitud_id' => $this->solicitudId,
            'centro_costo_id' => $centroCostoId,
            'origen_type' => $this->historialCambioId ? HistorialCambio::class : ($this->solicitudId ? Solicitud::class : null),
            'origen_id' => $this->historialCambioId ?: $this->solicitudId,
            'destinatarios' => $destinatarios,
            'metadata' => $metadata,
            'estado' => $estado,
            'error' => $error,
            'enviado_at' => $estado === 'enviado' ? now() : null,
        ]);
    }

    private function debeSuprimirPorDebounce(int $centroCostoId, array $contexto): bool
    {
        if (!in_array($this->tipoCorreo, ['stock_minimo', 'stock_critico'], true)) {
            return false;
        }

        $horas = max(1, (int) config('inventory_notifications.stock_alert_debounce_hours', 6));

        return HistorialCorreo::query()
            ->where('tipo_correo', $this->tipoCorreo)
            ->where('centro_costo_id', $centroCostoId)
            ->where('created_at', '>=', now()->subHours($horas))
            ->whereIn('estado', ['pendiente', 'enviado'])
            ->where('metadata->producto_id', $contexto['producto_id'] ?? null)
            ->where('metadata->contenedor_id', $contexto['contenedor_id'] ?? null)
            ->exists();
    }

    private function labelTipo(string $tipo): string
    {
        return match ($tipo) {
            'solicitud_productos' => 'Solicitud de productos',
            'retiro_productos' => 'Retiro de productos',
            'armado_equipos' => 'Armado de equipos',
            'ingreso_stock' => 'Ingreso de stock',
            'carga_masiva'  => 'Carga masiva de stock',
            'recepcion_oc'  => 'Recepción de Orden de Compra',
            'devolucion_productos' => 'Devolucion de productos',
            'stock_minimo' => 'Stock minimo',
            'stock_critico' => 'Stock critico',
            default => 'Movimiento de inventario',
        };
    }
}
