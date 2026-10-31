<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatTipoAlmacenamiento extends Model
{
    protected $table = 'tblCatTipoAlmacenamiento';
    protected $primaryKey = 'id_TipoAlmacenamiento';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
