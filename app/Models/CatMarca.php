<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatMarca extends Model
{
    protected $table = 'tblCatMarca';
    protected $primaryKey = 'id_Marca';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function modelos() { return $this->hasMany(CatModelo::class, 'id_Marca'); }
}
