<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoUsuario extends Model
{
    protected $table = 'tblEquipoUsuario';
    protected $primaryKey = 'id_EquipoUsuario';
    public $timestamps = false;
    protected $fillable = ['id_Equipo', 'id_Usuario', 'fechaAsignacion', 'fechaBaja', 'observaciones', 'id_Area', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAsignacion' => 'datetime', 'fechaBaja' => 'datetime', 'fechaAlta' => 'datetime'];

    public function equipo() { return $this->belongsTo(Equipo::class, 'id_Equipo'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
    public function area() { return $this->belongsTo(Area::class, 'id_Area'); }
}
