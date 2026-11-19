<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Documento FO-IT-04 (Inventario de herramienta) generado en el módulo ISO 9001.
 *
 * Inventario simple: sin firmas ni huella SHA-256. Cada herramienta tiene su
 * propio registro y se puede filtrar y reportar desde el listado.
 */
class IsoInventarioHerramienta extends Model
{
    use HasFactory;

    protected $table = 'tblIsoInventarioHerramienta';

    protected $primaryKey = 'id_InventarioHerramienta';

    public $timestamps = false;

    protected $fillable = [
        'codigo', 'nombre', 'marca', 'modelo',
        'numeroSerie', 'numeroInventario', 'ubicacion', 'estado',
        'fechaAdquisicion', 'id_Usuario', 'fechaCreacion', 'activo',
    ];

    protected $casts = [
        'fechaAdquisicion' => 'date',
        'fechaCreacion' => 'datetime',
        'activo' => 'boolean',
    ];

    /** Estados válidos del catálogo. */
    public const ESTADOS = [
        'bueno' => 'Bueno',
        'regular' => 'Regular',
        'malo' => 'Malo',
        'baja' => 'Baja',
    ];

    /** Usuario que creó el registro. */
    public function creador()
    {
        return $this->belongsTo(User::class, 'id_Usuario');
    }

    /** Folio de presentación: FO-IT-04-0001. */
    public function getFolioAttribute(): string
    {
        return 'FO-IT-04-'.str_pad((string) $this->getKey(), 4, '0', STR_PAD_LEFT);
    }

    /** Estado legible. */
    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
