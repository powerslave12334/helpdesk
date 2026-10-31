<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Oficina extends Model
{
    protected $table = 'tblOficinas';
    protected $primaryKey = 'id_Oficinas';
    public $timestamps = false;
    protected $fillable = ['nombre', 'descripcion', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
