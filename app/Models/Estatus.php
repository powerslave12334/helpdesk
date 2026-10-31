<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estatus extends Model
{
    protected $table = 'tblEstatus';
    protected $primaryKey = 'id_Estatus';
    public $timestamps = false;
    protected $fillable = ['descripcion', 'fechaAlta', 'activo', 'id_Modulos'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function modulo() { return $this->belongsTo(Modulo::class, 'id_Modulos'); }
}
