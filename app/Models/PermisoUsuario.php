<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoUsuario extends Model
{
    protected $table = 'tblPermisoUsuarios';
    protected $primaryKey = 'id_PermisoUsuarios';
    public $timestamps = false;
    protected $fillable = ['id_Usuario', 'id_Modulos', 'id_SubModulos', 'id_Accion', 'permitido', 'fechaAlta', 'activo'];
    protected $casts = ['permitido' => 'boolean', 'activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
    public function modulo() { return $this->belongsTo(Modulo::class, 'id_Modulos'); }
    public function subModulo() { return $this->belongsTo(SubModulo::class, 'id_SubModulos'); }
    public function accion() { return $this->belongsTo(Accion::class, 'id_Accion'); }
}
