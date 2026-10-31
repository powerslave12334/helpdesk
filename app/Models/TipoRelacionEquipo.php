<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoRelacionEquipo extends Model
{
    protected $table = 'tblTipoRelacionEquipo';
    protected $primaryKey = 'id_TipoRelacionEquipo';
    public $timestamps = false;
    protected $fillable = ['descripcion', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function relaciones() { return $this->hasMany(EquipoRelacion::class, 'id_TipoRelacionEquipo'); }
}
