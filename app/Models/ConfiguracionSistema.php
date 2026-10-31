<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfiguracionSistema extends Model
{
    protected $table = 'tblConfiguracionSistema';
    protected $primaryKey = 'id_ConfiguracionSistema';
    public $timestamps = false;
    protected $fillable = ['clave', 'valor', 'descripcion', 'fechaAlta', 'activo'];
    protected $casts = ['activo' => 'boolean', 'fechaAlta' => 'datetime'];

    public static function obtener($clave, $default = null)
    {
        $config = static::where('clave', $clave)->where('activo', true)->first();
        return $config ? $config->valor : $default;
    }
}
