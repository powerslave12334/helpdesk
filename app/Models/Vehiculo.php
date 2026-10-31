<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    protected $table = 'tblVehiculo';
    protected $primaryKey = 'id_Vehiculo';
    public $timestamps = false;
    protected $fillable = ['numeroUnidad', 'tipoVehiculo', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public const TIPOS = ['Tractocamión', 'Camión'];

    public static function tipos(): array
    {
        return self::TIPOS;
    }

    public function etiqueta(): string
    {
        return $this->numeroUnidad . ' (' . $this->tipoVehiculo . ')';
    }
}
