<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Boleta extends Model
{
    use SoftDeletes;
    protected $table = 'boletas';

    protected $fillable = [
        'archivo_nombre',
        'archivo_blob',
        'archivo_mime',
        'archivo_ruta',
    ];
}
