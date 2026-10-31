<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketArchivo extends Model
{
    protected $table = 'tblTicketArchivo';
    protected $primaryKey = 'id_TicketArchivo';
    public $timestamps = false;
    protected $fillable = ['id_Ticket', 'id_Usuario', 'tipoArchivo', 'ruta', 'nombreOriginal', 'extension', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function ticket() { return $this->belongsTo(Ticket::class, 'id_Ticket'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_Usuario'); }
}
