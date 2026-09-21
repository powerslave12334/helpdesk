<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipoTicket extends Model
{
    protected $table = 'tblTipoEquipoTicket';
    protected $primaryKey = 'id_TipoEquipoTicket';
    public $timestamps = false;
    protected $fillable = ['clave', 'descripcion', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
