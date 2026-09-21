<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubModuloSistema extends Model
{
    protected $table = 'tblSubmodulosSistemas';
    protected $primaryKey = 'id_SubmodulosSistemas';
    public $timestamps = false;
    protected $fillable = ['nombre', 'id_ModulosSistemas', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];

    public function moduloSistema()
    {
        return $this->belongsTo(ModuloSistema::class, 'id_ModulosSistemas');
    }
}
