<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

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

    public function getAuthIdentifierName()
    {
        return 'id_Usuario';
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthPassword()
    {
        return $this->login ? $this->login->pass : '';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
    }

    public function getRememberTokenName()
    {
        return '';
    }

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

    public function nombreCompleto()
    {
        return trim($this->nombre . ' ' . $this->app . ' ' . $this->apm);
    }

    public function esAdministrador()
    {
        return $this->roles()->where(function ($q) {
            $q->whereRaw("LOWER(tblRoles.nombre) LIKE '%admin%'")
              ->orWhereRaw("LOWER(tblRoles.nombre) = 'root'");
        })->where('tblRoles.activo', true)->exists();
    }
}
