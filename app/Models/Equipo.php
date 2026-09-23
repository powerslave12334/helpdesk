<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    protected $table = 'tblEquipo';
    protected $primaryKey = 'id_Equipo';
    public $timestamps = false;
    protected $fillable = [
        'marca', 'modelo', 'id_TipoEquipo', 'id_Estatus', 'fechaAdquisicion',
        'numeroSerie', 'nombreEquipo', 'ubicacion', 'observaciones',
        'id_PeriodoMantenimiento', 'fechaUltimoMantenimiento', 'fechaAlta', 'activo', 'id_Oficinas', 'esCompartido',
    ];
    protected $casts = ['activo' => 'boolean', 'esCompartido' => 'boolean', 'fechaAdquisicion' => 'datetime', 'fechaUltimoMantenimiento' => 'datetime', 'fechaAlta' => 'datetime'];

    public function tipoEquipo() { return $this->belongsTo(TipoEquipo::class, 'id_TipoEquipo'); }
    public function estatus() { return $this->belongsTo(Estatus::class, 'id_Estatus'); }
    public function oficina() { return $this->belongsTo(Oficina::class, 'id_Oficinas'); }
    public function usuarios() { return $this->belongsToMany(Usuario::class, 'tblEquipoUsuario', 'id_Equipo', 'id_Usuario'); }
    public function asignaciones() { return $this->hasMany(EquipoUsuario::class, 'id_Equipo'); }
    public function historial() { return $this->hasMany(HistorialEquipoUsuario::class, 'id_Equipo'); }
    public function equipoDetalle() { return $this->hasMany(EquipoCampo::class, 'id_Equipo'); }
    public function periodoMantenimiento() { return $this->belongsTo(PeriodoMantenimiento::class, 'id_PeriodoMantenimiento'); }
    public function mantenimientos() { return $this->hasMany(Mantenimiento::class, 'id_Equipo'); }
}
