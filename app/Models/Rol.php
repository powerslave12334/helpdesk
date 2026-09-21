<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'tblRoles';
    protected $primaryKey = 'id_Rol';
    public $timestamps = false;
    protected $fillable = ['nombre', 'descipcion', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function usuarios() { return $this->belongsToMany(Usuario::class, 'tblRolesUsuarios', 'id_Rol', 'id_Usuario'); }
    public function permisos() { return $this->hasMany(RolPermiso::class, 'id_Rol'); }
}
