<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolesUsuario extends Model
{
    protected $table = 'tblRolesUsuarios';
    protected $primaryKey = 'id_RolesUsuario';
    public $timestamps = false;
    protected $fillable = ['id_Rol', 'id_Usuario', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function rol() { return $this->belongsTo(Rol::class, 'id_Rol'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
}
