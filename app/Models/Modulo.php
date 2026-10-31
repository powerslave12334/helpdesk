<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'tblModulos';
    protected $primaryKey = 'id_Modulos';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo', 'orden'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function subModulos() { return $this->hasMany(SubModulo::class, 'id_Modulos'); }
}
