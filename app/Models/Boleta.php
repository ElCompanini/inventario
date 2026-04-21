<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Boleta extends Model
{
    protected $table = 'boletas';

    protected $fillable = [
        'archivo_nombre',
        'archivo_blob',
        'archivo_mime',
        'archivo_ruta',
    ];
}
