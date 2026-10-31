<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolSubModulo extends Model
{
    protected $table = 'tblRolSubModulo';
    protected $primaryKey = 'id_RolSubModulo';
    public $timestamps = false;
    protected $fillable = ['id_Rol', 'id_SubModulos', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public function rol() { return $this->belongsTo(Rol::class, 'id_Rol'); }
    public function subModulo() { return $this->belongsTo(SubModulo::class, 'id_SubModulos'); }
}
