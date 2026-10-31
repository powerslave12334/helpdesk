<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'tblNotificaciones';
    protected $primaryKey = 'id_Notificacion';
    public $timestamps = false;
    protected $fillable = ['id_UsuarioDestino', 'Mensaje', 'Tipo', 'UrlAccion', 'Leido', 'FechaCreacion'];
    protected $casts = ['Leido' => 'boolean', 'FechaCreacion' => 'datetime'];

    public function usuarioDestino() { return $this->belongsTo(Usuario::class, 'id_UsuarioDestino'); }
}
