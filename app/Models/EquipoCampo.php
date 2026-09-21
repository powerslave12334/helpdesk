<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoCampo extends Model
{
    protected $table = 'tblEquipoDetalle';
    protected $primaryKey = 'id_EquipoCampo';
    public $timestamps = false;
    protected $fillable = ['id_Equipo', 'id_TipoEquipoCampo', 'valor'];
}
