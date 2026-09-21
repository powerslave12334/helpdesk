<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoCampo extends Model
{
    protected $table = 'tblEquipoDetalle';
    protected $primaryKey = 'id_EquipoDetalle';
    public $timestamps = false;
    protected $fillable = ['id_Equipo', 'id_EquipoCampo', 'valor', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function equipo() { return $this->belongsTo(Equipo::class, 'id_Equipo'); }
}
