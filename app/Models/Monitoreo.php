<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Monitoreo extends Model
{
    protected $table = 'tblMonitoreo';

    protected $primaryKey = 'id_Monitoreo';

    public $timestamps = false;

    protected $fillable = [
        'nombre', 'tipo', 'host_o_url', 'puerto', 'intervalo_minutos',
        'estatus', 'tiempo_respuesta_ms', 'ultimo_check', 'mensaje_ultimo_check',
        'activo', 'fechaAlta',
        'nagios_host_name', 'nagios_service_description', 'ultimo_sincronizado_nagios',
        'cpu_porcentaje', 'memoria_porcentaje', 'memoria_usada_gb', 'memoria_total_gb',
        'memoria_libre_gb', 'disco_porcentaje', 'disco_usado_gb', 'disco_total_gb',
        'disco_libre_gb',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'puerto' => 'integer',
        'intervalo_minutos' => 'integer',
        'tiempo_respuesta_ms' => 'integer',
        'ultimo_check' => 'datetime',
        'fechaAlta' => 'datetime',
        'ultimo_sincronizado_nagios' => 'datetime',
        'cpu_porcentaje' => 'decimal:2',
        'memoria_porcentaje' => 'decimal:2',
        'memoria_usada_gb' => 'decimal:2',
        'memoria_total_gb' => 'decimal:2',
        'memoria_libre_gb' => 'decimal:2',
        'disco_porcentaje' => 'decimal:2',
        'disco_usado_gb' => 'decimal:2',
        'disco_total_gb' => 'decimal:2',
        'disco_libre_gb' => 'decimal:2',
    ];

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorTipo($query, string $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    public function esOnline(): bool
    {
        return $this->estatus === 'online';
    }

    public function esOffline(): bool
    {
        return $this->estatus === 'offline';
    }

    public function badgeClass(): string
    {
        return match ($this->estatus) {
            'online' => 'bg-success',
            'offline' => 'bg-danger',
            'degradado' => 'bg-warning',
            default => 'bg-secondary',
        };
    }

    public function etiquetaEstatus(): string
    {
        return match ($this->estatus) {
            'online' => 'Online',
            'offline' => 'Fuera de línea',
            'degradado' => 'Degradado',
            default => 'Desconocido',
        };
    }
}
