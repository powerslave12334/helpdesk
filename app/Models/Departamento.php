<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Departamento extends Model
{
    protected $table = 'tblDepto';
    protected $primaryKey = 'id_Depto';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function usuarios() { return $this->hasMany(Usuario::class, 'id_Depto'); }
}
