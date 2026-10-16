<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatSistemaOperativo extends Model
{
    protected $table = 'tblCatSistemaOperativo';
    protected $primaryKey = 'id_SistemaOperativo';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
