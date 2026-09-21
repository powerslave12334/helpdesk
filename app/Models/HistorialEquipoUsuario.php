<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEquipoUsuario extends Model
{
    protected $table = 'tblHistorialEquipoUsuario';
    protected $primaryKey = 'id_Historial';
    public $timestamps = false;
    protected $fillable = ['id_Equipo', 'id_Usuario', 'id_UsuarioRealizo', 'accion', 'observaciones', 'fechaAccion'];
    protected $casts = ['fechaAccion' => 'datetime'];

    public function equipo() { return $this->belongsTo(Equipo::class, 'id_Equipo'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
}
