<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketComentario extends Model
{
    protected $table = 'tblTicketComentarios';
    protected $primaryKey = 'id_TicketComentario';
    public $timestamps = false;
    protected $fillable = ['id_Ticket', 'comentario', 'fechaComentario', 'id_Usuario', 'id_Estatus', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaComentario' => 'datetime', 'fechaAlta' => 'datetime'];

    public function ticket() { return $this->belongsTo(Ticket::class, 'id_Ticket'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
    public function estatus() { return $this->belongsTo(Estatus::class, 'id_Estatus'); }
}
