<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatProcesador extends Model
{
    protected $table = 'tblCatProcesador';
    protected $primaryKey = 'id_Procesador';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
