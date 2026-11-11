<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Documento FO-IT-02 (Acta de entrega) generado en el módulo ISO 9001.
 *
 * Cada formato ISO 9001 tendrá su propia tabla (patrón elegido para este
 * módulo); los campos son los datos que se imprimen en el PDF, que se
 * regenera bajo demanda desde estos datos. Los renglones de equipos del
 * acta (tabla dinámica) viven en tblIsoActaEntregaEquipo.
 */
class IsoActaEntrega extends Model
{
    use HasFactory;

    /**
     * Catálogo de la columna "Condiciones" del formato oficial. Fuente única
     * para la validación, el dropdown del formulario y el PDF.
     */
    public const CONDICIONES = [
        'Nuevo',
        'Usado en buen estado',
        'Usado en estado regular',
        'Con daños',
        'No funcional',
    ];

    protected $table = 'tblIsoActaEntrega';

    protected $primaryKey = 'id_ActaEntrega';

    public $timestamps = false;

    protected $fillable = [
        'fecha', 'entregadoPor', 'recibidoPor', 'id_Usuario',
        'fechaCreacion', 'activo',
        'firmaEntrega', 'fechaFirmaEntrega',
        'firmaRecepcion', 'fechaFirmaRecepcion', 'huella',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fechaCreacion' => 'datetime',
        'fechaFirmaEntrega' => 'datetime',
        'fechaFirmaRecepcion' => 'datetime',
        'activo' => 'boolean',
    ];

    /**
     * La huella SHA-256 se recalcula tras cada guardado para que siempre
     * describa el estado actual del documento (alta, firma o re-firma).
     * updateQuietly evita el bucle de eventos.
     */
    protected static function booted(): void
    {
        static::saved(function (self $acta): void {
            $acta->actualizarHuella();
        });
    }

    /** Usuario que creó el documento. */
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_Usuario');
    }

    /** Renglones de equipos del acta, en orden de captura. */
    public function equipos()
    {
        return $this->hasMany(IsoActaEntregaEquipo::class, 'id_ActaEntrega')
            ->orderBy('id_ActaEquipo');
    }

    /** Folio de presentación: FO-IT-02-0001. */
    public function getFolioAttribute(): string
    {
        return 'FO-IT-02-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    /** Tiene al menos una firma capturada. */
    public function estaFirmado(): bool
    {
        return $this->firmaEntrega !== null || $this->firmaRecepcion !== null;
    }

    /**
     * Recalcula la huella y la persiste sin disparar eventos.
     *
     * Lo llaman el hook "saved" del propio acta y el hook "saved" de cada
     * renglón de equipos (IsoActaEntregaEquipo), de modo que la huella
     * describa siempre el documento completo.
     */
    public function actualizarHuella(): void
    {
        $huella = $this->calcularHuella();

        if ($this->huella === $huella) {
            return;
        }

        // Tras insertGetId la columna identidad (y lo recién persistido)
        // sigue "dirty": sin sincronizar, el UPDATE la incluiría en el
        // SET y SQL Server rechaza escribir columnas identidad (MySQL lo
        // tolera). Se sincroniza el estado ya guardado y solo se escribe
        // la huella.
        $this->syncOriginal();
        $this->updateQuietly(['huella' => $huella]);
    }

    /**
     * Huella SHA-256 (hex) de la información del documento + renglones +
     * firmas.
     *
     * Se hashean los valores normalizados (no los bytes del PDF: dompdf
     * incluye la fecha de creación en los metadatos y el hash del archivo
     * no sería estable entre regeneraciones). Los renglones se ordenan por
     * su id para que el orden de inserción no altere la huella.
     *
     * @return string Cadena de 64 caracteres hexadecimales.
     */
    public function calcularHuella(): string
    {
        $renglones = $this->equipos()
            ->get()
            ->map(fn (IsoActaEntregaEquipo $equipo): string => implode("\t", [
                (string) $equipo->cantidad,
                (string) $equipo->descripcion,
                (string) $equipo->marca,
                (string) $equipo->modelo,
                (string) $equipo->noSerie,
                (string) $equipo->condiciones,
            ]))
            ->implode("\n");

        $campos = [
            $this->folio,
            $this->fecha?->format('Y-m-d') ?? '',
            (string) $this->entregadoPor,
            (string) $this->recibidoPor,
            $renglones,
            (string) $this->firmaEntrega,
            $this->fechaFirmaEntrega?->format('Y-m-d H:i:s') ?? '',
            (string) $this->firmaRecepcion,
            $this->fechaFirmaRecepcion?->format('Y-m-d H:i:s') ?? '',
            (string) $this->id_Usuario,
            $this->fechaCreacion?->format('Y-m-d H:i:s') ?? '',
        ];

        return hash('sha256', implode('|', $campos));
    }
}
