<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketEvidencia extends Model
{
    protected $table = 'tblTicketEvidencia';
    protected $primaryKey = 'id_TicketEvidencia';
    public $timestamps = false;
    protected $fillable = ['id_Ticket', 'nombreArchivo', 'rutaArchivo', 'extension', 'tipoContenido', 'tamanioBytes', 'id_UsuarioAlta', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime', 'tamanioBytes' => 'integer'];

    public function ticket() { return $this->belongsTo(Ticket::class, 'id_Ticket'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_UsuarioAlta'); }
}
