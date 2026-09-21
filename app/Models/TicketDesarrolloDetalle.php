<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketDesarrolloDetalle extends Model
{
    protected $table = 'tblTicketDesarrolloDetalle';
    protected $primaryKey = 'id_TicketDesarrolloDetalle';
    public $timestamps = false;
    protected $fillable = ['id_Ticket', 'id_TipoDesarrollo', 'id_Sistemas', 'id_Modulo', 'id_Submodulo', 'aprobado', 'id_UsuarioAprobo', 'fechaAprobo', 'fechaEstimadaTermino'];
    protected $casts = ['aprobado' => 'boolean', 'fechaAprobo' => 'datetime', 'fechaEstimadaTermino' => 'datetime'];

    public function ticket() { return $this->belongsTo(Ticket::class, 'id_Ticket'); }
    public function tipoDesarrollo() { return $this->belongsTo(TipoDesarrollo::class, 'id_TipoDesarrollo'); }
    public function sistema() { return $this->belongsTo(Sistema::class, 'id_Sistemas'); }
}
