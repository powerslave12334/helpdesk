<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatConectividad extends Model
{
    protected $table = 'tblCatConectividad';
    protected $primaryKey = 'id_Conectividad';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
