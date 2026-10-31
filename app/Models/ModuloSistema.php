<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuloSistema extends Model
{
    protected $table = 'tblModulosSistemas';
    protected $primaryKey = 'id_ModulosSistemas';
    public $timestamps = false;
    protected $fillable = ['nombre', 'id_sistemas', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];

    public function sistema()
    {
        return $this->belongsTo(Sistema::class, 'id_sistemas');
    }

    public function submodulosSistema()
    {
        return $this->hasMany(SubModuloSistema::class, 'id_ModulosSistemas');
    }
}
