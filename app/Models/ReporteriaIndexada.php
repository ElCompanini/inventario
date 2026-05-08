<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ReporteriaIndexada extends Model
{
    use SoftDeletes;

    protected $table = 'reporterias_indexadas';

    protected $fillable = [
        'uuid',
        'tipo',
        'nombre',
        'modulo',
        'formato',
        'usuario_id',
        'usuario_nombre',
        'nombre_archivo',
        'ruta_archivo',
        'tamaño_bytes',
        'hash_archivo',
        'filtros',
        'estado',
        'notas',
    ];

    protected $casts = [
        'filtros'      => 'array',
        'tamaño_bytes' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $r) {
            if (empty($r->uuid)) {
                $r->uuid = Str::uuid()->toString();
            }
        });
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /** Tamaño formateado (KB / MB) */
    public function tamañoFormateado(): string
    {
        if (!$this->tamaño_bytes) return '—';
        if ($this->tamaño_bytes < 1024)       return $this->tamaño_bytes . ' B';
        if ($this->tamaño_bytes < 1048576)    return round($this->tamaño_bytes / 1024, 1) . ' KB';
        return round($this->tamaño_bytes / 1048576, 2) . ' MB';
    }

    /** ¿Tiene archivo físico disponible? */
    public function tieneArchivo(): bool
    {
        return !empty($this->ruta_archivo) &&
               \Illuminate\Support\Facades\Storage::disk('local')->exists($this->ruta_archivo);
    }
}
