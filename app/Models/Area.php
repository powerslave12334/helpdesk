<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    protected $table = 'tblArea';
    protected $primaryKey = 'id_Area';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function usuarios() { return $this->hasMany(Usuario::class, 'id_Area'); }
}
