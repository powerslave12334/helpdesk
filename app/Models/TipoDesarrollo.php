<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDesarrollo extends Model
{
    protected $table = 'tblTipoDesarrollo';
    protected $primaryKey = 'id_TipoDesarrollo';
    public $timestamps = false;
    protected $fillable = ['nombre', 'enabled', 'descripcion'];
    protected $casts = ['enabled' => 'boolean'];
}
