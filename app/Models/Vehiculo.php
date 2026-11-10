<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'tblVehiculo';

    protected $primaryKey = 'id_Vehiculo';

    public $timestamps = false;

    protected $fillable = [
        'estado',
        'numeroUnidad',
        'marca',
        'modelo',
        'placa_Estatal',
        'placa_Federal',
        'placa_Puesta',
        'activo',
    ];
}
