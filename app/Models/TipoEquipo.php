<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    protected $table = 'tblTipoEquipo';
    protected $primaryKey = 'id_TipoEquipo';
    public $timestamps = false;
    protected $fillable = ['descripcion', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function equipos() { return $this->hasMany(Equipo::class, 'id_TipoEquipo'); }
}
