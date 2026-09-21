<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tblTicket';
    protected $primaryKey = 'id_Ticket';
    public $timestamps = false;
    protected $fillable = [
        'id_Usuario', 'id_TipoSoporte', 'id_Tecnico', 'asunto', 'problema',
        'img', 'fechaAlta', 'activo', 'id_Estatus', 'id_Equipo', 'fechaCierre',
    ];
    protected $casts = [
        'activo' => 'boolean',
        'fechaAlta' => 'datetime',
        'fechaCierre' => 'datetime',
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'id_Usuario');
    }

    public function tecnico()
    {
        return $this->belongsTo(Usuario::class, 'id_Tecnico');
    }

    public function estatus()
    {
        return $this->belongsTo(Estatus::class, 'id_Estatus');
    }

    public function tipoSoporte()
    {
        return $this->belongsTo(TipoSoporte::class, 'id_TipoSoporte');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'id_Equipo');
    }

    public function comentarios()
    {
        return $this->hasMany(TicketComentario::class, 'id_Ticket');
    }

    public function evidencias()
    {
        return $this->hasMany(TicketEvidencia::class, 'id_Ticket');
    }

    public function desarrolloDetalle()
    {
        return $this->hasOne(TicketDesarrolloDetalle::class, 'id_Ticket');
    }
}
