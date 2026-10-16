<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatModelo extends Model
{
    protected $table = 'tblCatModelo';
    protected $primaryKey = 'id_Modelo';
    public $timestamps = false;
    protected $fillable = ['id_Marca', 'nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function marca() { return $this->belongsTo(CatMarca::class, 'id_Marca'); }
}
