<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SicdDocumento extends Model
{
    protected $table = 'sicd_documentos';

    protected $fillable = [
        'sicd_id',
        'tipo',
        'nombre_original',
        'ruta',
        'subido_por',
        'usuario_id',
    ];

    public function sicd()
    {
        return $this->belongsTo(Sicd::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}
