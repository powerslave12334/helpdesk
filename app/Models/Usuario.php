<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usuario extends Model
{
    protected $table = 'tblUsuario';
    protected $primaryKey = 'id_Usuario';
    public $timestamps = false;
    protected $fillable = [
        'nombre', 'app', 'apm', 'correo', 'telefono',
        'id_Depto', 'id_Area', 'esTecnico', 'id_TipoEmpleado',
        'fechaAlta', 'activo', 'id_Oficinas',
    ];
    protected $casts = [
        'esTecnico' => 'boolean',
        'activo' => 'boolean',
        'fechaAlta' => 'datetime',
    ];

    public function login()
    {
        return $this->hasOne(Login::class, 'id_Usuario');
    }

    public function roles()
    {
        return $this->belongsToMany(Rol::class, 'tblRolesUsuarios', 'id_Usuario', 'id_Rol');
    }

    public function departamento()
    {
        return $this->belongsTo(Departamento::class, 'id_Depto');
    }

    public function area()
    {
        return $this->belongsTo(Area::class, 'id_Area');
    }

    public function tipoEmpleado()
    {
        return $this->belongsTo(TipoEmpleado::class, 'id_TipoEmpleado');
    }

    public function oficina()
    {
        return $this->belongsTo(Oficina::class, 'id_Oficinas');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'id_Usuario');
    }

    public function equipos()
    {
        return $this->belongsToMany(Equipo::class, 'tblEquipoUsuario', 'id_Usuario', 'id_Equipo');
    }

    public function nombreCompleto()
    {
        return trim($this->nombre . ' ' . $this->app . ' ' . $this->apm);
    }
}
