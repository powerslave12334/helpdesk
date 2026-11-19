<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Documento FO-IT-03 (Recepción de equipo) generado en el módulo ISO 9001.
 *
 * Cada formato ISO 9001 tendrá su propia tabla (patrón elegido para este
 * módulo); los campos son los datos que se imprimen en el PDF, que se
 * regenera bajo demanda desde estos datos. Los grupos de checkboxes del
 * formato se guardan como texto: selección única directa y selección
 * múltiple como lista separada por comas.
 */
class IsoRecepcionEquipo extends Model
{
    use HasFactory;

    /** Catálogo "Motivo de la Entrega" (selección única). */
    public const MOTIVOS = [
        'Baja de Personal',
        'Cambio de Activo',
        'Mantenimiento de Activo',
    ];

    /** Catálogo "Tipo de activo entregado" (selección múltiple). */
    public const TIPOS_ACTIVO = [
        'Computadora',
        'Laptop',
        'USB',
        'Telefono Celular',
        'Kit inalambrico Mouse',
        'Kit inalambrico Teclado',
    ];

    /** Catálogo "Accesorios del Activo" (selección múltiple).
     *  Orden del formato oficial: primero la columna izquierda del PDF
     *  (4 opciones) y luego la derecha (3 opciones). */
    public const ACCESORIOS = [
        'Sin Accesorios',
        'Mouse Inalámbrico',
        'USB Bluetooth',
        'Teclado Inalámbrico',
        'Cargador',
        'Adaptador de Corriente',
        'Monitor',
    ];

    /** Catálogo "Aplicacion de Gastos de Reparación" (selección única). */
    public const APLICACION_GASTOS = [
        'Con cargo a Usuario',
        'Con cargo a Cliente',
        'Con cargo a Alcomex',
    ];

    /** Valor por defecto de "Descripcion de la Entrega" (xlsx oficial). */
    public const DESCRIPCION_DEFECTO = 'Se recibe equipo en buenas condiciones';

    protected $table = 'tblIsoRecepcionEquipo';

    protected $primaryKey = 'id_RecepcionEquipo';

    public $timestamps = false;

    protected $fillable = [
        'id_Equipo',
        'fecha', 'motivoEntrega', 'tipoActivo',
        'marca', 'modelo', 'noSerie', 'imei', 'numeroTelefonico',
        'accesorios', 'descripcionEntrega',
        'aplicacionGastos', 'gastosReparacion',
        'revisadoPor', 'entregadoPor',
        'id_Usuario', 'fechaCreacion', 'activo',
        'firmaRevision', 'fechaFirmaRevision',
        'firmaEntrega', 'fechaFirmaEntrega', 'huella',
    ];

    protected $casts = [
        'fecha' => 'date',
        'fechaCreacion' => 'datetime',
        'fechaFirmaRevision' => 'datetime',
        'fechaFirmaEntrega' => 'datetime',
        'gastosReparacion' => 'decimal:2',
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
            $documento->actualizarHuella();
        });
    }

    /** Usuario que creó el documento. */
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_Usuario');
    }

    /** Equipo asignado del que se copian los datos de identificación. */
    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_Equipo');
    }

    /**
     * Datos de identificación del activo para el PDF.
     *
     * Si el documento tiene un equipo asignado, se toman los datos de
     * tblEquipo (marca, modelo, numeroSerie, imei, lineaSim). Si no, se
     * usan los campos de texto libre del propio documento.
     *
     * @return array{marca: string, modelo: string, noSerie: string, imei: string|null, numeroTelefonico: string|null}
     */
    public function datosEquipo(): array
    {
        $equipo = $this->equipo;

        if ($equipo !== null) {
            return [
                'marca' => (string) $equipo->marca,
                'modelo' => (string) $equipo->modelo,
                'noSerie' => (string) $equipo->numeroSerie,
                'imei' => $equipo->imei ?? null,
                'numeroTelefonico' => $equipo->lineaSim ?? null,
            ];
        }

        return [
            'marca' => (string) $this->marca,
            'modelo' => (string) $this->modelo,
            'noSerie' => (string) $this->noSerie,
            'imei' => $this->imei,
            'numeroTelefonico' => $this->numeroTelefonico,
        ];
    }

    /** Folio de presentación: FO-IT-03-0001. */
    public function getFolioAttribute(): string
    {
        return 'FO-IT-03-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    /** Tiene al menos una firma capturada. */
    public function estaFirmado(): bool
    {
        return $this->firmaRevision !== null || $this->firmaEntrega !== null;
    }

    /** Opciones marcadas de "Tipo de activo entregado". */
    public function tiposActivos(): array
    {
        return $this->descomponer($this->tipoActivo);
    }

    /** Opciones marcadas de "Accesorios del Activo". */
    public function accesoriosMarcados(): array
    {
        return $this->descomponer($this->accesorios);
    }

    /**
     * Opciones de tipo de activo repartidas en las 2 columnas del formato
     * oficial (3 + 3), para el PDF y el formulario.
     *
     * @return array<int, array<int, string>>
     */
    public static function tiposEnColumnas(): array
    {
        return array_chunk(self::TIPOS_ACTIVO, 3);
    }

    /**
     * Opciones de accesorio repartidas en las 2 columnas del formato
     * oficial (4 + 3), para el PDF y el formulario.
     *
     * @return array<int, array<int, string>>
     */
    public static function accesoriosEnColumnas(): array
    {
        return [array_slice(self::ACCESORIOS, 0, 4), array_slice(self::ACCESORIOS, 4)];
    }

    /**
     * Recalcula la huella y la persiste sin disparar eventos.
     *
     * Lo llaman el hook "saved" del propio modelo tras cada alta o firma.
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
     * Huella SHA-256 (hex) de la información del documento + firmas.
     *
     * Se hashean los valores normalizados (no los bytes del PDF: dompdf
     * incluye la fecha de creación en los metadatos y el hash del archivo
     * no sería estable entre regeneraciones).
     *
     * @return string Cadena de 64 caracteres hexadecimales.
     */
    public function calcularHuella(): string
    {
        $campos = [
            $this->folio,
            (string) $this->id_Equipo,
            $this->fecha?->format('Y-m-d') ?? '',
            (string) $this->motivoEntrega,
            (string) $this->tipoActivo,
            (string) $this->marca,
            (string) $this->modelo,
            (string) $this->noSerie,
            (string) $this->imei,
            (string) $this->numeroTelefonico,
            (string) $this->accesorios,
            (string) $this->descripcionEntrega,
            (string) $this->aplicacionGastos,
            (string) $this->gastosReparacion,
            (string) $this->revisadoPor,
            (string) $this->entregadoPor,
            (string) $this->firmaRevision,
            $this->fechaFirmaRevision?->format('Y-m-d H:i:s') ?? '',
            (string) $this->firmaEntrega,
            $this->fechaFirmaEntrega?->format('Y-m-d H:i:s') ?? '',
            (string) $this->id_Usuario,
            $this->fechaCreacion?->format('Y-m-d H:i:s') ?? '',
        ];

        return hash('sha256', implode('|', $campos));
    }

    /** Divide una lista separada por comas en sus opciones. */
    private function descomponer(?string $lista): array
    {
        if ($lista === null || trim($lista) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $lista))));
    }
}
