<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tblTicket';

    protected $primaryKey = 'id_Ticket';

    public $timestamps = false;

    protected $fillable = [
        'id_Usuario', 'id_TipoSoporte', 'id_Tecnico', 'asunto', 'problema',
        'img', 'fechaAlta', 'activo', 'id_Estatus', 'id_Equipo', 'fechaCierre',
        'tipoTicket', 'vehiculos', 'problemaGps',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'fechaAlta' => 'datetime',
        'fechaCierre' => 'datetime',
    ];

    public const TIPOS_TICKET = [
        'falla_tecnica' => 'Falla técnica',
        'instalacion_gps' => 'Instalación de GPS',
        'revision_gps' => 'Revisión de GPS',
        'desarrollo' => 'Desarrollo',
    ];

    /** Catálogo de problemas GPS para tickets de revisión ('__otro' = texto libre). */
    public const PROBLEMAS_GPS = [
        'Sin señal / no reporta',
        'No enciende',
        'Se apaga solo',
        'Ubicación incorrecta',
        'Batería no carga',
        'Antena o instalación dañada',
        'SIM sin datos',
        '__otro',
    ];

    public static function tiposTicket(): array
    {
        return self::TIPOS_TICKET;
    }

    public function etiquetaTipoTicket(): string
    {
        return self::TIPOS_TICKET[$this->tipoTicket] ?? 'Falla técnica';
    }

    /**
     * Unidades del catálogo tblVehiculo vinculadas al ticket
     * (ids guardados en la columna vehiculos, separados por coma).
     */
    public function vehiculosDelTicket()
    {
        $ids = array_filter(array_map('intval', explode(',', (string) $this->vehiculos)));
        if (empty($ids)) {
            return collect();
        }

        return Vehiculo::whereIn('id_Vehiculo', $ids)->get()
            ->sortBy(fn ($v) => array_search($v->id_Vehiculo, $ids))
            ->values();
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_Usuario');
    }

    public function tecnico()
    {
        return $this->belongsTo(Usuario::class, 'id_Tecnico');
    }

    public function estatus()
    {
        return $this->belongsTo(Estatus::class, 'id_Estatus');
    }

    public function tipoSoporte()
    {
        return $this->belongsTo(TipoSoporte::class, 'id_TipoSoporte');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_Equipo');
    }

    public function comentarios()
    {
        return $this->hasMany(TicketComentario::class, 'id_Ticket');
    }

    public function evidencias()
    {
        return $this->hasMany(TicketEvidencia::class, 'id_Ticket');
    }

    public function desarrolloDetalle()
    {
        return $this->hasOne(TicketDesarrolloDetalle::class, 'id_Ticket');
    }
}
