<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolPermiso extends Model
{
    protected $table = 'tblRolPermiso';
    protected $primaryKey = 'id_RolPermiso';
    public $timestamps = false;
    protected $fillable = ['id_Rol', 'id_Modulos', 'id_SubModulos', 'id_Accion', 'permitido', 'fechaAlta', 'activo'];
    protected $casts = ['permitido' => 'boolean', 'activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function rol() { return $this->belongsTo(Rol::class, 'id_Rol'); }
    public function modulo() { return $this->belongsTo(Modulo::class, 'id_Modulos'); }
    public function subModulo() { return $this->belongsTo(SubModulo::class, 'id_SubModulos'); }
    public function accion() { return $this->belongsTo(Accion::class, 'id_Accion'); }
}
