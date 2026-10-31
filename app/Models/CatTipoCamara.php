<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatTipoCamara extends Model
{
    protected $table = 'tblCatTipoCamara';
    protected $primaryKey = 'id_TipoCamara';
    public $timestamps = false;
    protected $fillable = ['nombre', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];
}
