<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accion extends Model
{
    protected $table = 'tblAcciones';
    protected $primaryKey = 'id_Accion';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
