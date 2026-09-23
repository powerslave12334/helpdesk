<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'tblMantenimiento';
    protected $primaryKey = 'id_Mantenimiento';
    public $timestamps = false;

    protected $fillable = [
        'id_Equipo',
        'id_UsuarioTecnico',
        'fechaProgramada',
        'fechaEjecucion',
        'id_Estatus',
        'tipoMantenimiento',
        'observaciones',
        'id_Ticket',
        'fechaAlta',
        'activo',
        'estadoInicial',
        'hw_encendido', 'hw_pantalla', 'hw_ventilacion', 'hw_dispositivos',
        'sw_so', 'sw_seguridad', 'sw_antivirus', 'sw_rendimiento',
        'act_limpieza', 'act_revision_hw', 'act_actualizacion_so', 'act_instalacion_sw',
        'act_parches', 'act_eliminacion', 'act_diagnostico', 'act_otro', 'act_otro_detalle',
        'conclusion',
        'reporteCerrado',
    ];

    protected $casts = [
        'fechaProgramada' => 'datetime',
        'fechaEjecucion' => 'datetime',
        'fechaAlta' => 'datetime',
        'activo' => 'boolean',
        'reporteCerrado' => 'boolean',
        'act_limpieza' => 'boolean',
        'act_revision_hw' => 'boolean',
        'act_actualizacion_so' => 'boolean',
        'act_instalacion_sw' => 'boolean',
        'act_parches' => 'boolean',
        'act_eliminacion' => 'boolean',
        'act_diagnostico' => 'boolean',
        'act_otro' => 'boolean',
    ];

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_Equipo', 'id_Equipo');
    }

    public function tecnico()
    {
        return $this->belongsTo(Usuario::class, 'id_UsuarioTecnico', 'id_Usuario');
    }

    public function estatus()
    {
        return $this->belongsTo(Estatus::class, 'id_Estatus', 'id_Estatus');
    }

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_Ticket', 'id_Ticket');
    }

    public function scopePendientes($query)
    {
        return $query->where('activo', true)
                     ->where('fechaEjecucion', null);
    }

    public function scopeVencidos($query)
    {
        return $query->where('activo', true)
                     ->where('fechaEjecucion', null)
                     ->where('fechaProgramada', '<', now());
    }

    public function scopeProximos($query, $dias = 7)
    {
        return $query->where('activo', true)
                     ->where('fechaEjecucion', null)
                     ->where('fechaProgramada', '>=', now())
                     ->where('fechaProgramada', '<=', now()->addDays($dias));
    }
}
