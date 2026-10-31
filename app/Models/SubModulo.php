<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubModulo extends Model
{
    protected $table = 'tblSubModulos';
    protected $primaryKey = 'id_SubModulos';
    public $timestamps = false;
    protected $fillable = ['id_Modulos', 'nombre', 'controller', 'clave', 'action', 'fechaAlta', 'activo', 'orden'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function modulo() { return $this->belongsTo(Modulo::class, 'id_Modulos'); }
}
