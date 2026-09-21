<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipoCampoDefinicion extends Model
{
    protected $table = 'tblEquipoCampo';
    protected $primaryKey = 'id_EquipoCampo';
    public $timestamps = false;
    protected $fillable = ['nombre', 'activo'];
    protected $casts = ['activo' => 'boolean'];
}
