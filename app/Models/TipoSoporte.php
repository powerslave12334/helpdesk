<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoSoporte extends Model
{
    protected $table = 'tblTipoSoporte';
    protected $primaryKey = 'id_TipoSoporte';
    public $timestamps = false;
    protected $fillable = ['descripcion', 'fechaAlta', 'activo', 'id_Area'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function area() { return $this->belongsTo(Area::class, 'id_Area'); }
    public function tickets() { return $this->hasMany(Ticket::class, 'id_TipoSoporte'); }
}
