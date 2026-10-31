<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatTipoImpresora extends Model
{
    protected $table = 'tblCatTipoImpresora';
    protected $primaryKey = 'id_TipoImpresora';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
