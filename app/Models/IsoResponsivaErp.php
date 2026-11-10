<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Documento FO-IT-01 (Responsiva ERP) generado en el módulo ISO 9001.
 *
 * Cada formato ISO 9001 tendrá su propia tabla (patrón elegido para este
 * módulo); los campos son los datos que se imprimen en el PDF, que se
 * regenera bajo demanda desde estos datos.
 */
class IsoResponsivaErp extends Model
{
    use HasFactory;

    protected $table = 'tblIsoResponsivaErp';

    protected $primaryKey = 'id_ResponsivaErp';

    public $timestamps = false;

    protected $fillable = [
        'fecha', 'nombreEmpleado', 'puesto', 'correo',
        'usuarioErp', 'contrasenaErp', 'id_Usuario',
        'fechaCreacion', 'activo',
        'firmaEmpleado', 'fechaFirmaEmpleado',
        'firmaTi', 'fechaFirmaTi', 'huella',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fechaCreacion' => 'datetime',
        // Cifrada: se necesita para regenerar el PDF, pero no debe vivir en
        // claro en la BD (ni mostrarse en el listado).
        'contrasenaErp' => 'encrypted',
        'fechaFirmaEmpleado' => 'datetime',
        'fechaFirmaTi' => 'datetime',
        'activo' => 'boolean',
    ];

    /**
     * La huella SHA-256 se recalcula tras cada guardado para que siempre
     * describa el estado actual del documento (alta, firma o re-firma).
     * updateQuietly evita el bucle de eventos.
     */
    protected static function booted(): void
    {
        static::saved(function (self $documento): void {
            $huella = $documento->calcularHuella();

            if ($documento->huella !== $huella) {
                $documento->updateQuietly(['huella' => $huella]);
            }
        });
    }

    /** Usuario que creó el documento. */
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_Usuario');
    }

    /** Folio de presentación: FO-IT-01-0001. */
    public function getFolioAttribute(): string
    {
        return 'FO-IT-01-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    /** Tiene al menos una firma capturada. */
    public function estaFirmado(): bool
    {
        return $this->firmaEmpleado !== null || $this->firmaTi !== null;
    }

    /**
     * Huella SHA-256 (hex) de la información del documento + firmas.
     *
     * Se hashean los valores normalizados (no los bytes del PDF: dompdf
     * incluye la fecha de creación en los metadatos y el hash del archivo
     * no sería estable entre regeneraciones). La contraseña se usa
     * descifrada porque el cifrado usa IV distinto en cada escritura.
     *
     * @return string Cadena de 64 caracteres hexadecimales.
     */
    public function calcularHuella(): string
    {
        $campos = [
            $this->folio,
            $this->fecha?->format('Y-m-d') ?? '',
            (string) $this->nombreEmpleado,
            (string) $this->puesto,
            (string) $this->correo,
            (string) $this->usuarioErp,
            (string) $this->contrasenaErp,
            (string) $this->firmaEmpleado,
            $this->fechaFirmaEmpleado?->format('Y-m-d H:i:s') ?? '',
            (string) $this->firmaTi,
            $this->fechaFirmaTi?->format('Y-m-d H:i:s') ?? '',
            (string) $this->id_Usuario,
            $this->fechaCreacion?->format('Y-m-d H:i:s') ?? '',
        ];

        return hash('sha256', implode('|', $campos));
    }
}
