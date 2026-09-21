<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipoCampo extends Model
{
    protected $table = 'tblTipoEquipoCampo';
    protected $primaryKey = 'id_TipoEquipoCampo';
    public $timestamps = false;
    protected $fillable = ['id_TipoEquipo', 'id_EquipoCampo', 'requerido', 'fechaAlta', 'activo'];
    protected $casts = ['requerido' => 'boolean', 'activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function tipoEquipo() { return $this->belongsTo(TipoEquipo::class, 'id_TipoEquipo'); }
}
