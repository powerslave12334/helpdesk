<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipoCampoConfig extends Model
{
    protected $table = 'tblTipoEquipoCampoConfig';

    protected $primaryKey = 'id_TipoEquipoCampoConfig';

    public $timestamps = false;

    protected $fillable = [
        'id_TipoEquipo',
        'campo',
        'es_requerido',
        'orden',
        'activo',
        'fechaAlta',
    ];

    protected $casts = [
        'es_requerido' => 'boolean',
        'activo' => 'boolean',
        'orden' => 'integer',
        'fechaAlta' => 'datetime',
    ];

    public function tipoEquipo()
    {
        return $this->belongsTo(TipoEquipo::class, 'id_TipoEquipo');
    }
}
