<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEvidencia extends Model
{
    protected $table = 'tblTicketEvidencia';

    protected $primaryKey = 'id_TicketEvidencia';

    public $timestamps = false;

    protected $fillable = ['id_Ticket', 'nombreArchivo', 'rutaArchivo', 'driveFileId', 'extension', 'tipoContenido', 'tamanioBytes', 'id_UsuarioAlta', 'fechaAlta', 'activo'];

    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime', 'tamanioBytes' => 'integer'];

    /** Enlaces que la galería necesita además de las columnas. */
    protected $appends = ['url', 'urlImagen'];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class, 'id_Ticket');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_UsuarioAlta');
    }

    /**
     * Enlace para abrir la evidencia: en Drive si tiene fileId, o la ruta
     * local (con / inicial) para los registros anteriores a la migración.
     */
    public function getUrlAttribute($valor = null): ?string
    {
        if ($this->driveFileId) {
            return 'https://drive.google.com/file/d/'.$this->driveFileId.'/view';
        }

        return $this->rutaArchivo ? '/'.ltrim($this->rutaArchivo, '/') : null;
    }

    /**
     * Enlace apto para src de <img>: Drive sirve el binario por
     * lh3.googleusercontent.com; lo local ya es una URL de la app.
     */
    public function getUrlImagenAttribute($valor = null): ?string
    {
        if ($this->driveFileId && $this->esImagen()) {
            return 'https://lh3.googleusercontent.com/d/'.$this->driveFileId;
        }

        return $this->url;
    }

    /** Indica si la evidencia es una imagen (para la galería). */
    public function esImagen(): bool
    {
        if (str_starts_with((string) $this->tipoContenido, 'image/')) {
            return true;
        }

        return in_array(strtolower((string) $this->extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
    }
}
