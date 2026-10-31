<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEquipoUsuario extends Model
{
    protected $table = 'tblHistorialEquipoUsuario';
    protected $primaryKey = 'id_HistorialEquipoUsuario';
    public $timestamps = false;
    protected $fillable = [
        'id_EquipoUsuarioOriginal', 'id_Equipo', 'id_Usuario',
        'fechaAsignacion', 'fechaBaja', 'observaciones', 'id_Area',
        'fechaAlta', 'fechaAccion', 'id_UsuarioAccion', 'accion',
        'motivo', 'equipoMarca', 'equipoModelo', 'equipoNombre',
        'equipoNumeroSerie', 'equipoUbicacion',
    ];
    protected $casts = [
        'fechaAsignacion' => 'datetime',
        'fechaBaja' => 'datetime',
        'fechaAlta' => 'datetime',
        'fechaAccion' => 'datetime',
    ];

    public function equipo() { return $this->belongsTo(Equipo::class, 'id_Equipo'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
    public function usuarioAccion() { return $this->belongsTo(Usuario::class, 'id_UsuarioAccion'); }
    public function area() { return $this->belongsTo(Area::class, 'id_Area'); }
}
