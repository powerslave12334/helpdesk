<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatTipoGps extends Model
{
    protected $table = 'tblCatTipoGps';
    protected $primaryKey = 'id_TipoGps';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
