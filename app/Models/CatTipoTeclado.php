<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatTipoTeclado extends Model
{
    protected $table = 'tblCatTipoTeclado';
    protected $primaryKey = 'id_TipoTeclado';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
