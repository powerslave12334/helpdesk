<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoRelacion extends Model
{
    protected $table = 'tblEquipoRelacion';
    protected $primaryKey = 'id_EquipoRelacion';
    public $timestamps = false;
    protected $fillable = [
        'id_EquipoPadre', 'id_EquipoHijo', 'id_TipoRelacionEquipo',
        'fechaAsignacion', 'fechaBaja', 'observaciones', 'fechaAlta', 'activo',
    ];
    protected $casts = [
        'activo' => 'boolean',
        'fechaAsignacion' => 'date',
        'fechaBaja' => 'date',
        'fechaAlta' => 'datetime',
    ];

    public function equipoPadre() { return $this->belongsTo(Equipo::class, 'id_EquipoPadre'); }
    public function equipoHijo() { return $this->belongsTo(Equipo::class, 'id_EquipoHijo'); }
    public function tipoRelacion() { return $this->belongsTo(TipoRelacionEquipo::class, 'id_TipoRelacionEquipo'); }
}
