<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolModulo extends Model
{
    protected $table = 'tblRolModulo';
    protected $primaryKey = 'id_RolModulo';
    public $timestamps = false;
    protected $fillable = ['id_Rol', 'id_Modulos', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function rol() { return $this->belongsTo(Rol::class, 'id_Rol'); }
    public function modulo() { return $this->belongsTo(Modulo::class, 'id_Modulos'); }
}
