<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstalacionGPS extends Model
{
    protected $table = 'tblInstalacionesGPS';
    protected $primaryKey = 'id_InstalacionGPS';
    public $timestamps = false;
    protected $fillable = [
        'unidad',
        'marca',
        'placas',
        'placas_federal',
        'fecha_cambio_sim',
        'sim_cambiado',
        'reinstalacion',
        'fecha_reinstalacion',
        'marca_gps',
        'modelo_gps',
        'imei',
        'numero_anterior',
        'numero_actual',
        'fechaAlta',
        'activo',
    ];
    protected $casts = [
        'sim_cambiado' => 'boolean',
        'reinstalacion' => 'boolean',
        'fechaAlta' => 'datetime',
        'fecha_cambio_sim' => 'date',
        'fecha_reinstalacion' => 'date',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_Ticket');
    }
}