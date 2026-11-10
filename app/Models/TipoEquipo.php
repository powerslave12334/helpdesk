<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoEquipo extends Model
{
    protected $table = 'tblTipoEquipo';

    protected $primaryKey = 'id_TipoEquipo';

    public $timestamps = false;

    protected $fillable = ['descripcion', 'fechaAlta', 'activo'];

    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function equipos()
    {
        return $this->hasMany(Equipo::class, 'id_TipoEquipo');
    }

    public function camposConfig()
    {
        return $this->hasMany(TipoEquipoCampoConfig::class, 'id_TipoEquipo');
    }

    public function inventoryFields()
    {
        return $this->belongsToMany(InventoryField::class, 'inventory_type_field', 'id_TipoEquipo', 'inventory_field_id')
            ->withPivot('order', 'is_required')
            ->withTimestamps();
    }
}
