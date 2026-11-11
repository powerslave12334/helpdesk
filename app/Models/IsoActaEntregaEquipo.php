<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Renglón de equipos de un acta de entrega FO-IT-02: una fila de la tabla
 * dinámica del formulario (cantidad, descripción, marca, modelo,
 * no. de serie y condiciones).
 */
class IsoActaEntregaEquipo extends Model
{
    use HasFactory;

    protected $table = 'tblIsoActaEntregaEquipo';

    protected $primaryKey = 'id_ActaEquipo';

    public $timestamps = false;

    protected $fillable = [
        'id_ActaEntrega', 'cantidad', 'descripcion',
        'marca', 'modelo', 'noSerie', 'condiciones',
    ];

    protected $casts = [
        'cantidad' => 'integer',
    ];

    /**
     * Al guardar un renglón se recalcula la huella del acta al que pertenece,
     * para que la huella describa siempre el documento completo (patrón de
     * IsoResponsivaErp). Se usa la relación acta() para que la consulta corra
     * en la misma conexión que el renglón. updateQuietly evita el bucle de
     * eventos.
     */
    protected static function booted(): void
    {
        static::saved(function (self $equipo): void {
            $equipo->acta()->first()?->actualizarHuella();
        });
    }

    /** Acta a la que pertenece el renglón. */
    public function acta()
    {
        return $this->belongsTo(IsoActaEntrega::class, 'id_ActaEntrega');
    }
}
